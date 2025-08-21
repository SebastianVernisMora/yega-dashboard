<?php

require_once __DIR__ . '/vendor/autoload.php';

use Yega\Dashboard\GitHubAPI;
use Yega\Dashboard\Database;
use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configurar logger
$logger = new Logger('sync');
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/sync.log', Logger::INFO));

try {
    // Inicializar servicios
    $githubApi = new GitHubAPI($_ENV['GITHUB_TOKEN'], (int)$_ENV['CACHE_TTL']);
    
    // Parse DATABASE_URL
    $databaseUrl = parse_url($_ENV['DATABASE_URL']);
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $databaseUrl['host'],
        $databaseUrl['port'] ?? 3306,
        ltrim($databaseUrl['path'], '/')
    );
    
    $database = new Database($dsn, $databaseUrl['user'], $databaseUrl['pass']);
    
    // Repositorios del ecosistema Yega
    $repositories = explode(',', $_ENV['YEGA_REPOSITORIES']);
    
    $logger->info('Starting sync for ' . count($repositories) . ' repositories');
    
    foreach ($repositories as $repoFullName) {
        $repoFullName = trim($repoFullName);
        [$owner, $repoName] = explode('/', $repoFullName);
        
        $logger->info("Syncing repository: {$repoFullName}");
        
        // Sincronizar datos del repositorio
        $repoData = $githubApi->getRepository($owner, $repoName);
        if ($repoData) {
            $repositoryId = $database->saveRepository($repoData);
            $logger->info("Repository {$repoFullName} saved with ID: {$repositoryId}");
            
            // Sincronizar issues
            $issues = $githubApi->getIssues($owner, $repoName);
            if ($issues) {
                $database->saveIssues($repositoryId, $issues);
            }
            
            // Sincronizar pull requests
            $pullRequests = $githubApi->getPullRequests($owner, $repoName);
            if ($pullRequests) {
                $database->savePullRequests($repositoryId, $pullRequests);
            }
            
            // Sincronizar README
            $readme = $githubApi->getReadme($owner, $repoName);
            if ($readme) {
                $database->saveReadme($repositoryId, $readme);
            }
            
            $logger->info("Completed sync for {$repoFullName}");
        } else {
            $logger->error("Failed to fetch repository data for {$repoFullName}");
        }
        
        // Pausa para evitar rate limiting
        sleep(1);
    }
    
    $logger->info('Sync completed successfully');
    echo "Sync completed successfully at " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    $logger->error('Sync failed: ' . $e->getMessage());
    echo "Sync failed: " . $e->getMessage() . "\n";
    exit(1);
}