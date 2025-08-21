<?php

namespace Yega\GitHub\Routing;

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

class ApiRoutes
{
    public static function getRoutes(): RouteCollection
    {
        $routes = new RouteCollection();

        // Repository routes
        $routes->add('repositories.index', new Route('/api/repositories', [
            '_controller' => 'Yega\\GitHub\\Controllers\\RepositoryController::index'
        ], [], [], '', [], ['GET']));

        $routes->add('repositories.show', new Route('/api/repositories/{repo}', [
            '_controller' => 'Yega\\GitHub\\Controllers\\RepositoryController::show'
        ], [], [], '', [], ['GET']));

        $routes->add('repositories.stats', new Route('/api/repositories/{repo}/stats', [
            '_controller' => 'Yega\\GitHub\\Controllers\\RepositoryController::stats'
        ], [], [], '', [], ['GET']));

        $routes->add('repositories.commits', new Route('/api/repositories/{repo}/commits', [
            '_controller' => 'Yega\\GitHub\\Controllers\\RepositoryController::commits'
        ], [], [], '', [], ['GET']));

        // Issue routes
        $routes->add('issues.index', new Route('/api/repositories/{repo}/issues', [
            '_controller' => 'Yega\\GitHub\\Controllers\\IssueController::index'
        ], [], [], '', [], ['GET']));

        $routes->add('issues.create', new Route('/api/repositories/{repo}/issues', [
            '_controller' => 'Yega\\GitHub\\Controllers\\IssueController::create'
        ], [], [], '', [], ['POST']));

        $routes->add('issues.update', new Route('/api/repositories/{repo}/issues/{issueNumber}', [
            '_controller' => 'Yega\\GitHub\\Controllers\\IssueController::update'
        ], ['issueNumber' => '\\d+'], [], '', [], ['PUT', 'PATCH']));

        $routes->add('issues.summary', new Route('/api/repositories/{repo}/issues/summary', [
            '_controller' => 'Yega\\GitHub\\Controllers\\IssueController::summary'
        ], [], [], '', [], ['GET']));

        // Pull Request routes
        $routes->add('pullrequests.index', new Route('/api/repositories/{repo}/pulls', [
            '_controller' => 'Yega\\GitHub\\Controllers\\PullRequestController::index'
        ], [], [], '', [], ['GET']));

        $routes->add('pullrequests.show', new Route('/api/repositories/{repo}/pulls/{prNumber}', [
            '_controller' => 'Yega\\GitHub\\Controllers\\PullRequestController::show'
        ], ['prNumber' => '\\d+'], [], '', [], ['GET']));

        $routes->add('pullrequests.files', new Route('/api/repositories/{repo}/pulls/{prNumber}/files', [
            '_controller' => 'Yega\\GitHub\\Controllers\\PullRequestController::files'
        ], ['prNumber' => '\\d+'], [], '', [], ['GET']));

        $routes->add('pullrequests.commits', new Route('/api/repositories/{repo}/pulls/{prNumber}/commits', [
            '_controller' => 'Yega\\GitHub\\Controllers\\PullRequestController::commits'
        ], ['prNumber' => '\\d+'], [], '', [], ['GET']));

        $routes->add('pullrequests.summary', new Route('/api/repositories/{repo}/pulls/summary', [
            '_controller' => 'Yega\\GitHub\\Controllers\\PullRequestController::summary'
        ], [], [], '', [], ['GET']));

        // Sync routes
        $routes->add('sync.all', new Route('/api/sync', [
            '_controller' => 'Yega\\GitHub\\Controllers\\SyncController::syncAll'
        ], [], [], '', [], ['POST']));

        $routes->add('sync.incremental', new Route('/api/sync/incremental', [
            '_controller' => 'Yega\\GitHub\\Controllers\\SyncController::syncIncremental'
        ], [], [], '', [], ['POST']));

        $routes->add('sync.status', new Route('/api/sync/status', [
            '_controller' => 'Yega\\GitHub\\Controllers\\SyncController::status'
        ], [], [], '', [], ['GET']));

        $routes->add('sync.history', new Route('/api/sync/history', [
            '_controller' => 'Yega\\GitHub\\Controllers\\SyncController::history'
        ], [], [], '', [], ['GET']));

        $routes->add('sync.schedule', new Route('/api/sync/schedule', [
            '_controller' => 'Yega\\GitHub\\Controllers\\SyncController::schedule'
        ], [], [], '', [], ['POST']));

        // Health check
        $routes->add('health', new Route('/api/health', [
            '_controller' => function() {
                return new \Symfony\Component\HttpFoundation\JsonResponse([
                    'status' => 'ok',
                    'timestamp' => time(),
                    'version' => '1.0.0'
                ]);
            }
        ], [], [], '', [], ['GET']));

        return $routes;
    }
}