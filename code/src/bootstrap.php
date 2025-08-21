<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Predis\Client as RedisClient;
use League\Container\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

use Yega\GitHub\Config\GitHubConfig;
use Yega\GitHub\Services\GitHubService;
use Yega\GitHub\Services\RateLimitService;
use Yega\GitHub\Controllers\RepositoryController;
use Yega\GitHub\Controllers\IssueController;
use Yega\GitHub\Controllers\PullRequestController;
use Yega\GitHub\Controllers\SyncController;
use Yega\GitHub\Middleware\RateLimitMiddleware;
use Yega\GitHub\Middleware\CacheMiddleware;
use Yega\GitHub\Routing\ApiRoutes;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Configurar logger
$logger = new Logger('yega-github-api');
$logger->pushHandler(new StreamHandler(__DIR__ . '/logs/app.log', Logger::INFO));
$logger->pushHandler(new StreamHandler('php://stdout', Logger::ERROR));

// Configurar Redis
$redis = new RedisClient([
    'scheme' => 'tcp',
    'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['REDIS_PORT'] ?? 6379,
    'database' => $_ENV['REDIS_DB'] ?? 0
]);

// Configurar contenedor de dependencias
$container = new Container();

// Registrar servicios
$container->add(Logger::class, $logger);
$container->add(RedisClient::class, $redis);
$container->add(GitHubConfig::class, new GitHubConfig());

$container->add(RateLimitService::class)
    ->addArgument(RedisClient::class)
    ->addArgument(Logger::class);

$container->add(GitHubService::class)
    ->addArgument(GitHubConfig::class)
    ->addArgument(Logger::class)
    ->addArgument(RedisClient::class)
    ->addArgument(RateLimitService::class);

// Registrar controllers
$container->add(RepositoryController::class)
    ->addArgument(GitHubService::class)
    ->addArgument(Logger::class);

$container->add(IssueController::class)
    ->addArgument(GitHubService::class)
    ->addArgument(Logger::class);

$container->add(PullRequestController::class)
    ->addArgument(GitHubService::class)
    ->addArgument(Logger::class);

$container->add(SyncController::class)
    ->addArgument(GitHubService::class)
    ->addArgument(Logger::class)
    ->addArgument(RedisClient::class);

// Registrar middleware
$container->add(RateLimitMiddleware::class)
    ->addArgument(RateLimitService::class)
    ->addArgument(Logger::class);

$container->add(CacheMiddleware::class)
    ->addArgument(RedisClient::class)
    ->addArgument(Logger::class)
    ->addArgument(300); // TTL por defecto

// Manejar request
$request = Request::createFromGlobals();

// CORS headers
$corsHeaders = [
    'Access-Control-Allow-Origin' => '*',
    'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
    'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
    'Access-Control-Max-Age' => '86400'
];

// Manejar preflight OPTIONS request
if ($request->getMethod() === 'OPTIONS') {
    $response = new Response('', 200, $corsHeaders);
    $response->send();
    exit;
}

try {
    // Configurar routing
    $routes = ApiRoutes::getRoutes();
    $context = new RequestContext();
    $context->fromRequest($request);
    $matcher = new UrlMatcher($routes, $context);
    
    $routeInfo = $matcher->match($request->getPathInfo());
    $controllerName = $routeInfo['_controller'];
    
    // Crear instancia del controller
    if (is_string($controllerName) && strpos($controllerName, '::') !== false) {
        [$class, $method] = explode('::', $controllerName);
        $controller = $container->get($class);
        
        // Aplicar middleware
        $rateLimitMiddleware = $container->get(RateLimitMiddleware::class);
        $cacheMiddleware = $container->get(CacheMiddleware::class);
        
        $response = $rateLimitMiddleware->handle($request, function($request) use ($cacheMiddleware, $controller, $method, $routeInfo) {
            return $cacheMiddleware->handle($request, function($request) use ($controller, $method, $routeInfo) {
                // Extraer parámetros de la ruta
                $params = array_filter($routeInfo, function($key) {
                    return $key !== '_controller' && $key !== '_route';
                }, ARRAY_FILTER_USE_KEY);
                
                // Llamar al método del controller
                $args = [$request];
                foreach ($params as $param) {
                    $args[] = $param;
                }
                
                return call_user_func_array([$controller, $method], $args);
            });
        });
    } else {
        // Controller como closure
        $response = $controllerName();
    }
    
} catch (ResourceNotFoundException $e) {
    $response = new JsonResponse([
        'success' => false,
        'error' => 'Endpoint no encontrado',
        'path' => $request->getPathInfo()
    ], 404);
    
} catch (MethodNotAllowedException $e) {
    $response = new JsonResponse([
        'success' => false,
        'error' => 'Método no permitido',
        'allowed_methods' => $e->getAllowedMethods()
    ], 405);
    
} catch (\Exception $e) {
    $logger->error('Error no manejado en la API', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'path' => $request->getPathInfo(),
        'method' => $request->getMethod()
    ]);
    
    $response = new JsonResponse([
        'success' => false,
        'error' => 'Error interno del servidor',
        'timestamp' => time()
    ], 500);
}

// Añadir CORS headers a la respuesta
foreach ($corsHeaders as $key => $value) {
    $response->headers->set($key, $value);
}

// Añadir headers adicionales
$response->headers->set('Content-Type', 'application/json');
$response->headers->set('X-API-Version', '1.0.0');
$response->headers->set('X-Powered-By', 'Yega GitHub API');

$response->send();