<?php

namespace Yega\GitHub\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class CacheMiddleware
{
    private RedisClient $redis;
    private LoggerInterface $logger;
    private int $defaultTtl;

    public function __construct(RedisClient $redis, LoggerInterface $logger, int $defaultTtl = 300)
    {
        $this->redis = $redis;
        $this->logger = $logger;
        $this->defaultTtl = $defaultTtl;
    }

    public function handle(Request $request, callable $next): Response
    {
        // Solo cachear GET requests
        if ($request->getMethod() !== 'GET') {
            return $next($request);
        }
        
        $cacheKey = $this->generateCacheKey($request);
        
        // Verificar cache
        $cached = $this->redis->get($cacheKey);
        if ($cached) {
            $this->logger->debug('Cache hit', ['key' => $cacheKey]);
            
            $cachedData = json_decode($cached, true);
            $response = new JsonResponse($cachedData['data']);
            $response->headers->set('X-Cache', 'HIT');
            $response->headers->set('X-Cache-Age', (string) (time() - $cachedData['timestamp']));
            
            return $response;
        }
        
        // Ejecutar request
        $response = $next($request);
        
        // Cachear respuesta exitosa
        if ($response instanceof JsonResponse && $response->getStatusCode() === 200) {
            $data = json_decode($response->getContent(), true);
            
            if (isset($data['success']) && $data['success']) {
                $cacheData = [
                    'data' => $data,
                    'timestamp' => time()
                ];
                
                $ttl = $this->getTtlForEndpoint($request->getPathInfo());
                $this->redis->setex($cacheKey, $ttl, json_encode($cacheData));
                
                $response->headers->set('X-Cache', 'MISS');
                $response->headers->set('X-Cache-TTL', (string) $ttl);
                
                $this->logger->debug('Response cached', [
                    'key' => $cacheKey,
                    'ttl' => $ttl
                ]);
            }
        }
        
        return $response;
    }

    private function generateCacheKey(Request $request): string
    {
        $parts = [
            'api_cache',
            $request->getPathInfo(),
            md5($request->getQueryString() ?? '')
        ];
        
        return implode(':', $parts);
    }

    private function getTtlForEndpoint(string $path): int
    {
        // TTL específico por tipo de endpoint
        if (strpos($path, '/repositories') !== false) {
            return 600; // 10 minutos
        }
        
        if (strpos($path, '/stats') !== false) {
            return 3600; // 1 hora
        }
        
        if (strpos($path, '/sync') !== false) {
            return 60; // 1 minuto
        }
        
        return $this->defaultTtl;
    }
}