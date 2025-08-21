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

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido. Use POST.'
    ]);
    exit();
}

require_once '../../src/Sync.php';

try {
    // Verificar token de GitHub
    $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
    if (!$githubToken) {
        throw new Exception('Token de GitHub no configurado en variables de entorno');
    }
    
    // Obtener parámetros del cuerpo de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    
    $force = isset($input['force']) ? (bool)$input['force'] : false;
    $owner = isset($input['owner']) ? $input['owner'] : 'yegamultimedia';
    $repositories = isset($input['repositories']) ? $input['repositories'] : null;
    
    // Inicializar sincronizador
    $sync = new Sync($githubToken, $owner);
    
    // Configurar timeout para evitar problemas con requests largos
    set_time_limit(300); // 5 minutos
    
    $startTime = microtime(true);
    $results = [];
    
    if ($repositories && is_array($repositories)) {
        // Sincronizar solo repositorios específicos
        foreach ($repositories as $repoName) {
            try {
                $sync->syncRepositoryData($repoName, $force);
                $results[$repoName] = 'success';
            } catch (Exception $e) {
                $results[$repoName] = 'error: ' . $e->getMessage();
            }
        }
        $message = 'Sincronización de repositorios específicos completada';
    } else {
        // Sincronización completa
        $sync->syncAll($force);
        $message = 'Sincronización completa realizada';
    }
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'duration' => $duration . ' segundos',
        'force' => $force,
        'owner' => $owner,
        'results' => $results ?: null,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
