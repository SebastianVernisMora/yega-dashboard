<?php

namespace Yega\GitHub\Services;

use Yega\GitHub\Exceptions\RateLimitException;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;

class RateLimitService
{
    private RedisClient $redis;
    private LoggerInterface $logger;
    private const RATE_LIMIT_KEY = 'github_rate_limit';
    private const RATE_LIMIT_RESET_KEY = 'github_rate_limit_reset';
    
    public function __construct(RedisClient $redis, LoggerInterface $logger)
    {
        $this->redis = $redis;
        $this->logger = $logger;
    }

    /**
     * Verifica si podemos hacer requests
     */
    public function checkRateLimit(): void
    {
        $remaining = (int) $this->redis->get(self::RATE_LIMIT_KEY) ?: 5000;
        $reset = (int) $this->redis->get(self::RATE_LIMIT_RESET_KEY) ?: time();
        
        if ($remaining <= 10 && time() < $reset) {
            $waitTime = $reset - time();
            $this->logger->warning('Rate limit casi alcanzado', [
                'remaining' => $remaining,
                'reset_in' => $waitTime
            ]);
            
            if ($remaining <= 0) {
                throw new RateLimitException(
                    "Rate limit de GitHub alcanzado. Reset en {$waitTime} segundos.",
                    429
                );
            }
        }
    }

    /**
     * Actualiza info de rate limit desde headers de respuesta
     */
    public function updateFromHeaders(array $headers): void
    {
        $remaining = $this->getHeaderValue($headers, 'x-ratelimit-remaining');
        $reset = $this->getHeaderValue($headers, 'x-ratelimit-reset');
        $limit = $this->getHeaderValue($headers, 'x-ratelimit-limit');
        
        if ($remaining !== null) {
            $this->redis->set(self::RATE_LIMIT_KEY, $remaining);
        }
        
        if ($reset !== null) {
            $this->redis->set(self::RATE_LIMIT_RESET_KEY, $reset);
        }
        
        $this->logger->debug('Rate limit actualizado', [
            'remaining' => $remaining,
            'limit' => $limit,
            'reset' => $reset ? date('Y-m-d H:i:s', $reset) : null
        ]);
    }

    /**
     * Obtiene valor de header
     */
    private function getHeaderValue(array $headers, string $name): ?int
    {
        $name = strtolower($name);
        
        foreach ($headers as $key => $values) {
            if (strtolower($key) === $name && !empty($values)) {
                return (int) $values[0];
            }
        }
        
        return null;
    }

    /**
     * Obtiene status actual del rate limit
     */
    public function getStatus(): array
    {
        return [
            'remaining' => (int) $this->redis->get(self::RATE_LIMIT_KEY) ?: 5000,
            'reset' => (int) $this->redis->get(self::RATE_LIMIT_RESET_KEY) ?: time(),
            'reset_datetime' => date('Y-m-d H:i:s', (int) $this->redis->get(self::RATE_LIMIT_RESET_KEY) ?: time())
        ];
    }
}