<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$repo = $_GET['repo'] ?? '';

// Simulación de issues por repositorio
$issues_data = [
    'yega-core' => [
        [
            'number' => 42,
            'title' => 'Mejorar rendimiento del módulo principal',
            'state' => 'open',
            'author' => 'María García',
            'created_at' => '2025-08-15T09:30:00Z',
            'updated_at' => '2025-08-16T14:20:00Z',
            'url' => 'https://github.com/yega/yega-core/issues/42',
            'labels' => ['enhancement', 'performance'],
            'assignees' => ['Juan Pérez'],
            'comments' => 3
        ],
        [
            'number' => 41,
            'title' => 'Bug en validación de entrada',
            'state' => 'open',
            'author' => 'Carlos López',
            'created_at' => '2025-08-14T16:45:00Z',
            'updated_at' => '2025-08-15T10:15:00Z',
            'url' => 'https://github.com/yega/yega-core/issues/41',
            'labels' => ['bug', 'priority-high'],
            'assignees' => ['María García'],
            'comments' => 7
        ]
    ],
    'yega-ui' => [
        [
            'number' => 18,
            'title' => 'Añadir componente de calendario',
            'state' => 'open',
            'author' => 'Ana Martín',
            'created_at' => '2025-08-13T11:20:00Z',
            'updated_at' => '2025-08-14T08:30:00Z',
            'url' => 'https://github.com/yega/yega-ui/issues/18',
            'labels' => ['feature', 'component'],
            'assignees' => [],
            'comments' => 2
        ]
    ],
    'yega-api' => [
        [
            'number' => 25,
            'title' => 'Optimizar consultas de base de datos',
            'state' => 'open',
            'author' => 'Luis Torres',
            'created_at' => '2025-08-12T14:10:00Z',
            'updated_at' => '2025-08-13T16:45:00Z',
            'url' => 'https://github.com/yega/yega-api/issues/25',
            'labels' => ['performance', 'database'],
            'assignees' => ['Carlos López'],
            'comments' => 5
        ]
    ]
];

try {
    $issues = $issues_data[$repo] ?? [];
    
    $response = [
        'success' => true,
        'issues' => $issues,
        'repository' => $repo,
        'total' => count($issues),
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error cargando issues',
        'error' => $e->getMessage()
    ]);
}
?>