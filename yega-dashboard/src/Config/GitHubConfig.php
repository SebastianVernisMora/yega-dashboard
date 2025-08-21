<?php

namespace YegaDashboard\Config;

class GitHubConfig
{
    public const API_BASE_URL = 'https://api.github.com';
    public const RATE_LIMIT_PER_HOUR = 5000;
    public const CACHE_TTL = 3600; // 1 hora
    
    private static $repositories = [
        'SebastianVernisMora/Yega-Ecosistema',
        'SebastianVernisMora/Yega-API',
        'SebastianVernisMora/Yega-Tienda',
        'SebastianVernisMora/Yega-Repartidor',
        'SebastianVernisMora/Yega-Cliente'
    ];

    public static function getToken(): string
    {
        $token = $_ENV['GITHUB_TOKEN'] ?? '';
        if (empty($token)) {
            throw new \Exception('GITHUB_TOKEN no configurado en las variables de entorno');
        }
        return $token;
    }

    public static function getRepositories(): array
    {
        $envRepos = $_ENV['REPOSITORIES'] ?? '';
        if (!empty($envRepos)) {
            return array_map('trim', explode(',', $envRepos));
        }
        return self::$repositories;
    }

    public static function getApiUrl(): string
    {
        return $_ENV['GITHUB_API_URL'] ?? self::API_BASE_URL;
    }

    public static function getRateLimit(): int
    {
        return (int) ($_ENV['RATE_LIMIT_PER_HOUR'] ?? self::RATE_LIMIT_PER_HOUR);
    }

    public static function getCacheTtl(): int
    {
        return (int) ($_ENV['CACHE_TTL'] ?? self::CACHE_TTL);
    }
}
