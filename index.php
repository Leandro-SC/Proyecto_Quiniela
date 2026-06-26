<?php
/*
 * Front controller del Sistema de Quiniela Automatizada.
 */

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\GeoService;

// --- 1. CARGA DE LIBRERÍAS ---
require __DIR__ . '/vendor/autoload.php';


// --- 2. CARGA DE VARIABLES DE ENTORNO (.env) ---
$envFile = __DIR__ . '/.env';
if (is_file($envFile) && class_exists(\Dotenv\Dotenv::class)) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__); 
    $dotenv->safeLoad();
}

// --- 3. CARGA DE CONFIGURACIÓN ---
$configPath = __DIR__ . '/config/app.php';
if (!is_file($configPath)) {
    http_response_code(500);
    echo 'Error crítico: No se encuentra el archivo config/app.php';
    exit;
}

/** @var array<string,mixed> $config */
$config = require $configPath;

// --- CAMBIO PARA OKLAHOMA ---
// Forzamos la zona horaria de Oklahoma (America/Chicago)
// Esto asegura que date(), time() y strtotime() funcionen correctamente.
date_default_timezone_set('America/Chicago');
// ----------------------------

// Cabeceras de seguridad
$env = $config['app']['env'] ?? 'production';
if ($env === 'production') {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Iniciar sesión
Session::start();

// Crear Request/Response
$request  = new Request($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
$response = new Response();

// Middleware Geo
GeoService::boot($request, $config);

// --- 4. RUTAS ---
$routesPath = __DIR__ . '/config/routes.php';
if (!is_file($routesPath)) {
    http_response_code(500);
    echo 'Error crítico: No se encuentra el archivo config/routes.php';
    exit;
}

$routes = require $routesPath;
$router = new Router($routes, $config);

// Ejecutar aplicación
try {
    $router->dispatch($request, $response);
} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    
    // Mostrar error si DEBUG es true en el .env
    $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
    if ($debug) {
        echo '<h1>Error de Aplicación</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } else {
        echo '<h1>Ha ocurrido un error inesperado</h1>';
        echo '<p>Por favor, intenta nuevamente más tarde.</p>';
    }
}