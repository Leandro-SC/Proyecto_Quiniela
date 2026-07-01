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

// Modo mantenimiento simple administrable desde settings.
// No bloquea el panel admin para permitir desactivarlo.
try {
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $isAdminRoute = str_starts_with($requestUri, '/admin');

    if (!$isAdminRoute && class_exists('\App\Core\Database')) {
        $pdoMaintenance = \App\Core\Database::getConnection();

        $stmtMaintenance = $pdoMaintenance->prepare('
            SELECT setting_value
            FROM settings
            WHERE setting_key = :setting_key
            LIMIT 1
        ');

        $stmtMaintenance->execute([
            ':setting_key' => 'maintenance_mode',
        ]);

        $maintenanceMode = (string)($stmtMaintenance->fetchColumn() ?: '0');

        if ($maintenanceMode === '1') {
            http_response_code(503);
            echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mantenimiento</title></head><body style="font-family:Arial,sans-serif;background:#0f172a;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center;padding:24px;"><main><h1>Estamos en mantenimiento</h1><p>Volveremos pronto. Gracias por tu paciencia.</p></main></body></html>';
            exit;
        }
    }
} catch (Throwable $e) {
    error_log('Error verificando modo mantenimiento: ' . $e->getMessage());
}

// Zona horaria centralizada.
// Evita valores quemados como America/Chicago dentro del front controller.
$timezone = (string)($config['app']['timezone'] ?? 'America/Mexico_City');

if ($timezone === '') {
    $timezone = 'America/Mexico_City';
}

date_default_timezone_set($timezone);

// Cabeceras de seguridad base.
// Se mantienen simples para no romper assets actuales.
$env = $config['app']['env'] ?? 'production';

if ($env === 'production') {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    ) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
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