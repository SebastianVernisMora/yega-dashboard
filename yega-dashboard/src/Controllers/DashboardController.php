<?php

namespace YegaDashboard\Controllers;

use YegaDashboard\Config\DatabaseConfig;
use YegaDashboard\Services\GitHubAPI;

class DashboardController
{
    private $db;
    private $githubAPI;
    private string $token = 'YOUR_GITHUB_TOKEN'; // Replace with actual token or configuration

    public function __construct()
    {
        $this->db = DatabaseConfig::getInstance()->getConnection();
        $this->githubAPI = new GitHubAPI($this->token);
    }

    public function getOverviewStats(): array
    {
        $stats = [];
        
        // Total de repositorios
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM repositories");
        $stats['total_repositories'] = $stmt->fetchColumn();
        
        // Total de issues abiertas
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM issues WHERE state = 'open'");
        $stats['open_issues'] = $stmt->fetchColumn();
        
        // Total de PRs abiertos
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM pull_requests WHERE state = 'open'");
        $stats['open_pull_requests'] = $stmt->fetchColumn();
        
        // Total de commits últimos 30 días
        $stmt = $this->db->query("
            SELECT COUNT(*) as total 
            FROM commits 
            WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['recent_commits'] = $stmt->fetchColumn();
        
        // Repositorio más activo (por commits)
        $stmt = $this->db->query("
            SELECT r.name, r.full_name, COUNT(c.id) as commit_count
            FROM repositories r
            LEFT JOIN commits c ON r.id = c.repo_id
            WHERE c.date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY r.id
            ORDER BY commit_count DESC
            LIMIT 1
        ");
        $stats['most_active_repo'] = $stmt->fetch();
        
        // Actividad por día (últimos 7 días)
        $stmt = $this->db->query("
            SELECT 
                DATE(date) as day,
                COUNT(*) as commits
            FROM commits 
            WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(date)
            ORDER BY day ASC
        ");
        $stats['daily_activity'] = $stmt->fetchAll();
        
        return $stats;
    }

    public function getRepositoryDetails(int $repoId): array
    {
        // Información básica del repositorio
        $stmt = $this->db->prepare("
            SELECT * FROM repositories WHERE id = ?
        ");
        $stmt->execute([$repoId]);
        $repo = $stmt->fetch();
        
        if (!$repo) {
            throw new \Exception('Repositorio no encontrado');
        }
        
        // Estadísticas del repositorio
        $stmt = $this->db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM issues WHERE repo_id = ? AND state = 'open') as open_issues,
                (SELECT COUNT(*) FROM issues WHERE repo_id = ? AND state = 'closed') as closed_issues,
                (SELECT COUNT(*) FROM pull_requests WHERE repo_id = ? AND state = 'open') as open_prs,
                (SELECT COUNT(*) FROM pull_requests WHERE repo_id = ? AND state = 'closed') as closed_prs,
                (SELECT COUNT(*) FROM commits WHERE repo_id = ?) as total_commits
        ");
        $stmt->execute([$repoId, $repoId, $repoId, $repoId, $repoId]);
        $stats = $stmt->fetch();
        
        // Últimos commits
        $stmt = $this->db->prepare("
            SELECT * FROM commits 
            WHERE repo_id = ?
            ORDER BY date DESC
            LIMIT 10
        ");
        $stmt->execute([$repoId]);
        $recentCommits = $stmt->fetchAll();
        
        // Issues recientes
        $stmt = $this->db->prepare("
            SELECT * FROM issues 
            WHERE repo_id = ?
            ORDER BY updated_at DESC
            LIMIT 10
        ");
        $stmt->execute([$repoId]);
        $recentIssues = $stmt->fetchAll();
        
        return [
            'repository' => $repo,
            'stats' => $stats,
            'recent_commits' => $recentCommits,
            'recent_issues' => $recentIssues
        ];
    }

    public function syncRepository(int $repoId): array
    {
        $stmt = $this->db->prepare("SELECT full_name FROM repositories WHERE id = ?");
        $stmt->execute([$repoId]);
        $fullName = $stmt->fetchColumn();
        
        if (!$fullName) {
            throw new \Exception('Repositorio no encontrado');
        }
        
        return $this->githubAPI->syncRepository($fullName);
    }

    public function syncAllRepositories(): array
    {
        return $this->githubAPI->syncAllRepositories();
    }

    public function getRepositoriesList(): array
    {
        $stmt = $this->db->query("
            SELECT 
                r.*,
                (SELECT COUNT(*) FROM issues WHERE repo_id = r.id AND state = 'open') as open_issues,
                (SELECT COUNT(*) FROM pull_requests WHERE repo_id = r.id AND state = 'open') as open_prs,
                (SELECT COUNT(*) FROM commits WHERE repo_id = r.id AND date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_commits
            FROM repositories r
            ORDER BY r.updated_at DESC
        ");
        
        return $stmt->fetchAll();
    }

    public function getLanguageStats(): array
    {
        $stmt = $this->db->query("
            SELECT 
                language,
                COUNT(*) as repo_count,
                SUM(stars) as total_stars
            FROM repositories 
            WHERE language IS NOT NULL
            GROUP BY language
            ORDER BY repo_count DESC
        ");
        
        return $stmt->fetchAll();
    }

    public function getActivityTrends(int $days = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(date) as day,
                COUNT(*) as commits,
                COUNT(DISTINCT repo_id) as active_repos
            FROM commits 
            WHERE date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(date)
            ORDER BY day ASC
        ");
        $stmt->execute([$days]);
        
        return $stmt->fetchAll();
    }
}
