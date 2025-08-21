<?php

namespace YegaDashboard\Services;

use YegaDashboard\Config\GitHubConfig;

class RateLimitService
{
    private $cacheFile;
    private $maxRequests;
    private $windowSize; // en segundos

    public function __construct()
    {
        $this->cacheFile = __DIR__ . '/../../cache/rate_limit.json';
        $this->maxRequests = GitHubConfig::getRateLimit();
        $this->windowSize = 3600; // 1 hora
        
        // Crear directorio cache si no existe
        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }

    public function checkLimit(): bool
    {
        $currentTime = time();
        $requests = $this->getRequestHistory();
        
        // Filtrar requests dentro de la ventana de tiempo
        $recentRequests = array_filter($requests, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < $this->windowSize;
        });
        
        if (count($recentRequests) >= $this->maxRequests) {
            $oldestRequest = min($recentRequests);
            $waitTime = $this->windowSize - ($currentTime - $oldestRequest);
            
            throw new \Exception(
                "Límite de rate limit alcanzado. Espera {$waitTime} segundos."
            );
        }
        
        // Registrar nueva request
        $this->recordRequest($currentTime);
        return true;
    }

    private function getRequestHistory(): array
    {
        if (!file_exists($this->cacheFile)) {
            return [];
        }
        
        $content = file_get_contents($this->cacheFile);
        $data = json_decode($content, true);
        
        return $data['requests'] ?? [];
    }

    private function recordRequest(int $timestamp): void
    {
        $requests = $this->getRequestHistory();
        $requests[] = $timestamp;
        
        // Mantener solo requests recientes
        $cutoff = $timestamp - $this->windowSize;
        $requests = array_filter($requests, function($ts) use ($cutoff) {
            return $ts > $cutoff;
        });
        
        $data = ['requests' => array_values($requests)];
        file_put_contents($this->cacheFile, json_encode($data));
    }

    public function getRemainingRequests(): int
    {
        $requests = $this->getRequestHistory();
        $currentTime = time();
        
        $recentRequests = array_filter($requests, function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < $this->windowSize;
        });
        
        return max(0, $this->maxRequests - count($recentRequests));
    }

    public function getResetTime(): int
    {
        $requests = $this->getRequestHistory();
        if (empty($requests)) {
            return time();
        }
        
        $oldestRequest = min($requests);
        return $oldestRequest + $this->windowSize;
    }
}
