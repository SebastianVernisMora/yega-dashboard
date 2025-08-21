<?php

namespace Yega\GitHub\Controllers;

use Yega\GitHub\Services\GitHubService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

class RepositoryController
{
    private GitHubService $githubService;
    private LoggerInterface $logger;

    public function __construct(GitHubService $githubService, LoggerInterface $logger)
    {
        $this->githubService = $githubService;
        $this->logger = $logger;
    }

    /**
     * Lista todos los repositorios Yega
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $repositories = $this->githubService->getYegaRepositories();
            
            return new JsonResponse([
                'success' => true,
                'data' => $repositories,
                'meta' => [
                    'total' => count(array_filter($repositories)),
                    'timestamp' => time()
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo repositorios', ['error' => $e->getMessage()]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error obteniendo repositorios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene información detallada de un repositorio
     */
    public function show(Request $request, string $repo): JsonResponse
    {
        try {
            $owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
            
            // Verificar que sea un repo Yega válido
            if (!in_array($repo, $this->githubService->getYegaRepositoryNames())) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Repositorio no válido para Yega'
                ], 404);
            }
            
            $repository = $this->githubService->getRepository($owner, $repo);
            
            return new JsonResponse([
                'success' => true,
                'data' => $repository
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo repositorio', [
                'repo' => $repo,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error obteniendo repositorio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas completas de un repositorio
     */
    public function stats(Request $request, string $repo): JsonResponse
    {
        try {
            $owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
            
            if (!in_array($repo, $this->githubService->getYegaRepositoryNames())) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Repositorio no válido para Yega'
                ], 404);
            }
            
            $stats = $this->githubService->getRepositoryStats($owner, $repo);
            
            return new JsonResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo estadísticas', [
                'repo' => $repo,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error obteniendo estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene commits recientes del repositorio
     */
    public function commits(Request $request, string $repo): JsonResponse
    {
        try {
            $owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
            $page = (int) $request->query->get('page', 1);
            $perPage = min((int) $request->query->get('per_page', 30), 100);
            
            if (!in_array($repo, $this->githubService->getYegaRepositoryNames())) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Repositorio no válido para Yega'
                ], 404);
            }
            
            $commits = $this->githubService->getCommits($owner, $repo, [
                'page' => $page,
                'per_page' => $perPage
            ]);
            
            return new JsonResponse([
                'success' => true,
                'data' => $commits,
                'meta' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'count' => count($commits)
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo commits', [
                'repo' => $repo,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error obteniendo commits: ' . $e->getMessage()
            ], 500);
        }
    }
}