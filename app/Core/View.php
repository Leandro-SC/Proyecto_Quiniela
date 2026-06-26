<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Motor de vistas muy simple.
 */
class View
{
    /**
     * Renderizar una vista usando el layout principal.
     *
     * @param string $view Ruta relativa como "home/index".
     * @param array<string,mixed> $params
     */
    public static function render(string $view, array $params = []): void
    {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException('Vista no encontrada: ' . $viewFile);
        }

        extract($params, EXTR_SKIP);

        $pageTitle = $params['pageTitle'] ?? 'Villa Quinielas';

        $layoutFile = __DIR__ . '/../Views/layouts/main.php';
        if (!is_file($layoutFile)) {
            throw new \RuntimeException('Layout principal no encontrado.');
        }

        require $layoutFile;
    }
}
