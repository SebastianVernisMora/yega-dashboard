<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Estadísticas generales del ecosistema
$stats = [
    'total_repos' => 5,
    'total_issues' => 23,
    'total_prs' => 18,
    'total_contributors' => 12,
    'total_commits_last_month' => 142,
    'total_stars' => 391,
    'total_forks' => 64,
    'languages' => [
        'JavaScript' => 35,
        'TypeScript' => 25,
        'Python' => 20,
        'Go' => 10,
        'Markdown' => 10
    ],
    'activity_score' => 8.7,
    'health_score' => 9.2,
    'last_updated' => date('c')
];

// Métricas adicionales
$metrics = [
    'code_frequency' => [
        'additions_last_week' => 2847,
        'deletions_last_week' => 1256,
        'net_changes' => 1591
    ],
    'contributor_activity' => [
        'new_contributors_last_month' => 3,
        'active_contributors_last_week' => 8,
        'most_active_contributor' => 'Juan Pérez'
    ],
    'issue_metrics' => [
        'avg_close_time_days' => 4.2,
        'issues_opened_last_week' => 7,
        'issues_closed_last_week' => 9
    ],
    'pr_metrics' => [
        'avg_merge_time_hours' => 18.5,
        'prs_opened_last_week' => 5,
        'prs_merged_last_week' => 6
    ]
];

try {
    $response = [
        'success' => true,
        'stats' => $stats,
        'metrics' => $metrics,
        'generated_at' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error cargando estadísticas',
        'error' => $e->getMessage()
    ]);
}
?>