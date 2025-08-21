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
    // Obtener nombre del repositorio de la URL
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    $pathParts = explode('/', trim($pathInfo, '/'));
    
    if (count($pathParts) < 2) {
        throw new Exception('Nombre de repositorio requerido');
    }
    
    $repoName = $pathParts[1];
    
    // Parámetros de paginación
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(10, (int)$_GET['per_page'])) : 50;
    $offset = ($page - 1) * $perPage;
    
    // Filtros
    $state = isset($_GET['state']) ? $_GET['state'] : 'all';
    $label = isset($_GET['label']) ? $_GET['label'] : null;
    
    $db = new Database();
    
    // Verificar que el repositorio existe
    $repo = $db->getRepositoryByName($repoName);
    if (!$repo) {
        throw new Exception('Repositorio no encontrado');
    }
    
    // Construir consulta
    $whereClauses = ['repository_id = :repository_id'];
    $params = ['repository_id' => $repo['id']];
    
    if ($state !== 'all') {
        $whereClauses[] = 'state = :state';
        $params['state'] = $state;
    }
    
    if ($label) {
        $whereClauses[] = 'JSON_CONTAINS(labels, JSON_QUOTE(:label))';
        $params['label'] = $label;
    }
    
    $whereClause = implode(' AND ', $whereClauses);
    
    // Contar total de issues
    $countSql = "SELECT COUNT(*) as total FROM issues WHERE {$whereClause}";
    $stmt = $db->getPdo()->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Obtener issues
    $sql = "
        SELECT *, 
               JSON_UNQUOTE(JSON_EXTRACT(labels, '$')) as labels_array
        FROM issues 
        WHERE {$whereClause}
        ORDER BY updated_at DESC 
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->getPdo()->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $issues = $stmt->fetchAll();
    
    // Procesar issues para decodificar JSON
    foreach ($issues as &$issue) {
        $issue['labels'] = json_decode($issue['labels'], true) ?: [];
        $issue['assignees'] = json_decode($issue['assignees'], true) ?: [];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $issues,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage)
        ],
        'repository' => [
            'name' => $repo['name'],
            'full_name' => $repo['full_name']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
