<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Datos de actividad para gráficos
$activity_data = [
    'labels' => ['yega-core', 'yega-ui', 'yega-api', 'yega-docs', 'yega-cli'],
    'commits' => [45, 32, 28, 15, 22],
    'issues' => [8, 4, 6, 2, 3],
    'prs' => [5, 3, 4, 1, 2]
];

// Datos de comparación para radar chart
$comparison_data = [
    'datasets' => [
        [
            'name' => 'yega-core',
            'values' => [90, 70, 80, 60, 85, 75] // Estrellas, Forks, Issues, PRs, Actividad, Tamaño
        ],
        [
            'name' => 'yega-ui',
            'values' => [60, 50, 40, 70, 65, 55]
        ],
        [
            'name' => 'yega-api',
            'values' => [70, 40, 60, 50, 70, 45]
        ],
        [
            'name' => 'yega-docs',
            'values' => [30, 25, 20, 15, 40, 25]
        ],
        [
            'name' => 'yega-cli',
            'values' => [45, 20, 30, 25, 50, 35]
        ]
    ]
];

// Datos de tendencias (30 días)
$trends_data = [
    'labels' => [],
    'commits' => [],
    'issues' => [],
    'prs' => []
];

// Generar datos para los últimos 30 días
for ($i = 29; $i >= 0; $i--) {
    $date = date('d/m', strtotime("-$i days"));
    $trends_data['labels'][] = $date;
    $trends_data['commits'][] = rand(5, 25);
    $trends_data['issues'][] = rand(1, 8);
    $trends_data['prs'][] = rand(1, 6);
}

// Distribución de issues
$issues_distribution = [
    'labels' => ['yega-core', 'yega-ui', 'yega-api', 'yega-docs', 'yega-cli'],
    'values' => [8, 4, 6, 2, 3]
];

// Issues vs PRs
$issues_prs_data = [
    'labels' => ['Issues Abiertas', 'Issues Cerradas', 'PRs Abiertas', 'PRs Fusionadas'],
    'values' => [23, 67, 8, 45]
];

// Top contribuidores
$contributors_data = [
    ['name' => 'Juan Pérez', 'commits' => 156, 'prs' => 23],
    ['name' => 'María García', 'commits' => 134, 'prs' => 19],
    ['name' => 'Carlos López', 'commits' => 98, 'prs' => 15],
    ['name' => 'Ana Martín', 'commits' => 87, 'prs' => 12],
    ['name' => 'Luis Torres', 'commits' => 76, 'prs' => 9]
];

try {
    $response = [
        'success' => true,
        'activity' => $activity_data,
        'comparison' => $comparison_data,
        'trends' => $trends_data,
        'distribution' => $issues_distribution,
        'data' => $issues_prs_data,
        'contributors' => $contributors_data,
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error cargando datos de gráficos',
        'error' => $e->getMessage()
    ]);
}
?>