<?php

namespace Yega\GitHub\Controllers;

use Yega\GitHub\Services\GitHubService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

class PullRequestController
{
    private GitHubService $githubService;
    private LoggerInterface $logger;

    public function __construct(GitHubService $githubService, LoggerInterface $logger)
    {
        $this->githubService = $githubService;
        $this->logger = $logger;
    }

    /**
     * Lista pull requests de un repositorio
     */
    private function validateRepository(string $repo): bool
    {
        try {
            $owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
            
            if (!in_array($repo, $this->githubService->getYegaRepositoryNames())) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Repositorio no válido para Yega'
                ], 404);
            }
            
            $params = [
                'state' => $request->query->get('state', 'all'),
                'page' => (int) $request->query->get('page', 1),
                'per_page' => min((int) $request->query->get('per_page', 30), 100),
                'sort' => $request->query->get('sort', 'created'),
                'direction' => $request->query->get('direction', 'desc')
            ];
            
            if ($request->query->has('base')) {
                $params['base'] = $request->query->get('base');
            }
            
            if ($request->query->has('head')) {
                $params['head'] = $request->query->get('head');
            }
            
            $pullRequests = $this->githubService->getPullRequests($owner, $repo, $params);
            
            return new JsonResponse([
                'success' => true,
                'data' => $pullRequests,
                'meta' => [
                    'page' => $params['page'],
                    'per_page' => $params['per_page'],
                    'count' => count($pullRequests),
                    'filters' => $params
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo pull requests', [
                'repo' => $repo,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error obteniendo pull requests: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene detalles de un pull request específico
     */
    public function show(Request $request, string $repo, int $prNumber): JsonResponse
    {
        try {
            $owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
            
            if (!in_array($repo, $this->githubService->getYegaRepositoryNames())) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Repositorio no válido para Yega'
                ], 404);
            }
            
            $pr = $this->githubService->makeRequest("repos/{$owner}/{$repo}/pulls/{$prNumber}");
            
            return new JsonResponse([
                'success' => true,
                'data' => $pr
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo pull request', [
                'repo' => $repo,
                'pr_number' => $prNumber,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error obteniendo pull request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene archivos modificados en un pull request
     */
    public function files(Request $request, string $repo, int $prNumber): JsonResponse
    {
        try {
            $owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
            
            if (!in_array($repo, $this->githubService->getYegaRepositoryNames())) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Repositorio no válido para Yega'
                ], 404);
            }
            
            $files = $this->githubService->makeRequest("repos/{$owner}/{$repo}/pulls/{$prNumber}/files");
            
            return new JsonResponse([
                'success' => true,
                'data' => $files,
                'meta' => [
                    'count' => count($files)
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo archivos del PR', [
                'repo' => $repo,
                'pr_number' => $prNumber,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error obteniendo archivos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene commits de un pull request
     */
    public function commits(Request $request, string $repo, int $prNumber): JsonResponse
    {
        try {
            $owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
            
            if (!in_array($repo, $this->githubService->getYegaRepositoryNames())) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Repositorio no válido para Yega'
                ], 404);
            }
            
            $commits = $this->githubService->makeRequest("repos/{$owner}/{$repo}/pulls/{$prNumber}/commits");
            
            return new JsonResponse([
                'success' => true,
                'data' => $commits,
                'meta' => [
                    'count' => count($commits)
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo commits del PR', [
                'repo' => $repo,
                'pr_number' => $prNumber,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error obteniendo commits: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene resumen de pull requests por estado
     */
    public function summary(Request $request, string $repo): JsonResponse
    {
        try {
            $owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
            
            if (!in_array($repo, $this->githubService->getYegaRepositoryNames())) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Repositorio no válido para Yega'
                ], 404);
            }
            
            $openPRs = $this->githubService->getPullRequests($owner, $repo, ['state' => 'open', 'per_page' => 1]);
            $closedPRs = $this->githubService->getPullRequests($owner, $repo, ['state' => 'closed', 'per_page' => 1]);
            
            return new JsonResponse([
                'success' => true,
                'data' => [
                    'open' => count($openPRs),
                    'closed' => count($closedPRs),
                    'total' => count($openPRs) + count($closedPRs)
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo resumen de PRs', [
                'repo' => $repo,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error obteniendo resumen: ' . $e->getMessage()
            ], 500);
        }
    }
}