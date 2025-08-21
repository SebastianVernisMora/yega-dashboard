<?php

namespace Yega\Dashboard;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class GitHubAPI
{
    private Client $client;
    private string $token;
    private Logger $logger;
    private array $cache = [];
    private int $cacheTtl;

    public function __construct(string $token, int $cacheTtl = 3600)
    {
        $this->token = $token;
        $this->cacheTtl = $cacheTtl;
        
        $this->client = new Client([
            'base_uri' => 'https://api.github.com/',
            'headers' => [
                'Authorization' => 'token ' . $token,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'YegaDashboard/1.0'
            ],
            'timeout' => 30
        ]);

        $this->logger = new Logger('github_api');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/github.log', Logger::INFO));
    }

    public function getRepository(string $owner, string $repo): ?array
    {
        $cacheKey = "repo_{$owner}_{$repo}";
        
        if ($this->getCachedData($cacheKey)) {
            return $this->getCachedData($cacheKey);
        }

        try {
            $response = $this->client->get("repos/{$owner}/{$repo}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            $this->setCachedData($cacheKey, $data);
            $this->logger->info("Repository data fetched for {$owner}/{$repo}");
            
            return $data;
        } catch (GuzzleException $e) {
            $this->logger->error("Error fetching repository {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }

    public function getIssues(string $owner, string $repo, string $state = 'all'): ?array
    {
        $cacheKey = "issues_{$owner}_{$repo}_{$state}";
        
        if ($this->getCachedData($cacheKey)) {
            return $this->getCachedData($cacheKey);
        }

        try {
            $response = $this->client->get("repos/{$owner}/{$repo}/issues", [
                'query' => [
                    'state' => $state,
                    'per_page' => 100,
                    'sort' => 'updated',
                    'direction' => 'desc'
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            // Filtrar solo issues (no PRs)
            $issues = array_filter($data, function($item) {
                return !isset($item['pull_request']);
            });
            
            $this->setCachedData($cacheKey, $issues);
            $this->logger->info("Issues fetched for {$owner}/{$repo}");
            
            return $issues;
        } catch (GuzzleException $e) {
            $this->logger->error("Error fetching issues for {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }

    public function getPullRequests(string $owner, string $repo, string $state = 'all'): ?array
    {
        $cacheKey = "prs_{$owner}_{$repo}_{$state}";
        
        if ($this->getCachedData($cacheKey)) {
            return $this->getCachedData($cacheKey);
        }

        try {
            $response = $this->client->get("repos/{$owner}/{$repo}/pulls", [
                'query' => [
                    'state' => $state,
                    'per_page' => 100,
                    'sort' => 'updated',
                    'direction' => 'desc'
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $this->setCachedData($cacheKey, $data);
            $this->logger->info("Pull requests fetched for {$owner}/{$repo}");
            
            return $data;
        } catch (GuzzleException $e) {
            $this->logger->error("Error fetching PRs for {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }

    public function getCommits(string $owner, string $repo, int $days = 30): ?array
    {
        $cacheKey = "commits_{$owner}_{$repo}_{$days}";
        
        if ($this->getCachedData($cacheKey)) {
            return $this->getCachedData($cacheKey);
        }

        try {
            $since = (new \DateTime())->modify("-{$days} days")->format('c');
            
            $response = $this->client->get("repos/{$owner}/{$repo}/commits", [
                'query' => [
                    'since' => $since,
                    'per_page' => 100
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $this->setCachedData($cacheKey, $data);
            $this->logger->info("Commits fetched for {$owner}/{$repo}");
            
            return $data;
        } catch (GuzzleException $e) {
            $this->logger->error("Error fetching commits for {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }

    public function getReadme(string $owner, string $repo): ?string
    {
        $cacheKey = "readme_{$owner}_{$repo}";
        
        if ($this->getCachedData($cacheKey)) {
            return $this->getCachedData($cacheKey);
        }

        try {
            $response = $this->client->get("repos/{$owner}/{$repo}/readme");
            $data = json_decode($response->getBody()->getContents(), true);
            
            $content = base64_decode($data['content']);
            $this->setCachedData($cacheKey, $content);
            $this->logger->info("README fetched for {$owner}/{$repo}");
            
            return $content;
        } catch (GuzzleException $e) {
            $this->logger->error("Error fetching README for {$owner}/{$repo}: " . $e->getMessage());
            return null;
        }
    }

    private function getCachedData(string $key): mixed
    {
        if (!isset($this->cache[$key])) {
            return null;
        }
        
        $data = $this->cache[$key];
        if ($data['expires'] < time()) {
            unset($this->cache[$key]);
            return null;
        }
        
        return $data['value'];
    }

    private function setCachedData(string $key, mixed $value): void
    {
        $this->cache[$key] = [
            'value' => $value,
            'expires' => time() + $this->cacheTtl
        ];
    }
}