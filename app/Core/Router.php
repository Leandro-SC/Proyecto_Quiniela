<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Router simple para mapear rutas a controladores y acciones.
 */
class Router
{
    /** @var array<string,mixed> */
    private array $routes;

    /** @var array<string,mixed> */
    private array $config;

    /**
     * @param array<string,mixed> $routes
     * @param array<string,mixed> $config
     */
    public function __construct(array $routes, array $config)
    {
        $this->routes = $routes;
        $this->config = $config;
    }

    /**
     * Despachar la petición a la acción correspondiente.
     */
    public function dispatch(Request $request, Response $response): void
    {
        $method = $request->getMethod();
        $uri    = $this->normalizePath($request->getPath());

        $routeTable = $this->routes[$method] ?? [];

        if (!isset($routeTable[$uri])) {
            $this->handleNotFound($response);
            return;
        }

        /** @var array<string,string> $route */
        $route = $routeTable[$uri];

        $controllerName = $route['controller'] ?? '';
        $actionName     = $route['action'] ?? '';

        if ($controllerName === '' || $actionName === '') {
            $response->setStatusCode(500);
            $response->setContent('Ruta mal configurada.');
            $response->send();
            return;
        }

        $controllerClass = 'App\\Controllers\\' . $controllerName;

        if (!class_exists($controllerClass)) {
            $response->setStatusCode(500);
            $response->setContent('Controlador no encontrado.');
            $response->send();
            return;
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $actionName)) {
            $response->setStatusCode(500);
            $response->setContent('Acción no encontrada en el controlador.');
            $response->send();
            return;
        }

        // Ejecutar acción
        $controller->{$actionName}($request, $response);
    }

    /**
     * Normalizar ruta (quitar querystring, trailing slash).
     */
    private function normalizePath(string $path): string
    {
        $path = strtok($path, '?') ?: '/';

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }

    /**
     * Manejo de 404 centralizado.
     */
    private function handleNotFound(Response $response): void
    {
        $response->setStatusCode(404);
        $response->setContent('Página no encontrada.');
        $response->send();
    }
}
