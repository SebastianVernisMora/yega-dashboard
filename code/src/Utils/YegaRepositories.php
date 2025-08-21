<?php

namespace Yega\GitHub\Utils;

class YegaRepositories
{
    /**
     * Lista de repositorios oficiales del ecosistema Yega
     */
    public const REPOSITORIES = [
        'yega-core' => [
            'name' => 'yega-core',
            'description' => 'Núcleo principal del ecosistema Yega',
            'type' => 'core',
            'priority' => 1
        ],
        'yega-dashboard' => [
            'name' => 'yega-dashboard',
            'description' => 'Dashboard y panel de control Yega',
            'type' => 'frontend',
            'priority' => 2
        ],
        'yega-api' => [
            'name' => 'yega-api',
            'description' => 'APIs y servicios backend Yega',
            'type' => 'backend',
            'priority' => 3
        ],
        'yega-frontend' => [
            'name' => 'yega-frontend',
            'description' => 'Aplicación frontend principal Yega',
            'type' => 'frontend',
            'priority' => 4
        ],
        'yega-mobile' => [
            'name' => 'yega-mobile',
            'description' => 'Aplicación móvil Yega',
            'type' => 'mobile',
            'priority' => 5
        ]
    ];
    
    /**
     * Obtiene lista de nombres de repositorios
     */
    public static function getNames(): array
    {
        return array_keys(self::REPOSITORIES);
    }
    
    /**
     * Obtiene repositorios por tipo
     */
    public static function getByType(string $type): array
    {
        return array_filter(self::REPOSITORIES, fn($repo) => $repo['type'] === $type);
    }
    
    /**
     * Verifica si un repositorio es válido para Yega
     */
    public static function isValid(string $repo): bool
    {
        return array_key_exists($repo, self::REPOSITORIES);
    }
    
    /**
     * Obtiene información de un repositorio
     */
    public static function getInfo(string $repo): ?array
    {
        return self::REPOSITORIES[$repo] ?? null;
    }
    
    /**
     * Obtiene repositorios ordenados por prioridad
     */
    public static function getByPriority(): array
    {
        $repos = self::REPOSITORIES;
        uasort($repos, fn($a, $b) => $a['priority'] <=> $b['priority']);
        return $repos;
    }
}