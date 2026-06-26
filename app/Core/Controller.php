<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Clase base para todos los controladores.
 */
abstract class Controller
{
    /**
     * Renderizar una vista con el layout principal.
     *
     * @param string $view Ruta de la vista relativa a app/Views, por ejemplo "home/index".
     * @param array<string,mixed> $params Datos para la vista.
     */
    protected function render(string $view, array $params = []): void
{
    $viewFile = __DIR__ . '/../Views/' . $view . '.php';

    if (!is_file($viewFile)) {
        throw new \RuntimeException("Vista no encontrada: $viewFile");
    }

    // Variables para la vista
    extract($params);

    $layoutFile = __DIR__ . '/../Views/layouts/main.php';
    if (!is_file($layoutFile)) {
        throw new \RuntimeException("Layout principal no encontrado.");
    }

    require $layoutFile;
}

}
