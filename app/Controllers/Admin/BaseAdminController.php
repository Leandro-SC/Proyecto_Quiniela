<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;

/**
 * Controlador base para el área de administración.
 *
 * Todas las pantallas admin deben extender de esta clase
 * y llamar a $this->requireAdmin() al inicio de cada acción
 * que requiera autenticación.
 */
abstract class BaseAdminController extends Controller
{
    /**
     * Exigir que haya un administrador autenticado.
     *
     * Si no hay sesión válida, redirige al formulario de login.
     */
    protected function requireAdmin(): void
    {
        $adminId = Session::get('admin.id');

        if ($adminId === null || (int)$adminId <= 0) {
            // No autenticado: llevamos al login admin
            header('Location: /Admin/login');
            exit;
        }
    }

    /**
     * Obtener el ID del administrador autenticado.
     */
    protected function getAdminId(): ?int
    {
        $adminId = Session::get('admin.id');
        if ($adminId === null) {
            return null;
        }

        $adminId = (int)$adminId;
        return $adminId > 0 ? $adminId : null;
    }

    /**
     * Obtener el username del administrador autenticado.
     */
    protected function getAdminUsername(): ?string
    {
        $username = Session::get('admin.username');
        return $username !== null ? (string)$username : null;
    }
}
