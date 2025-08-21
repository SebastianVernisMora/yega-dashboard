<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../vendor/autoload.php';

use Yega\Dashboard\Database;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

try {
    // Parse DATABASE_URL
    $databaseUrl = parse_url($_ENV['DATABASE_URL']);
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $databaseUrl['host'],
        $databaseUrl['port'] ?? 3306,
        ltrim($databaseUrl['path'], '/')
    );
    
    $database = new Database($dsn, $databaseUrl['user'], $databaseUrl['pass']);
    
    // Router simple
    $requestUri = $_SERVER['REQUEST_URI'];
    $path = parse_url($requestUri, PHP_URL_PATH);
    $path = str_replace('/api', '', $path);
    
    switch ($path) {
        case '/repositories':
            $repositories = $database->getAllRepositories();
            echo json_encode([
                'success' => true,
                'data' => $repositories
            ]);
            break;
            
        case '/stats/overview':
            $stats = $database->getRepositoryStats();
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        default:
            if (preg_match('/\/repository\/([^\/]+)\/([^\/]+)\/(.+)/', $path, $matches)) {
                $owner = $matches[1];
                $repoName = $matches[2];
                $action = $matches[3];
                
                $repositoryId = $database->getRepositoryId("{$owner}/{$repoName}");
                
                if (!$repositoryId) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'Repository not found']);
                    break;
                }
                
                switch ($action) {
                    case 'issues':
                        $pdo = new PDO($dsn, $databaseUrl['user'], $databaseUrl['pass']);
                        $stmt = $pdo->prepare('SELECT * FROM issues WHERE repository_id = ? ORDER BY created_at DESC');
                        $stmt->execute([$repositoryId]);
                        $issues = $stmt->fetchAll();
                        
                        echo json_encode([
                            'success' => true,
                            'data' => $issues
                        ]);
                        break;
                        
                    case 'pulls':
                        $pdo = new PDO($dsn, $databaseUrl['user'], $databaseUrl['pass']);
                        $stmt = $pdo->prepare('SELECT * FROM pull_requests WHERE repository_id = ? ORDER BY created_at DESC');
                        $stmt->execute([$repositoryId]);
                        $pulls = $stmt->fetchAll();
                        
                        echo json_encode([
                            'success' => true,
                            'data' => $pulls
                        ]);
                        break;
                        
                    case 'readme':
                        $pdo = new PDO($dsn, $databaseUrl['user'], $databaseUrl['pass']);
                        $stmt = $pdo->prepare('SELECT content FROM readme_content WHERE repository_id = ?');
                        $stmt->execute([$repositoryId]);
                        $readme = $stmt->fetch();
                        
                        echo json_encode([
                            'success' => true,
                            'data' => $readme ? $readme['content'] : null
                        ]);
                        break;
                        
                    default:
                        http_response_code(404);
                        echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}