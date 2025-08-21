<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$repo = $_GET['repo'] ?? '';

// Simulación de pull requests por repositorio
$prs_data = [
    'yega-core' => [
        [
            'number' => 67,
            'title' => 'Implementar cache de resultados',
            'state' => 'open',
            'author' => 'Juan Pérez',
            'created_at' => '2025-08-16T10:30:00Z',
            'updated_at' => '2025-08-17T09:15:00Z',
            'url' => 'https://github.com/yega/yega-core/pull/67',
            'base' => 'main',
            'head' => 'feature/cache-implementation',
            'mergeable' => true,
            'additions' => 156,
            'deletions' => 23,
            'changed_files' => 8
        ],
        [
            'number' => 66,
            'title' => 'Actualizar dependencias',
            'state' => 'merged',
            'author' => 'María García',
            'created_at' => '2025-08-15T14:20:00Z',
            'updated_at' => '2025-08-16T11:45:00Z',
            'merged_at' => '2025-08-16T11:45:00Z',
            'url' => 'https://github.com/yega/yega-core/pull/66',
            'base' => 'main',
            'head' => 'chore/update-deps',
            'mergeable' => null,
            'additions' => 45,
            'deletions' => 67,
            'changed_files' => 3
        ]
    ],
    'yega-ui' => [
        [
            'number' => 34,
            'title' => 'Mejorar accesibilidad de componentes',
            'state' => 'open',
            'author' => 'Ana Martín',
            'created_at' => '2025-08-14T16:10:00Z',
            'updated_at' => '2025-08-15T13:30:00Z',
            'url' => 'https://github.com/yega/yega-ui/pull/34',
            'base' => 'main',
            'head' => 'feature/accessibility-improvements',
            'mergeable' => true,
            'additions' => 89,
            'deletions' => 12,
            'changed_files' => 15
        ]
    ],
    'yega-api' => [
        [
            'number' => 42,
            'title' => 'Añadir autenticación JWT',
            'state' => 'open',
            'author' => 'Carlos López',
            'created_at' => '2025-08-13T09:45:00Z',
            'updated_at' => '2025-08-16T15:20:00Z',
            'url' => 'https://github.com/yega/yega-api/pull/42',
            'base' => 'main',
            'head' => 'feature/jwt-auth',
            'mergeable' => false,
            'additions' => 234,
            'deletions' => 45,
            'changed_files' => 12
        ]
    ]
];

try {
    $prs = $prs_data[$repo] ?? [];
    
    $response = [
        'success' => true,
        'prs' => $prs,
        'repository' => $repo,
        'total' => count($prs),
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error cargando pull requests',
        'error' => $e->getMessage()
    ]);
}
?>