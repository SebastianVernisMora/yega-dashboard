<?php

namespace Yega\GitHub\Config;

class GitHubConfig
{
    private string $token;
    private string $owner;
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->token = $_ENV['GITHUB_TOKEN'] ?? '';
        $this->owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
        $this->baseUrl = $_ENV['GITHUB_API_URL'] ?? 'https://api.github.com/';
        $this->timeout = (int) ($_ENV['GITHUB_TIMEOUT'] ?? 30);
        
        if (empty($this->token)) {
            throw new \InvalidArgumentException('GITHUB_TOKEN es requerido');
        }
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function toArray(): array
    {
        return [
            'owner' => $this->owner,
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout
        ];
    }
}