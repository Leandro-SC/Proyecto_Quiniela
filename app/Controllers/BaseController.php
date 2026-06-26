<?php
declare(strict_types=1);

namespace App\Controllers;

/**
 * Controlador base para todo el proyecto.
 *
 * - Carga la configuración de config/app.php.
 * - Expone $this->config a los controladores hijos.
 * - Implementa render() para usar layout principal.
 */
abstract class BaseController
{
    /** @var array<string,mixed> */
    protected array $config = [];

    public function __construct()
    {
        $configPath = dirname(__DIR__, 2) . '/config/app.php';

        if (is_file($configPath)) {
            /** @var array<string,mixed> $cfg */
            $cfg = require $configPath;
            $this->config = $cfg;
        } else {
            $this->config = [];
        }
    }

    /**
     * Renderizar una vista dentro del layout principal.
     *
     * @param string               $view     Ruta relativa dentro de app/Views SIN .php (ej: 'home/index')
     * @param array<string,mixed>  $params   Variables disponibles en la vista
     */
    protected function render(string $view, array $params = []): void
    {
        $viewsPath   = dirname(__DIR__) . '/Views';
        $viewFile    = $viewsPath . '/' . $view . '.php';
        $layoutFile  = $viewsPath . '/layouts/main.php';

        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'Vista no encontrada: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        if (!is_file($layoutFile)) {
            // Si no hay layout, renderizamos solo la vista para no romper producción
            extract($params, EXTR_SKIP);
            require $viewFile;
            return;
        }

        // Variables disponibles tanto en la vista como en el layout
        $config = $this->config;

        // Extraer parámetros para la vista
        extract($params, EXTR_SKIP);

        // Renderizar la vista en un buffer
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Incluir layout principal (usa $content)
        require $layoutFile;
    }
}
