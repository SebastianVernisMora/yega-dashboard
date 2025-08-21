<?php

namespace Yega\GitHub\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Yega\GitHub\Exceptions\GitHubApiException;
use Yega\GitHub\Config\GitHubConfig;
use Psr\Log\LoggerInterface;
use Predis\Client as RedisClient;

class GitHubService
{
    private Client $httpClient;
    private GitHubConfig $config;
    private LoggerInterface $logger;
    private RedisClient $redis;
    private RateLimitService $rateLimitService;

    private const YEGA_REPOSITORIES = [
        'yega-core',
        'yega-dashboard', 
        'yega-api',
        'yega-frontend',
        'yega-mobile'
    ];

    public function __construct(
        GitHubConfig $config,
        LoggerInterface $logger,
        RedisClient $redis,
        RateLimitService $rateLimitService
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->redis = $redis;
        $this->rateLimitService = $rateLimitService;
        
        $this->httpClient = new Client([
            'base_uri' => 'https://api.github.com/',
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config->getToken(),
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'Yega-GitHub-API/1.0'
            ]
        ]);
    }

    /**
     * Realiza request autenticado a GitHub API
     */
    public function makeRequest(string $endpoint, string $method = 'GET', array $data = []): array
    {
        $this->rateLimitService->checkRateLimit();
        
        $cacheKey = $this->getCacheKey($endpoint, $method, $data);
        
        // Verificar cache para GET requests
        if ($method === 'GET') {
            $cached = $this->redis->get($cacheKey);
            if ($cached) {
                $this->logger->info('Cache hit para endpoint: ' . $endpoint);
                return json_decode($cached, true);
            }
        }

        try {
            $options = [];
            if (!empty($data)) {
                $options['json'] = $data;
            }

            $response = $this->httpClient->request($method, $endpoint, $options);
            $responseData = json_decode($response->getBody()->getContents(), true);
            
            // Actualizar rate limit info
            $this->rateLimitService->updateFromHeaders($response->getHeaders());
            
            // Cache para GET requests (5 minutos)
            if ($method === 'GET') {
                $this->redis->setex($cacheKey, 300, json_encode($responseData));
            }
            
            $this->logger->info('Request exitoso a GitHub API', [
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $response->getStatusCode()
            ]);
            
            return $responseData;
            
        } catch (GuzzleException $e) {
            $this->logger->error('Error en GitHub API request', [
                'endpoint' => $endpoint,
                'method' => $method,
                'error' => $e->getMessage()
            ]);
            
            throw new GitHubApiException(
                'Error en GitHub API: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Obtiene información de repositorio
     */
    public function getRepository(string $owner, string $repo): array
    {
        return $this->makeRequest("repos/{$owner}/{$repo}");
    }

    /**
     * Lista repositorios de Yega
     */
    public function getYegaRepositories(): array
    {
        $repositories = [];
        $owner = $this->config->getOwner();
        
        foreach (self::YEGA_REPOSITORIES as $repo) {
            try {
                $repositories[$repo] = $this->getRepository($owner, $repo);
            } catch (GitHubApiException $e) {
                $this->logger->warning("No se pudo obtener repo {$repo}", ['error' => $e->getMessage()]);
                $repositories[$repo] = null;
            }
        }
        
        return $repositories;
    }

    /**
     * Obtiene issues con paginación
     */
    public function getIssues(string $owner, string $repo, array $params = []): array
    {
        $defaultParams = [
            'state' => 'all',
            'per_page' => 100,
            'page' => 1
        ];
        
        $params = array_merge($defaultParams, $params);
        $queryString = http_build_query($params);
        
        return $this->makeRequest("repos/{$owner}/{$repo}/issues?{$queryString}");
    }

    /**
     * Obtiene pull requests con paginación
     */
    public function getPullRequests(string $owner, string $repo, array $params = []): array
    {
        $defaultParams = [
            'state' => 'all',
            'per_page' => 100,
            'page' => 1
        ];
        
        $params = array_merge($defaultParams, $params);
        $queryString = http_build_query($params);
        
        return $this->makeRequest("repos/{$owner}/{$repo}/pulls?{$queryString}");
    }

    /**
     * Obtiene commits con paginación
     */
    public function getCommits(string $owner, string $repo, array $params = []): array
    {
        $defaultParams = [
            'per_page' => 100,
            'page' => 1
        ];
        
        $params = array_merge($defaultParams, $params);
        $queryString = http_build_query($params);
        
        return $this->makeRequest("repos/{$owner}/{$repo}/commits?{$queryString}");
    }

    /**
     * Crea un issue
     */
    public function createIssue(string $owner, string $repo, array $issueData): array
    {
        return $this->makeRequest("repos/{$owner}/{$repo}/issues", 'POST', $issueData);
    }

    /**
     * Actualiza un issue
     */
    public function updateIssue(string $owner, string $repo, int $issueNumber, array $issueData): array
    {
        return $this->makeRequest("repos/{$owner}/{$repo}/issues/{$issueNumber}", 'PATCH', $issueData);
    }

    /**
     * Genera clave de cache
     */
    private function getCacheKey(string $endpoint, string $method, array $data): string
    {
        return 'github_api:' . md5($method . ':' . $endpoint . ':' . serialize($data));
    }

    /**
     * Obtiene estadísticas del repositorio
     */
    public function getRepositoryStats(string $owner, string $repo): array
    {
        $cacheKey = "repo_stats:{$owner}:{$repo}";
        $cached = $this->redis->get($cacheKey);
        
        if ($cached) {
            return json_decode($cached, true);
        }

        $stats = [
            'repository' => $this->getRepository($owner, $repo),
            'issues_count' => $this->getIssuesCount($owner, $repo),
            'prs_count' => $this->getPullRequestsCount($owner, $repo),
            'contributors' => $this->getContributors($owner, $repo),
            'languages' => $this->getLanguages($owner, $repo)
        ];
        
        // Cache por 1 hora
        $this->redis->setex($cacheKey, 3600, json_encode($stats));
        
        return $stats;
    }

    private function getIssuesCount(string $owner, string $repo): array
    {
        $open = $this->makeRequest("repos/{$owner}/{$repo}/issues?state=open&per_page=1");
        $closed = $this->makeRequest("repos/{$owner}/{$repo}/issues?state=closed&per_page=1");
        
        return [
            'open' => count($open),
            'closed' => count($closed)
        ];
    }

    private function getPullRequestsCount(string $owner, string $repo): array
    {
        $open = $this->makeRequest("repos/{$owner}/{$repo}/pulls?state=open&per_page=1");
        $closed = $this->makeRequest("repos/{$owner}/{$repo}/pulls?state=closed&per_page=1");
        
        return [
            'open' => count($open),
            'closed' => count($closed)
        ];
    }

    private function getContributors(string $owner, string $repo): array
    {
        return $this->makeRequest("repos/{$owner}/{$repo}/contributors?per_page=10");
    }

    private function getLanguages(string $owner, string $repo): array
    {
        return $this->makeRequest("repos/{$owner}/{$repo}/languages");
    }

    /**
     * Obtiene los repositorios Yega configurados
     */
    public function getYegaRepositoryNames(): array
    {
        return self::YEGA_REPOSITORIES;
    }
}