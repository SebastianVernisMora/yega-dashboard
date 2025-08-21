<?php

// Configurar CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../../src/Database.php';

try {
    $db = new Database();
    $stats = $db->getOverviewStats();
    
    // Obtener información adicional
    $pdo = $db->getPdo();
    
    // Lenguajes más utilizados
    $languagesStmt = $pdo->query("
        SELECT language, COUNT(*) as count, SUM(stars_count) as total_stars
        FROM repositories 
        WHERE language IS NOT NULL 
        GROUP BY language 
        ORDER BY count DESC 
        LIMIT 10
    ");
    $languages = $languagesStmt->fetchAll();
    
    // Actividad reciente (7 días)
    $recentActivityStmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as issues
        FROM issues 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $recentIssues = $recentActivityStmt->fetchAll();
    
    $recentPRsStmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as pull_requests
        FROM pull_requests 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $recentPRs = $recentPRsStmt->fetchAll();
    
    $recentCommitsStmt = $pdo->query("
        SELECT 
            DATE(committed_at) as date,
            COUNT(*) as commits
        FROM commits 
        WHERE committed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(committed_at)
        ORDER BY date DESC
    ");
    $recentCommits = $recentCommitsStmt->fetchAll();
    
    // Top contribuidores
    $contributorsStmt = $pdo->query("
        SELECT 
            author_name,
            author_email,
            COUNT(*) as commit_count
        FROM commits 
        WHERE authored_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY author_name, author_email
        ORDER BY commit_count DESC
        LIMIT 10
    ");
    $topContributors = $contributorsStmt->fetchAll();
    
    // Repositorios más activos
    $activeReposStmt = $pdo->query("
        SELECT 
            r.name,
            r.full_name,
            r.stars_count,
            r.forks_count,
            COUNT(c.sha) as recent_commits
        FROM repositories r
        LEFT JOIN commits c ON r.id = c.repository_id 
            AND c.committed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY r.id
        ORDER BY recent_commits DESC, r.stars_count DESC
        LIMIT 10
    ");
    $activeRepos = $activeReposStmt->fetchAll();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'overview' => $stats,
            'languages' => $languages,
            'recent_activity' => [
                'issues' => $recentIssues,
                'pull_requests' => $recentPRs,
                'commits' => $recentCommits
            ],
            'top_contributors' => $topContributors,
            'active_repositories' => $activeRepos
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
