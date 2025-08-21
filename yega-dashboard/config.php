<?php

/**
 * Archivo de configuración para variables de entorno
 * Cargar este archivo antes de usar las clases del dashboard
 */

// Cargar variables de entorno desde .env si existe
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Ignorar comentarios
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, ' "\''');
        
        if (!empty($key) && !isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Configuraciones por defecto
if (!isset($_ENV['DB_HOST'])) $_ENV['DB_HOST'] = 'localhost';
if (!isset($_ENV['DB_NAME'])) $_ENV['DB_NAME'] = 'yega_dashboard';
if (!isset($_ENV['DB_USER'])) $_ENV['DB_USER'] = 'root';
if (!isset($_ENV['DB_PASSWORD'])) $_ENV['DB_PASSWORD'] = '';

// Validar configuraciones críticas
if (!isset($_ENV['GITHUB_TOKEN'])) {
    if (php_sapi_name() === 'cli') {
        echo "ERROR: Variable GITHUB_TOKEN no configurada\n";
        exit(1);
    }
}
