<?php

namespace Yega\GitHub\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yega\GitHub\Services\RateLimitService;
use Psr\Log\LoggerInterface;

class RateLimitMiddleware
{
    private RateLimitService $rateLimitService;
    private LoggerInterface $logger;

    public function __construct(RateLimitService $rateLimitService, LoggerInterface $logger)
    {
        $this->rateLimitService = $rateLimitService;
        $this->logger = $logger;
    }

    public function handle(Request $request, callable $next): Response
    {
        try {
            $this->rateLimitService->checkRateLimit();
            
            $response = $next($request);
            
            // Añadir headers de rate limit a la respuesta
            $status = $this->rateLimitService->getStatus();
            
            if ($response instanceof JsonResponse) {
                $data = json_decode($response->getContent(), true);
                if (isset($data['meta'])) {
                    $data['meta']['rate_limit'] = $status;
                } else {
                    $data['rate_limit'] = $status;
                }
                $response->setContent(json_encode($data));
            }
            
            $response->headers->set('X-RateLimit-Remaining', (string) $status['remaining']);
            $response->headers->set('X-RateLimit-Reset', (string) $status['reset']);
            
            return $response;
            
        } catch (\Exception $e) {
            $this->logger->warning('Rate limit excedido', [
                'ip' => $request->getClientIp(),
                'endpoint' => $request->getPathInfo(),
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'retry_after' => method_exists($e, 'getWaitTime') ? $e->getWaitTime() : 60
            ], 429);
        }
    }
}