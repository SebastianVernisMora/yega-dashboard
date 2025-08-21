<?php

/**
 * Punto de entrada principal para Yega GitHub API
 * 
 * Este archivo maneja todas las requests HTTP y las enruta a los controladores apropiados.
 * Implementa middleware para rate limiting, cache y manejo de errores.
 */

require_once __DIR__ . '/src/bootstrap.php';