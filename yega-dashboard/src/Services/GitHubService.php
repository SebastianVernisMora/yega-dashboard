<?php

namespace YegaDashboard\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use YegaDashboard\Config\GitHubConfig;
use YegaDashboard\Config\DatabaseConfig;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class GitHubService
{
    private $client;
    private $token;
    private $db;
    private $logger;
    private $rateLimitService;

    public function __construct()
    {
        $this->token = GitHubConfig::getToken();
        $this->client = new Client([
            'base_uri' => GitHubConfig::getApiUrl(),
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'token ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Yega-Dashboard/1.0'
            ]
        ]);
        
        $this->db = DatabaseConfig::getInstance()->getConnection();
        $this->logger = new Logger('github-service');
        $this->logger->pushHandler(new StreamHandler('logs/github.log', Logger::INFO));
        $this->rateLimitService = new RateLimitService();
    }

    public function syncAllRepositories(): array
    {
        $results = [];
        $repositories = GitHubConfig::getRepositories();
        
        foreach ($repositories as $repoFullName) {
            try {
                $this->rateLimitService->checkLimit();
                $result = $this->syncRepository($repoFullName);
                $results[$repoFullName] = $result;
                $this->logger->info("Repositorio sincronizado: {$repoFullName}");
            } catch (\Exception $e) {
                $this->logger->error("Error sincronizando {$repoFullName}: " . $e->getMessage());
                $results[$repoFullName] = ['error' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    public function syncRepository(string $fullName): array
    {
        [$owner, $name] = explode('/', $fullName);
        
        // Obtener datos del repositorio
        $repoData = $this->getRepositoryData($owner, $name);
        
        // Guardar o actualizar repositorio
        $repoId = $this->saveRepository($repoData);
        
        // Sincronizar issues, PRs, commits y README
        $issuesCount = $this->syncIssues($repoId, $owner, $name);
        $prsCount = $this->syncPullRequests($repoId, $owner, $name);
        $commitsCount = $this->syncCommits($repoId, $owner, $name);
        $this->syncReadme($repoId, $owner, $name);
        
        return [
            'repository_id' => $repoId,
            'issues_synced' => $issuesCount,
            'prs_synced' => $prsCount,
            'commits_synced' => $commitsCount
        ];
    }

    private function getRepositoryData(string $owner, string $name): array
    {
        try {
            $response = $this->client->get("/repos/{$owner}/{$name}");
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new \Exception("Error obteniendo datos del repositorio: " . $e->getMessage());
        }
    }

    private function saveRepository(array $repoData): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO repositories 
            (name, owner, full_name, description, language, stars, forks, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            description = VALUES(description),
            language = VALUES(language),
            stars = VALUES(stars),
            forks = VALUES(forks),
            updated_at = VALUES(updated_at)
        ");
        
        $stmt->execute([
            $repoData['name'],
            $repoData['owner']['login'],
            $repoData['full_name'],
            $repoData['description'],
            $repoData['language'],
            $repoData['stargazers_count'],
            $repoData['forks_count'],
            date('Y-m-d H:i:s', strtotime($repoData['created_at'])),
            date('Y-m-d H:i:s', strtotime($repoData['updated_at']))
        ]);
        
        // Obtener ID del repositorio
        if ($this->db->lastInsertId()) {
            return (int) $this->db->lastInsertId();
        }
        
        // Si fue UPDATE, obtener ID existente
        $stmt = $this->db->prepare("SELECT id FROM repositories WHERE full_name = ?");
        $stmt->execute([$repoData['full_name']]);
        return (int) $stmt->fetchColumn();
    }

    private function syncIssues(int $repoId, string $owner, string $name): int
    {
        try {
            $response = $this->client->get("/repos/{$owner}/{$name}/issues", [
                'query' => ['state' => 'all', 'per_page' => 100]
            ]);
            
            $issues = json_decode($response->getBody(), true);
            $count = 0;
            
            foreach ($issues as $issue) {
                // Filtrar pull requests (GitHub incluye PRs en issues)
                if (isset($issue['pull_request'])) continue;
                
                $this->saveIssue($repoId, $issue);
                $count++;
            }
            
            return $count;
        } catch (RequestException $e) {
            $this->logger->warning("Error sincronizando issues: " . $e->getMessage());
            return 0;
        }
    }

    private function saveIssue(int $repoId, array $issueData): void
    {
        $labels = isset($issueData['labels']) ? json_encode($issueData['labels']) : null;
        
        $stmt = $this->db->prepare("
            INSERT INTO issues 
            (repo_id, number, title, state, body, author, labels, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            state = VALUES(state),
            body = VALUES(body),
            labels = VALUES(labels),
            updated_at = VALUES(updated_at)
        ");
        
        $stmt->execute([
            $repoId,
            $issueData['number'],
            $issueData['title'],
            $issueData['state'],
            $issueData['body'],
            $issueData['user']['login'],
            $labels,
            date('Y-m-d H:i:s', strtotime($issueData['created_at'])),
            date('Y-m-d H:i:s', strtotime($issueData['updated_at']))
        ]);
    }

    private function syncPullRequests(int $repoId, string $owner, string $name): int
    {
        try {
            $response = $this->client->get("/repos/{$owner}/{$name}/pulls", [
                'query' => ['state' => 'all', 'per_page' => 100]
            ]);
            
            $prs = json_decode($response->getBody(), true);
            $count = 0;
            
            foreach ($prs as $pr) {
                $this->savePullRequest($repoId, $pr);
                $count++;
            }
            
            return $count;
        } catch (RequestException $e) {
            $this->logger->warning("Error sincronizando PRs: " . $e->getMessage());
            return 0;
        }
    }

    private function savePullRequest(int $repoId, array $prData): void
    {
        $mergedAt = $prData['merged_at'] ? date('Y-m-d H:i:s', strtotime($prData['merged_at'])) : null;
        
        $stmt = $this->db->prepare("
            INSERT INTO pull_requests 
            (repo_id, number, title, state, body, author, merged_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            state = VALUES(state),
            body = VALUES(body),
            merged_at = VALUES(merged_at),
            updated_at = VALUES(updated_at)
        ");
        
        $stmt->execute([
            $repoId,
            $prData['number'],
            $prData['title'],
            $prData['state'],
            $prData['body'],
            $prData['user']['login'],
            $mergedAt,
            date('Y-m-d H:i:s', strtotime($prData['created_at'])),
            date('Y-m-d H:i:s', strtotime($prData['updated_at']))
        ]);
    }

    private function syncCommits(int $repoId, string $owner, string $name, int $limit = 50): int
    {
        try {
            $response = $this->client->get("/repos/{$owner}/{$name}/commits", [
                'query' => ['per_page' => $limit]
            ]);
            
            $commits = json_decode($response->getBody(), true);
            $count = 0;
            
            foreach ($commits as $commit) {
                $this->saveCommit($repoId, $commit);
                $count++;
            }
            
            return $count;
        } catch (RequestException $e) {
            $this->logger->warning("Error sincronizando commits: " . $e->getMessage());
            return 0;
        }
    }

    private function saveCommit(int $repoId, array $commitData): void
    {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO commits 
            (repo_id, sha, message, author, date, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $authorName = $commitData['commit']['author']['name'] ?? 'Unknown';
        $commitDate = date('Y-m-d H:i:s', strtotime($commitData['commit']['author']['date']));
        
        $stmt->execute([
            $repoId,
            $commitData['sha'],
            $commitData['commit']['message'],
            $authorName,
            $commitDate
        ]);
    }

    private function syncReadme(int $repoId, string $owner, string $name): void
    {
        try {
            $response = $this->client->get("/repos/{$owner}/{$name}/readme");
            $readmeData = json_decode($response->getBody(), true);
            
            $content = base64_decode($readmeData['content']);
            
            $stmt = $this->db->prepare("
                INSERT INTO readme_content 
                (repo_id, content, last_updated, created_at, updated_at)
                VALUES (?, ?, NOW(), NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                content = VALUES(content),
                last_updated = VALUES(last_updated),
                updated_at = VALUES(updated_at)
            ");
            
            $stmt->execute([$repoId, $content]);
        } catch (RequestException $e) {
            $this->logger->info("README no encontrado para {$owner}/{$name}");
        }
    }
}
