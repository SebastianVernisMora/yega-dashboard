<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Simulación de datos de repositorios del ecosistema Yega
$repositories = [
    [
        'name' => 'yega-core',
        'description' => 'Núcleo principal del ecosistema Yega con funcionalidades base',
        'language' => 'JavaScript',
        'stars' => 156,
        'forks' => 23,
        'open_issues' => 8,
        'size' => 2048,
        'url' => 'https://github.com/yega/yega-core',
        'updated_at' => '2025-08-15T10:30:00Z',
        'created_at' => '2024-01-15T08:00:00Z',
        'active' => true,
        'default_branch' => 'main',
        'license' => 'MIT'
    ],
    [
        'name' => 'yega-ui',
        'description' => 'Componentes de interfaz de usuario reutilizables para Yega',
        'language' => 'TypeScript',
        'stars' => 89,
        'forks' => 15,
        'open_issues' => 4,
        'size' => 1536,
        'url' => 'https://github.com/yega/yega-ui',
        'updated_at' => '2025-08-14T14:22:00Z',
        'created_at' => '2024-02-10T12:00:00Z',
        'active' => true,
        'default_branch' => 'main',
        'license' => 'MIT'
    ],
    [
        'name' => 'yega-api',
        'description' => 'API REST para servicios backend de Yega',
        'language' => 'Python',
        'stars' => 67,
        'forks' => 12,
        'open_issues' => 6,
        'size' => 1024,
        'url' => 'https://github.com/yega/yega-api',
        'updated_at' => '2025-08-13T09:15:00Z',
        'created_at' => '2024-03-05T16:30:00Z',
        'active' => true,
        'default_branch' => 'main',
        'license' => 'Apache-2.0'
    ],
    [
        'name' => 'yega-docs',
        'description' => 'Documentación oficial completa del ecosistema Yega',
        'language' => 'Markdown',
        'stars' => 34,
        'forks' => 8,
        'open_issues' => 2,
        'size' => 512,
        'url' => 'https://github.com/yega/yega-docs',
        'updated_at' => '2025-08-12T11:45:00Z',
        'created_at' => '2024-04-01T10:00:00Z',
        'active' => true,
        'default_branch' => 'main',
        'license' => 'CC-BY-4.0'
    ],
    [
        'name' => 'yega-cli',
        'description' => 'Herramienta de línea de comandos para automatizar tareas de Yega',
        'language' => 'Go',
        'stars' => 45,
        'forks' => 6,
        'open_issues' => 3,
        'size' => 768,
        'url' => 'https://github.com/yega/yega-cli',
        'updated_at' => '2025-08-11T16:20:00Z',
        'created_at' => '2024-05-20T14:15:00Z',
        'active' => true,
        'default_branch' => 'main',
        'license' => 'MIT'
    ]
];

try {
    $response = [
        'success' => true,
        'repositories' => $repositories,
        'total' => count($repositories),
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ]);
}
?>