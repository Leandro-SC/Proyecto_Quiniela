<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AdminUserModel;

/**
 * Controlador de autenticación del módulo Admin.
 *
 * Responsable de:
 * - Mostrar formulario de login.
 * - Validar credenciales.
 * - Crear y destruir sesión de administrador.
 */
class AdminAuthController extends Controller
{
    /**
     * Mostrar formulario de login de administrador.
     */
    public function showLoginForm(Request $request, Response $response): void
    {
        // Si ya hay sesión de admin, redirigir al panel
        if ($this->isLoggedIn()) {
            // CORREGIDO: Minúscula para coincidir con routes.php
            header('Location: /admin/rounds');
            exit;
        }

        // CORREGIDO: Minúscula para coincidir con la carpeta de vistas (app/Views/admin/)
        $this->render('admin/auth/login', [
            'pageTitle' => 'Acceso administrador · Villa Quiniela',
            'error'     => null,
            'old'       => [],
        ]);
    }

    /**
     * Procesar login de administrador (POST).
     */
    public function login(Request $request, Response $response): void
    {
        // Si ya hay sesión de admin, redirigir
        if ($this->isLoggedIn()) {
            header('Location: /admin/rounds');
            exit;
        }

        // Leemos directamente de $_POST para evitar dependencias no estándar
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $old = [
            'username' => $username,
        ];

        if ($username === '' || $password === '') {
            $this->render('admin/auth/login', [
                'pageTitle' => 'Acceso administrador · Villa Quiniela',
                'error'     => 'Usuario y contraseña son obligatorios.',
                'old'       => $old,
            ]);
            return;
        }

        $adminModel = new AdminUserModel();
        $admin      = $adminModel->verifyCredentials($username, $password);

        if ($admin === null) {
            // Credenciales inválidas
            $this->render('admin/auth/login', [
                'pageTitle' => 'Acceso administrador · Villa Quiniela',
                'error'     => 'Credenciales inválidas o usuario inactivo.',
                'old'       => $old,
            ]);
            return;
        }

        // Credenciales correctas: crear sesión segura de admin
        $this->startAdminSession((int)$admin['id'], (string)$admin['username']);

        // CORREGIDO: Minúscula
        header('Location: /admin/rounds');
        exit;
    }

    /**
     * Cerrar sesión de administrador.
     */
    public function logout(Request $request, Response $response): void
    {
        if ($this->isLoggedIn()) {
            $this->destroyAdminSession();
        }

        // CORREGIDO: Minúscula
        header('Location: /admin/login');
        exit;
    }

    /**
     * Verificar si hay un administrador autenticado.
     */
    private function isLoggedIn(): bool
    {
        $adminId = Session::get('admin.id');
        return $adminId !== null && (int)$adminId > 0;
    }

    /**
     * Iniciar sesión de administrador de forma segura.
     */
    private function startAdminSession(int $adminId, string $username): void
    {
        // Regenerar ID de sesión para mitigar fijación de sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);

        Session::set('admin.id', $adminId);
        Session::set('admin.username', $username);
        Session::set('admin.logged_in_at', date('Y-m-d H:i:s'));
    }

    /**
     * Destruir datos de sesión del administrador.
     */
    private function destroyAdminSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Eliminar solo claves relacionadas al admin
        Session::set('admin.id', null);
        Session::set('admin.username', null);
        Session::set('admin.logged_in_at', null);

        // Opcional: limpiar directamente en $_SESSION para mayor seguridad
        if (isset($_SESSION['admin'])) {
            unset($_SESSION['admin']);
        }

        session_regenerate_id(true);
    }
}
