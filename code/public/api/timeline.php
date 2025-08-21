<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$period = $_GET['period'] ?? '30d';
$type = $_GET['type'] ?? 'all';
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 20);

// Generar timeline de actividad simulada
$timeline_items = [
    [
        'type' => 'commit',
        'title' => 'Actualizar documentación de API',
        'description' => 'Mejoras en la documentación del endpoint de autenticación',
        'repository' => 'yega-api',
        'author' => 'Juan Pérez',
        'created_at' => '2025-08-17T14:30:00Z',
        'url' => 'https://github.com/yega/yega-api/commit/abc123'
    ],
    [
        'type' => 'issue',
        'title' => 'Bug en componente de navegación',
        'description' => 'El menú no se cierra correctamente en dispositivos móviles',
        'repository' => 'yega-ui',
        'author' => 'María García',
        'created_at' => '2025-08-17T12:15:00Z',
        'url' => 'https://github.com/yega/yega-ui/issues/42'
    ],
    [
        'type' => 'pull_request',
        'title' => 'Implementar autenticación JWT',
        'description' => 'Añadir sistema completo de autenticación con tokens JWT',
        'repository' => 'yega-core',
        'author' => 'Carlos López',
        'created_at' => '2025-08-17T10:45:00Z',
        'url' => 'https://github.com/yega/yega-core/pull/15'
    ],
    [
        'type' => 'release',
        'title' => 'Versión 1.2.0 publicada',
        'description' => 'Nueva versión con mejoras de rendimiento y corrección de bugs',
        'repository' => 'yega-core',
        'author' => 'Ana Martín',
        'created_at' => '2025-08-16T16:20:00Z',
        'url' => 'https://github.com/yega/yega-core/releases/tag/v1.2.0'
    ],
    [
        'type' => 'fork',
        'title' => 'Nuevo fork del proyecto',
        'description' => 'Desarrollador externo ha hecho fork del repositorio',
        'repository' => 'yega-cli',
        'author' => 'Contribuidor Externo',
        'created_at' => '2025-08-16T14:10:00Z',
        'url' => 'https://github.com/yega/yega-cli'
    ],
    [
        'type' => 'commit',
        'title' => 'Corregir validación de formularios',
        'description' => 'Mejorar sistema de validación en formularios de registro',
        'repository' => 'yega-ui',
        'author' => 'María García',
        'created_at' => '2025-08-16T11:30:00Z',
        'url' => 'https://github.com/yega/yega-ui/commit/def456'
    ],
    [
        'type' => 'issue',
        'title' => 'Optimizar consultas de base de datos',
        'description' => 'Las consultas actuales son lentas y necesitan optimización',
        'repository' => 'yega-api',
        'author' => 'Luis Torres',
        'created_at' => '2025-08-15T15:45:00Z',
        'url' => 'https://github.com/yega/yega-api/issues/28'
    ],
    [
        'type' => 'merge',
        'title' => 'Fusionar rama de características',
        'description' => 'Integrar nuevas características desarrolladas en sprint',
        'repository' => 'yega-core',
        'author' => 'Juan Pérez',
        'created_at' => '2025-08-15T13:20:00Z',
        'url' => 'https://github.com/yega/yega-core/commit/ghi789'
    ],
    [
        'type' => 'commit',
        'title' => 'Añadir tests unitarios',
        'description' => 'Mejorar cobertura de tests para módulo de autenticación',
        'repository' => 'yega-api',
        'author' => 'Carlos López',
        'created_at' => '2025-08-15T09:10:00Z',
        'url' => 'https://github.com/yega/yega-api/commit/jkl012'
    ],
    [
        'type' => 'pull_request',
        'title' => 'Mejorar accesibilidad',
        'description' => 'Implementar mejoras de accesibilidad en componentes UI',
        'repository' => 'yega-ui',
        'author' => 'Ana Martín',
        'created_at' => '2025-08-14T17:30:00Z',
        'url' => 'https://github.com/yega/yega-ui/pull/34'
    ],
    [
        'type' => 'commit',
        'title' => 'Actualizar documentación de instalación',
        'description' => 'Clarificar pasos de instalación y configuración inicial',
        'repository' => 'yega-docs',
        'author' => 'María García',
        'created_at' => '2025-08-14T14:15:00Z',
        'url' => 'https://github.com/yega/yega-docs/commit/mno345'
    ],
    [
        'type' => 'issue',
        'title' => 'Mejorar rendimiento en móviles',
        'description' => 'La aplicación es lenta en dispositivos móviles de gama baja',
        'repository' => 'yega-ui',
        'author' => 'Luis Torres',
        'created_at' => '2025-08-14T11:50:00Z',
        'url' => 'https://github.com/yega/yega-ui/issues/33'
    ]
];

// Filtrar por tipo si es necesario
if ($type !== 'all') {
    $timeline_items = array_filter($timeline_items, function($item) use ($type) {
        return $item['type'] === $type;
    });
}

// Simular paginación
$total_items = count($timeline_items);
$start_index = ($page - 1) * $limit;
$paginated_items = array_slice($timeline_items, $start_index, $limit);

try {
    $response = [
        'success' => true,
        'items' => $paginated_items,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total_items,
            'pages' => ceil($total_items / $limit)
        ],
        'filters' => [
            'period' => $period,
            'type' => $type
        ],
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error cargando timeline',
        'error' => $e->getMessage()
    ]);
}
?>