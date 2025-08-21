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
    
    // Parámetros
    $days = isset($_GET['days']) ? min(365, max(7, (int)$_GET['days'])) : 30;
    $granularity = isset($_GET['granularity']) ? $_GET['granularity'] : 'day';
    
    // Validar granularidad
    if (!in_array($granularity, ['day', 'week', 'month'])) {
        $granularity = 'day';
    }
    
    // Configurar formato de fecha según granularidad
    $dateFormat = 'DATE';
    $dateColumn = 'date';
    
    switch ($granularity) {
        case 'week':
            $dateFormat = 'DATE_FORMAT(created_at, "%Y-%u")';
            $dateColumn = 'week';
            break;
        case 'month':
            $dateFormat = 'DATE_FORMAT(created_at, "%Y-%m")';
            $dateColumn = 'month';
            break;
    }
    
    $pdo = $db->getPdo();
    
    // Actividad de issues
    $issuesStmt = $pdo->prepare("
        SELECT 
            {$dateFormat}(created_at) as {$dateColumn},
            COUNT(*) as count,
            COUNT(CASE WHEN state = 'open' THEN 1 END) as open_count,
            COUNT(CASE WHEN state = 'closed' THEN 1 END) as closed_count
        FROM issues 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        GROUP BY {$dateFormat}(created_at)
        ORDER BY {$dateColumn} DESC
    ");
    $issuesStmt->execute(['days' => $days]);
    $issuesActivity = $issuesStmt->fetchAll();
    
    // Actividad de pull requests
    $prsStmt = $pdo->prepare("
        SELECT 
            {$dateFormat}(created_at) as {$dateColumn},
            COUNT(*) as count,
            COUNT(CASE WHEN state = 'open' THEN 1 END) as open_count,
            COUNT(CASE WHEN state = 'closed' THEN 1 END) as closed_count,
            COUNT(CASE WHEN merged = 1 THEN 1 END) as merged_count
        FROM pull_requests 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        GROUP BY {$dateFormat}(created_at)
        ORDER BY {$dateColumn} DESC
    ");
    $prsStmt->execute(['days' => $days]);
    $prsActivity = $prsStmt->fetchAll();
    
    // Actividad de commits
    $commitsStmt = $pdo->prepare("
        SELECT 
            {$dateFormat}(committed_at) as {$dateColumn},
            COUNT(*) as count,
            COUNT(DISTINCT author_email) as unique_authors
        FROM commits 
        WHERE committed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        GROUP BY {$dateFormat}(committed_at)
        ORDER BY {$dateColumn} DESC
    ");
    $commitsStmt->execute(['days' => $days]);
    $commitsActivity = $commitsStmt->fetchAll();
    
    // Actividad por repositorio
    $repoActivityStmt = $pdo->prepare("
        SELECT 
            r.name,
            r.full_name,
            COUNT(DISTINCT i.id) as issues_count,
            COUNT(DISTINCT pr.id) as prs_count,
            COUNT(DISTINCT c.sha) as commits_count,
            MAX(GREATEST(
                IFNULL(i.created_at, '1970-01-01'),
                IFNULL(pr.created_at, '1970-01-01'),
                IFNULL(c.committed_at, '1970-01-01')
            )) as last_activity
        FROM repositories r
        LEFT JOIN issues i ON r.id = i.repository_id 
            AND i.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        LEFT JOIN pull_requests pr ON r.id = pr.repository_id 
            AND pr.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        LEFT JOIN commits c ON r.id = c.repository_id 
            AND c.committed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        GROUP BY r.id
        HAVING (issues_count > 0 OR prs_count > 0 OR commits_count > 0)
        ORDER BY last_activity DESC
    ");
    $repoActivityStmt->execute(['days' => $days]);
    $repoActivity = $repoActivityStmt->fetchAll();
    
    // Autores más activos
    $authorsStmt = $pdo->prepare("
        SELECT 
            author_name,
            author_email,
            COUNT(*) as commits_count,
            COUNT(DISTINCT repository_id) as repos_count,
            MIN(committed_at) as first_commit,
            MAX(committed_at) as last_commit
        FROM commits 
        WHERE committed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        GROUP BY author_name, author_email
        ORDER BY commits_count DESC
        LIMIT 20
    ");
    $authorsStmt->execute(['days' => $days]);
    $topAuthors = $authorsStmt->fetchAll();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'period' => [
                'days' => $days,
                'granularity' => $granularity,
                'start_date' => date('Y-m-d', strtotime("-{$days} days")),
                'end_date' => date('Y-m-d')
            ],
            'issues' => $issuesActivity,
            'pull_requests' => $prsActivity,
            'commits' => $commitsActivity,
            'repository_activity' => $repoActivity,
            'top_authors' => $topAuthors
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
