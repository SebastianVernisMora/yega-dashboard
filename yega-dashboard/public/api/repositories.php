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

require_once '../../src/Database.php';

try {
    $db = new Database();
    $repositories = $db->getAllRepositories();
    
    // Agregar estadísticas adicionales a cada repositorio
    foreach ($repositories as &$repo) {
        // Contar issues abiertas
        $stmt = $db->getPdo()->prepare("
            SELECT COUNT(*) as count 
            FROM issues 
            WHERE repository_id = ? AND state = 'open'
        ");
        $stmt->execute([$repo['id']]);
        $repo['open_issues'] = $stmt->fetch()['count'];
        
        // Contar PRs abiertas
        $stmt = $db->getPdo()->prepare("
            SELECT COUNT(*) as count 
            FROM pull_requests 
            WHERE repository_id = ? AND state = 'open'
        ");
        $stmt->execute([$repo['id']]);
        $repo['open_pull_requests'] = $stmt->fetch()['count'];
        
        // Último commit
        $stmt = $db->getPdo()->prepare("
            SELECT committed_at 
            FROM commits 
            WHERE repository_id = ? 
            ORDER BY committed_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$repo['id']]);
        $lastCommit = $stmt->fetch();
        $repo['last_commit_at'] = $lastCommit ? $lastCommit['committed_at'] : null;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $repositories,
        'count' => count($repositories)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
