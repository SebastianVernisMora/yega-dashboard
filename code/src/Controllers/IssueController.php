<?php

namespace Yega\GitHub\Controllers;

use Yega\GitHub\Services\GitHubService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

class IssueController
{
    private GitHubService $githubService;
    private LoggerInterface $logger;

    public function __construct(GitHubService $githubService, LoggerInterface $logger)
    {
        $this->githubService = $githubService;
        $this->logger = $logger;
    }

    /**
     * Lista issues de un repositorio con filtros
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
            
            if ($request->query->has('labels')) {
                $params['labels'] = $request->query->get('labels');
            }
            
            if ($request->query->has('assignee')) {
                $params['assignee'] = $request->query->get('assignee');
            }
            
            $issues = $this->githubService->getIssues($owner, $repo, $params);
            
            return new JsonResponse([
                'success' => true,
                'data' => $issues,
                'meta' => [
                    'page' => $params['page'],
                    'per_page' => $params['per_page'],
                    'count' => count($issues),
                    'filters' => $params
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo issues', [
                'repo' => $repo,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error obteniendo issues: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crea un nuevo issue
     */
    public function create(Request $request, string $repo): JsonResponse
    {
        try {
            $owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
            
            if (!in_array($repo, $this->githubService->getYegaRepositoryNames())) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Repositorio no válido para Yega'
                ], 404);
            }
            
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['title']) || empty($data['title'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Título del issue es requerido'
                ], 400);
            }
            
            $issueData = [
                'title' => $data['title'],
                'body' => $data['body'] ?? '',
            ];
            
            if (isset($data['labels']) && is_array($data['labels'])) {
                $issueData['labels'] = $data['labels'];
            }
            
            if (isset($data['assignees']) && is_array($data['assignees'])) {
                $issueData['assignees'] = $data['assignees'];
            }
            
            $issue = $this->githubService->createIssue($owner, $repo, $issueData);
            
            $this->logger->info('Issue creado exitosamente', [
                'repo' => $repo,
                'issue_number' => $issue['number'],
                'title' => $issue['title']
            ]);
            
            return new JsonResponse([
                'success' => true,
                'data' => $issue
            ], 201);
            
        } catch (\Exception $e) {
            $this->logger->error('Error creando issue', [
                'repo' => $repo,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error creando issue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza un issue existente
     */
    public function update(Request $request, string $repo, int $issueNumber): JsonResponse
    {
        try {
            $owner = $_ENV['GITHUB_OWNER'] ?? 'yega';
            
            if (!in_array($repo, $this->githubService->getYegaRepositoryNames())) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Repositorio no válido para Yega'
                ], 404);
            }
            
            $data = json_decode($request->getContent(), true);
            
            $issueData = [];
            
            if (isset($data['title'])) {
                $issueData['title'] = $data['title'];
            }
            
            if (isset($data['body'])) {
                $issueData['body'] = $data['body'];
            }
            
            if (isset($data['state'])) {
                $issueData['state'] = $data['state'];
            }
            
            if (isset($data['labels'])) {
                $issueData['labels'] = $data['labels'];
            }
            
            if (isset($data['assignees'])) {
                $issueData['assignees'] = $data['assignees'];
            }
            
            $issue = $this->githubService->updateIssue($owner, $repo, $issueNumber, $issueData);
            
            $this->logger->info('Issue actualizado exitosamente', [
                'repo' => $repo,
                'issue_number' => $issueNumber
            ]);
            
            return new JsonResponse([
                'success' => true,
                'data' => $issue
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error actualizando issue', [
                'repo' => $repo,
                'issue_number' => $issueNumber,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error actualizando issue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene resumen de issues por estado
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
            
            $openIssues = $this->githubService->getIssues($owner, $repo, ['state' => 'open', 'per_page' => 1]);
            $closedIssues = $this->githubService->getIssues($owner, $repo, ['state' => 'closed', 'per_page' => 1]);
            
            return new JsonResponse([
                'success' => true,
                'data' => [
                    'open' => count($openIssues),
                    'closed' => count($closedIssues),
                    'total' => count($openIssues) + count($closedIssues)
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo resumen de issues', [
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