<?php

// Cargar autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

// Configurar manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? 'false' === 'true');

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar OPTIONS para CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

use YegaDashboard\Controllers\DashboardController;

try {
    $controller = new DashboardController();
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Enrutamiento simple
    switch (true) {
        case $uri === '/api/overview' && $method === 'GET':
            $response = $controller->getOverviewStats();
            break;
            
        case preg_match('/^\/api\/repository\/(\d+)$/', $uri, $matches) && $method === 'GET':
            $response = $controller->getRepositoryDetails((int)$matches[1]);
            break;
            
        case $uri === '/api/repositories' && $method === 'GET':
            $response = $controller->getRepositoriesList();
            break;
            
        case $uri === '/api/sync/all' && $method === 'POST':
            $response = $controller->syncAllRepositories();
            break;
            
        case preg_match('/^\/api\/sync\/(\d+)$/', $uri, $matches) && $method === 'POST':
            $response = $controller->syncRepository((int)$matches[1]);
            break;
            
        case $uri === '/api/stats/languages' && $method === 'GET':
            $response = $controller->getLanguageStats();
            break;
            
        case $uri === '/api/stats/activity' && $method === 'GET':
            $days = (int)($_GET['days'] ?? 30);
            $response = $controller->getActivityTrends($days);
            break;
            
        default:
            if ($uri === '/' || $uri === '/index.html') {
                // Servir página principal
                header('Content-Type: text/html; charset=utf-8');
                readfile(__DIR__ . '/index.html');
                exit();
            }
            
            // Servir archivos estáticos
            $filePath = __DIR__ . $uri;
            if (file_exists($filePath) && is_file($filePath)) {
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $mimeTypes = [
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml'
                ];
                
                if (isset($mimeTypes[$extension])) {
                    header('Content-Type: ' . $mimeTypes[$extension]);
                }
                
                readfile($filePath);
                exit();
            }
            
            http_response_code(404);
            $response = ['error' => 'Endpoint no encontrado'];
            break;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $_ENV['APP_DEBUG'] === 'true' ? $e->getTraceAsString() : null
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
