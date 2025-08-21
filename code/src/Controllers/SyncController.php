<?php

namespace Yega\GitHub\Controllers;

use Yega\GitHub\Services\GitHubService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Predis\Client as RedisClient;

class SyncController
{
    private GitHubService $githubService;
    private LoggerInterface $logger;
    private RedisClient $redis;
    private const SYNC_STATUS_KEY = 'yega_sync_status';
    private const SYNC_LOCK_KEY = 'yega_sync_lock';
    private const SYNC_HISTORY_KEY = 'yega_sync_history';

    public function __construct(
        GitHubService $githubService,
        LoggerInterface $logger,
        RedisClient $redis
    ) {
        $this->githubService = $githubService;
        $this->logger = $logger;
        $this->redis = $redis;
    }

    /**
     * Ejecuta sincronización completa de todos los repos Yega
     */
    public function syncAll(Request $request): JsonResponse
    {
        $lockKey = self::SYNC_LOCK_KEY . ':all';
        
        // Verificar si ya hay una sincronización en progreso
        if ($this->redis->exists($lockKey)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Sincronización ya en progreso',
                'status' => $this->getSyncStatus()
            ], 409);
        }
        
        try {
            // Establecer lock por 30 minutos
            $this->redis->setex($lockKey, 1800, time());
            
            $this->logger->info('Iniciando sincronización completa de repos Yega');
            
            $results = [];
            $repositories = $this->githubService->getYegaRepositoryNames();
            $owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
            
            foreach ($repositories as $repo) {
                $results[$repo] = $this->syncRepository($owner, $repo);
            }
            
            $syncData = [
                'timestamp' => time(),
                'type' => 'full',
                'repositories' => $results,
                'success_count' => count(array_filter($results, fn($r) => $r['success'])),
                'total_count' => count($results)
            ];
            
            // Guardar historial de sincronización
            $this->saveSyncHistory($syncData);
            
            // Limpiar lock
            $this->redis->del($lockKey);
            
            $this->logger->info('Sincronización completa finalizada', $syncData);
            
            return new JsonResponse([
                'success' => true,
                'data' => $syncData
            ]);
            
        } catch (\Exception $e) {
            // Limpiar lock en caso de error
            $this->redis->del($lockKey);
            
            $this->logger->error('Error en sincronización completa', [
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error en sincronización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincroniza un repositorio específico
     */
    public function syncRepository(string $owner, string $repo): array
    {
        $startTime = microtime(true);
        
        try {
            $this->logger->info("Sincronizando repositorio {$repo}");
            
            // Obtener datos del repositorio
            $repoData = $this->githubService->getRepository($owner, $repo);
            $stats = $this->githubService->getRepositoryStats($owner, $repo);
            
            // Obtener issues recientes
            $issues = $this->githubService->getIssues($owner, $repo, [
                'state' => 'all',
                'per_page' => 50,
                'sort' => 'updated'
            ]);
            
            // Obtener PRs recientes
            $pullRequests = $this->githubService->getPullRequests($owner, $repo, [
                'state' => 'all', 
                'per_page' => 50,
                'sort' => 'updated'
            ]);
            
            // Obtener commits recientes
            $commits = $this->githubService->getCommits($owner, $repo, [
                'per_page' => 20
            ]);
            
            $syncResult = [
                'success' => true,
                'repository' => $repoData,
                'stats' => $stats,
                'issues_count' => count($issues),
                'prs_count' => count($pullRequests),
                'commits_count' => count($commits),
                'duration' => round(microtime(true) - $startTime, 2),
                'timestamp' => time()
            ];
            
            // Cachear resultado
            $this->redis->setex(
                "sync_result:{$owner}:{$repo}",
                3600, // 1 hora
                json_encode($syncResult)
            );
            
            return $syncResult;
            
        } catch (\Exception $e) {
            $this->logger->error("Error sincronizando {$repo}", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2),
                'timestamp' => time()
            ];
        }
    }

    /**
     * Sincronización incremental (solo cambios recientes)
     */
    public function syncIncremental(Request $request): JsonResponse
    {
        try {
            $since = $request->query->get('since');
            if (!$since) {
                // Por defecto, últimas 24 horas
                $since = date('Y-m-d\TH:i:s\Z', time() - 86400);
            }
            
            $this->logger->info('Iniciando sincronización incremental', ['since' => $since]);
            
            $results = [];
            $repositories = $this->githubService->getYegaRepositoryNames();
            $owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
            
            foreach ($repositories as $repo) {
                $results[$repo] = $this->syncIncrementalRepository($owner, $repo, $since);
            }
            
            $syncData = [
                'timestamp' => time(),
                'type' => 'incremental',
                'since' => $since,
                'repositories' => $results,
                'success_count' => count(array_filter($results, fn($r) => $r['success'])),
                'total_count' => count($results)
            ];
            
            $this->saveSyncHistory($syncData);
            
            return new JsonResponse([
                'success' => true,
                'data' => $syncData
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error en sincronización incremental', [
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error en sincronización incremental: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sincronización incremental de un repositorio
     */
    private function syncIncrementalRepository(string $owner, string $repo, string $since): array
    {
        $startTime = microtime(true);
        
        try {
            // Solo obtener elementos actualizados desde la fecha especificada
            $issues = $this->githubService->getIssues($owner, $repo, [
                'state' => 'all',
                'since' => $since,
                'per_page' => 100
            ]);
            
            $pullRequests = $this->githubService->getPullRequests($owner, $repo, [
                'state' => 'all',
                'sort' => 'updated',
                'per_page' => 100
            ]);
            
            // Filtrar PRs por fecha de actualización
            $pullRequests = array_filter($pullRequests, function($pr) use ($since) {
                return strtotime($pr['updated_at']) >= strtotime($since);
            });
            
            return [
                'success' => true,
                'issues_updated' => count($issues),
                'prs_updated' => count($pullRequests),
                'duration' => round(microtime(true) - $startTime, 2),
                'timestamp' => time()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2),
                'timestamp' => time()
            ];
        }
    }

    /**
     * Obtiene el estado actual de sincronización
     */
    public function status(Request $request): JsonResponse
    {
        $status = $this->getSyncStatus();
        
        return new JsonResponse([
            'success' => true,
            'data' => $status
        ]);
    }

    /**
     * Obtiene historial de sincronizaciones
     */
    public function history(Request $request): JsonResponse
    {
        $limit = min((int) $request->query->get('limit', 10), 50);
        
        $history = $this->redis->lrange(self::SYNC_HISTORY_KEY, 0, $limit - 1);
        $history = array_map(fn($item) => json_decode($item, true), $history);
        
        return new JsonResponse([
            'success' => true,
            'data' => $history,
            'meta' => [
                'count' => count($history),
                'limit' => $limit
            ]
        ]);
    }

    /**
     * Programa sincronización automática
     */
    public function schedule(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $interval = $data['interval'] ?? 3600; // 1 hora por defecto
        $type = $data['type'] ?? 'incremental';
        
        if (!in_array($type, ['full', 'incremental'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Tipo de sincronización inválido'
            ], 400);
        }
        
        $scheduleData = [
            'type' => $type,
            'interval' => $interval,
            'next_run' => time() + $interval,
            'enabled' => true,
            'created_at' => time()
        ];
        
        $this->redis->set('sync_schedule', json_encode($scheduleData));
        
        $this->logger->info('Sincronización programada', $scheduleData);
        
        return new JsonResponse([
            'success' => true,
            'data' => $scheduleData
        ]);
    }

    /**
     * Obtiene estado actual de sincronización
     */
    private function getSyncStatus(): array
    {
        $locks = [
            'full' => $this->redis->exists(self::SYNC_LOCK_KEY . ':all'),
            'repository' => $this->redis->keys(self::SYNC_LOCK_KEY . ':repo:*')
        ];
        
        $lastSync = $this->redis->lindex(self::SYNC_HISTORY_KEY, 0);
        $lastSync = $lastSync ? json_decode($lastSync, true) : null;
        
        $schedule = $this->redis->get('sync_schedule');
        $schedule = $schedule ? json_decode($schedule, true) : null;
        
        return [
            'is_syncing' => $locks['full'] || !empty($locks['repository']),
            'locks' => $locks,
            'last_sync' => $lastSync,
            'schedule' => $schedule,
            'timestamp' => time()
        ];
    }

    /**
     * Guarda historial de sincronización
     */
    private function saveSyncHistory(array $syncData): void
    {
        $this->redis->lpush(self::SYNC_HISTORY_KEY, json_encode($syncData));
        $this->redis->ltrim(self::SYNC_HISTORY_KEY, 0, 99); // Mantener últimos 100
    }
}