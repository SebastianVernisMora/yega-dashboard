<?php

/**
 * Archivo de bootstrap para incluir en todos los archivos de la API
 * Carga configuración y clases necesarias
 */

// Incluir configuración
require_once __DIR__ . '/config.php';

// Función para incluir clases automáticamente
spl_autoload_register(function ($class) {
    $classFile = __DIR__ . '/src/' . $class . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

// Configurar manejo de errores para APIs
if (!php_sapi_name() === 'cli') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
