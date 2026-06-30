<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Security;
use App\Core\Session;

/**
 * Controlador base para el área administrativa.
 *
 * Centraliza:
 * - Validación de sesión admin.
 * - Validación CSRF solo en acciones POST.
 * - Acceso al administrador autenticado.
 */
abstract class BaseAdminController extends Controller
{
    /**
     * Exige que exista un administrador autenticado.
     *
     * @return void
     */
    protected function requireAdmin(): void
    {
        $adminId = Session::get('admin.id');

        if ($adminId === null || (int)$adminId <= 0) {
            header('Location: /admin/login');
            exit;
        }
    }

    /**
     * Valida token CSRF únicamente en solicitudes POST.
     *
     * Importante:
     * No debe bloquear pantallas GET como:
     * - /admin/leagues
     * - /admin/countries
     * - /admin/settings
     *
     * @return void
     */
    protected function requireValidCsrf(): void
    {
        $requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($requestMethod !== 'POST') {
            return;
        }

        if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? null)) {
            http_response_code(419);
            echo 'Token de seguridad inválido. Actualiza la página e intenta nuevamente.';
            exit;
        }
    }

    /**
     * Obtiene el ID del administrador autenticado.
     *
     * @return int|null
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
     * Obtiene el usuario del administrador autenticado.
     *
     * @return string|null
     */
    protected function getAdminUsername(): ?string
    {
        $username = Session::get('admin.username');

        return $username !== null ? (string)$username : null;
    }
}