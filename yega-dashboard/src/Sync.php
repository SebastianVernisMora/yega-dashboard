<?php

require_once 'GitHubAPI.php';
require_once 'Database.php';

class Sync
{
    private $github;
    private $db;
    private $owner;
    
    public function __construct($githubToken, $owner = 'yegamultimedia')
    {
        $this->github = new GitHubAPI($githubToken);
        $this->db = new Database();
        $this->owner = $owner;
    }
    
    /**
     * Sincronización completa
     */
    public function syncAll($force = false)
    {
        $this->log('sync_all', 'started', 'Iniciando sincronización completa');
        
        try {
            // Sincronizar repositorios
            $repos = $this->syncRepositories($force);
            
            // Sincronizar issues, PRs y commits para cada repositorio
            foreach ($repos as $repo) {
                $this->syncRepositoryData($repo['name'], $force);
            }
            
            $this->log('sync_all', 'completed', 'Sincronización completa finalizada');
            return true;
        } catch (Exception $e) {
            $this->log('sync_all', 'error', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Sincronizar repositorios
     */
    public function syncRepositories($force = false)
    {
        $this->log('sync_repositories', 'started', 'Iniciando sincronización de repositorios');
        
        try {
            $repos = [];
            $page = 1;
            
            do {
                $response = $this->github->getRepositories($this->owner, 'all', $page);
                
                if (empty($response)) {
                    break;
                }
                
                foreach ($response as $repoData) {
                    $this->saveRepository($repoData);
                    $repos[] = $repoData;
                }
                
                $page++;
            } while (count($response) === 100); // Continuar si hay más páginas
            
            $this->log('sync_repositories', 'completed', 'Sincronizados ' . count($repos) . ' repositorios');
            return $repos;
        } catch (Exception $e) {
            $this->log('sync_repositories', 'error', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Sincronizar datos de un repositorio específico
     */
    public function syncRepositoryData($repoName, $force = false)
    {
        $this->log('sync_repository_data', 'started', "Sincronizando datos de {$repoName}");
        
        try {
            $repo = $this->db->getRepositoryByName($repoName);
            if (!$repo) {
                throw new Exception("Repositorio {$repoName} no encontrado en la base de datos");
            }
            
            // Determinar fecha de última sincronización para actualización incremental
            $since = null;
            if (!$force && $repo['synced_at']) {
                $since = date('c', strtotime($repo['synced_at'] . ' -1 day')); // 1 día de margen
            }
            
            // Sincronizar issues
            $this->syncIssues($repoName, $repo['id'], $since);
            
            // Sincronizar pull requests
            $this->syncPullRequests($repoName, $repo['id'], $since);
            
            // Sincronizar commits
            $this->syncCommits($repoName, $repo['id'], $since);
            
            $this->log('sync_repository_data', 'completed', "Datos de {$repoName} sincronizados");
            return true;
        } catch (Exception $e) {
            $this->log('sync_repository_data', 'error', $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Sincronizar issues de un repositorio
     */
    private function syncIssues($repoName, $repositoryId, $since = null)
    {
        try {
            $issues = [];
            $page = 1;
            
            do {
                $response = $this->github->getIssues($this->owner, $repoName, 'all', $page);
                
                if (empty($response)) {
                    break;
                }
                
                foreach ($response as $issueData) {
                    // Filtrar pull requests (GitHub los incluye en issues)
                    if (isset($issueData['pull_request'])) {
                        continue;
                    }
                    
                    // Si tenemos fecha de corte y el issue no es más reciente, parar
                    if ($since && strtotime($issueData['updated_at']) < strtotime($since)) {
                        break 2;
                    }
                    
                    $this->saveIssue($issueData, $repositoryId);
                    $issues[] = $issueData;
                }
                
                $page++;
            } while (count($response) === 100);
            
            $this->log('sync_issues', 'completed', "Sincronizados " . count($issues) . " issues de {$repoName}");
            
        } catch (Exception $e) {
            $this->log('sync_issues', 'error', "Error sincronizando issues de {$repoName}: " . $e->getMessage());
        }
    }
    
    /**
     * Sincronizar pull requests de un repositorio
     */
    private function syncPullRequests($repoName, $repositoryId, $since = null)
    {
        try {
            $prs = [];
            $page = 1;
            
            do {
                $response = $this->github->getPullRequests($this->owner, $repoName, 'all', $page);
                
                if (empty($response)) {
                    break;
                }
                
                foreach ($response as $prData) {
                    // Si tenemos fecha de corte y el PR no es más reciente, parar
                    if ($since && strtotime($prData['updated_at']) < strtotime($since)) {
                        break 2;
                    }
                    
                    $this->savePullRequest($prData, $repositoryId);
                    $prs[] = $prData;
                }
                
                $page++;
            } while (count($response) === 100);
            
            $this->log('sync_pull_requests', 'completed', "Sincronizados " . count($prs) . " pull requests de {$repoName}");
            
        } catch (Exception $e) {
            $this->log('sync_pull_requests', 'error', "Error sincronizando PRs de {$repoName}: " . $e->getMessage());
        }
    }
    
    /**
     * Sincronizar commits de un repositorio
     */
    private function syncCommits($repoName, $repositoryId, $since = null)
    {
        try {
            $commits = [];
            $page = 1;
            
            do {
                $response = $this->github->getCommits($this->owner, $repoName, $since, $page);
                
                if (empty($response)) {
                    break;
                }
                
                foreach ($response as $commitData) {
                    $this->saveCommit($commitData, $repositoryId);
                    $commits[] = $commitData;
                }
                
                $page++;
            } while (count($response) === 100);
            
            $this->log('sync_commits', 'completed', "Sincronizados " . count($commits) . " commits de {$repoName}");
            
        } catch (Exception $e) {
            $this->log('sync_commits', 'error', "Error sincronizando commits de {$repoName}: " . $e->getMessage());
        }
    }
    
    /**
     * Guardar repositorio en base de datos
     */
    private function saveRepository($repoData)
    {
        $data = [
            'github_id' => $repoData['id'],
            'name' => $repoData['name'],
            'full_name' => $repoData['full_name'],
            'description' => $repoData['description'],
            'html_url' => $repoData['html_url'],
            'clone_url' => $repoData['clone_url'],
            'language' => $repoData['language'],
            'stars_count' => $repoData['stargazers_count'],
            'forks_count' => $repoData['forks_count'],
            'open_issues_count' => $repoData['open_issues_count'],
            'size' => $repoData['size'],
            'default_branch' => $repoData['default_branch'],
            'visibility' => $repoData['visibility'],
            'created_at' => $repoData['created_at'],
            'updated_at' => $repoData['updated_at'],
            'pushed_at' => $repoData['pushed_at'],
            'owner_login' => $repoData['owner']['login'],
            'owner_type' => $repoData['owner']['type']
        ];
        
        return $this->db->upsertRepository($data);
    }
    
    /**
     * Guardar issue en base de datos
     */
    private function saveIssue($issueData, $repositoryId)
    {
        $data = [
            'github_id' => $issueData['id'],
            'repository_id' => $repositoryId,
            'number' => $issueData['number'],
            'title' => $issueData['title'],
            'body' => $issueData['body'],
            'state' => $issueData['state'],
            'html_url' => $issueData['html_url'],
            'author_login' => $issueData['user']['login'],
            'author_avatar_url' => $issueData['user']['avatar_url'],
            'labels' => json_encode(array_column($issueData['labels'], 'name')),
            'assignees' => json_encode(array_column($issueData['assignees'], 'login')),
            'created_at' => $issueData['created_at'],
            'updated_at' => $issueData['updated_at'],
            'closed_at' => $issueData['closed_at']
        ];
        
        return $this->db->upsertIssue($data);
    }
    
    /**
     * Guardar pull request en base de datos
     */
    private function savePullRequest($prData, $repositoryId)
    {
        $data = [
            'github_id' => $prData['id'],
            'repository_id' => $repositoryId,
            'number' => $prData['number'],
            'title' => $prData['title'],
            'body' => $prData['body'],
            'state' => $prData['state'],
            'html_url' => $prData['html_url'],
            'author_login' => $prData['user']['login'],
            'author_avatar_url' => $prData['user']['avatar_url'],
            'base_branch' => $prData['base']['ref'],
            'head_branch' => $prData['head']['ref'],
            'mergeable' => $prData['mergeable'],
            'merged' => $prData['merged'],
            'draft' => $prData['draft'],
            'created_at' => $prData['created_at'],
            'updated_at' => $prData['updated_at'],
            'closed_at' => $prData['closed_at'],
            'merged_at' => $prData['merged_at']
        ];
        
        return $this->db->upsertPullRequest($data);
    }
    
    /**
     * Guardar commit en base de datos
     */
    private function saveCommit($commitData, $repositoryId)
    {
        $data = [
            'sha' => $commitData['sha'],
            'repository_id' => $repositoryId,
            'message' => $commitData['commit']['message'],
            'author_name' => $commitData['commit']['author']['name'],
            'author_email' => $commitData['commit']['author']['email'],
            'committer_name' => $commitData['commit']['committer']['name'],
            'committer_email' => $commitData['commit']['committer']['email'],
            'html_url' => $commitData['html_url'],
            'authored_at' => $commitData['commit']['author']['date'],
            'committed_at' => $commitData['commit']['committer']['date']
        ];
        
        return $this->db->insertCommit($data);
    }
    
    /**
     * Registrar log
     */
    private function log($type, $status, $message, $data = null)
    {
        echo "[" . date('Y-m-d H:i:s') . "] {$type}: {$message}\n";
        $this->db->logSync($type, $status, $message, $data);
    }
}
