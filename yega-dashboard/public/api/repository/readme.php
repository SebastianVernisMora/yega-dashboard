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
require_once '../../../src/GitHubAPI.php';

try {
    // Obtener nombre del repositorio de la URL
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    $pathParts = explode('/', trim($pathInfo, '/'));
    
    if (count($pathParts) < 2) {
        throw new Exception('Nombre de repositorio requerido');
    }
    
    $repoName = $pathParts[1];
    
    $db = new Database();
    
    // Verificar que el repositorio existe
    $repo = $db->getRepositoryByName($repoName);
    if (!$repo) {
        throw new Exception('Repositorio no encontrado');
    }
    
    // Verificar si tenemos token de GitHub configurado
    $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
    if (!$githubToken) {
        throw new Exception('Token de GitHub no configurado');
    }
    
    $github = new GitHubAPI($githubToken);
    
    // Obtener README desde GitHub
    $owner = $repo['owner_login'];
    $readme = $github->getReadme($owner, $repoName);
    
    // Determinar tipo de contenido
    $contentType = 'text';
    if (isset($readme['name'])) {
        $extension = strtolower(pathinfo($readme['name'], PATHINFO_EXTENSION));
        if (in_array($extension, ['md', 'markdown'])) {
            $contentType = 'markdown';
        } elseif (in_array($extension, ['html', 'htm'])) {
            $contentType = 'html';
        } elseif (in_array($extension, ['rst'])) {
            $contentType = 'rst';
        }
    }
    
    $response = [
        'success' => true,
        'data' => [
            'name' => $readme['name'] ?? 'README',
            'path' => $readme['path'] ?? '',
            'size' => $readme['size'] ?? 0,
            'content' => $readme['decoded_content'] ?? '',
            'content_type' => $contentType,
            'encoding' => $readme['encoding'] ?? 'base64',
            'download_url' => $readme['download_url'] ?? null,
            'html_url' => $readme['html_url'] ?? null,
            'sha' => $readme['sha'] ?? null
        ],
        'repository' => [
            'name' => $repo['name'],
            'full_name' => $repo['full_name']
        ]
    ];
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
