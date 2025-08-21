<?php

namespace Yega\Dashboard;

use PDO;
use PDOException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Database
{
    private PDO $pdo;
    private Logger $logger;

    public function __construct(string $dsn, string $username, string $password)
    {
        $this->logger = new Logger('database');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/database.log', Logger::INFO));
        
        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            
            $this->logger->info('Database connection established');
        } catch (PDOException $e) {
            $this->logger->error('Database connection failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function saveRepository(array $repoData): int
    {
        $sql = "
            INSERT INTO repositories (name, owner, full_name, description, language, stars, forks, open_issues, created_at, updated_at)
            VALUES (:name, :owner, :full_name, :description, :language, :stars, :forks, :open_issues, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
            description = VALUES(description),
            language = VALUES(language),
            stars = VALUES(stars),
            forks = VALUES(forks),
            open_issues = VALUES(open_issues),
            updated_at = VALUES(updated_at)
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $repoData['name'],
            'owner' => $repoData['owner']['login'],
            'full_name' => $repoData['full_name'],
            'description' => $repoData['description'],
            'language' => $repoData['language'],
            'stars' => $repoData['stargazers_count'],
            'forks' => $repoData['forks_count'],
            'open_issues' => $repoData['open_issues_count'],
            'created_at' => date('Y-m-d H:i:s', strtotime($repoData['created_at'])),
            'updated_at' => date('Y-m-d H:i:s', strtotime($repoData['updated_at']))
        ]);
        
        return $this->getRepositoryId($repoData['full_name']);
    }

    public function saveIssues(int $repositoryId, array $issues): void
    {
        $sql = "
            INSERT INTO issues (repository_id, number, title, state, body, author, labels, created_at, updated_at, closed_at)
            VALUES (:repository_id, :number, :title, :state, :body, :author, :labels, :created_at, :updated_at, :closed_at)
            ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            state = VALUES(state),
            body = VALUES(body),
            labels = VALUES(labels),
            updated_at = VALUES(updated_at),
            closed_at = VALUES(closed_at)
        ";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($issues as $issue) {
            $labels = json_encode(array_column($issue['labels'], 'name'));
            
            $stmt->execute([
                'repository_id' => $repositoryId,
                'number' => $issue['number'],
                'title' => $issue['title'],
                'state' => $issue['state'],
                'body' => $issue['body'],
                'author' => $issue['user']['login'],
                'labels' => $labels,
                'created_at' => date('Y-m-d H:i:s', strtotime($issue['created_at'])),
                'updated_at' => date('Y-m-d H:i:s', strtotime($issue['updated_at'])),
                'closed_at' => $issue['closed_at'] ? date('Y-m-d H:i:s', strtotime($issue['closed_at'])) : null
            ]);
        }
        
        $this->logger->info("Saved " . count($issues) . " issues for repository ID {$repositoryId}");
    }

    public function savePullRequests(int $repositoryId, array $pullRequests): void
    {
        $sql = "
            INSERT INTO pull_requests (repository_id, number, title, state, body, author, created_at, updated_at, merged_at, closed_at)
            VALUES (:repository_id, :number, :title, :state, :body, :author, :created_at, :updated_at, :merged_at, :closed_at)
            ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            state = VALUES(state),
            body = VALUES(body),
            updated_at = VALUES(updated_at),
            merged_at = VALUES(merged_at),
            closed_at = VALUES(closed_at)
        ";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($pullRequests as $pr) {
            $stmt->execute([
                'repository_id' => $repositoryId,
                'number' => $pr['number'],
                'title' => $pr['title'],
                'state' => $pr['state'],
                'body' => $pr['body'],
                'author' => $pr['user']['login'],
                'created_at' => date('Y-m-d H:i:s', strtotime($pr['created_at'])),
                'updated_at' => date('Y-m-d H:i:s', strtotime($pr['updated_at'])),
                'merged_at' => $pr['merged_at'] ? date('Y-m-d H:i:s', strtotime($pr['merged_at'])) : null,
                'closed_at' => $pr['closed_at'] ? date('Y-m-d H:i:s', strtotime($pr['closed_at'])) : null
            ]);
        }
        
        $this->logger->info("Saved " . count($pullRequests) . " pull requests for repository ID {$repositoryId}");
    }

    public function saveReadme(int $repositoryId, string $content): void
    {
        $sql = "
            INSERT INTO readme_content (repository_id, content, last_updated)
            VALUES (:repository_id, :content, NOW())
            ON DUPLICATE KEY UPDATE
            content = VALUES(content),
            last_updated = VALUES(last_updated)
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'repository_id' => $repositoryId,
            'content' => $content
        ]);
        
        $this->logger->info("README saved for repository ID {$repositoryId}");
    }

    public function getRepositoryId(string $fullName): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM repositories WHERE full_name = ?');
        $stmt->execute([$fullName]);
        $result = $stmt->fetch();
        
        return $result ? $result['id'] : 0;
    }

    public function getAllRepositories(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM repositories ORDER BY stars DESC');
        return $stmt->fetchAll();
    }

    public function getRepositoryStats(): array
    {
        $sql = "
            SELECT 
                r.name,
                r.stars,
                r.forks,
                r.open_issues,
                COUNT(DISTINCT i.id) as total_issues,
                COUNT(DISTINCT pr.id) as total_prs,
                COUNT(DISTINCT c.id) as total_commits
            FROM repositories r
            LEFT JOIN issues i ON r.id = i.repository_id
            LEFT JOIN pull_requests pr ON r.id = pr.repository_id
            LEFT JOIN commits c ON r.id = c.repository_id
            GROUP BY r.id
            ORDER BY r.stars DESC
        ";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}