<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Core\Session;
use App\Models\AdminUserModel;
use PDO;

/**
 * Controlador de autenticación del módulo administrador.
 */
class AdminAuthController extends Controller
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    /**
     * Muestra el formulario de login.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function showLoginForm(Request $request, Response $response): void
    {
        if ($this->isLoggedIn()) {
            header('Location: /admin/dashboard');
            exit;
        }

        $this->render('admin/auth/login', [
            'pageTitle' => 'Acceso administrador · Villa Quiniela',
            'error'     => null,
            'old'       => [],
            'csrfInput' => Security::csrfInput(),
        ]);
    }

    /**
     * Procesa login con protección CSRF y bloqueo por intentos.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function login(Request $request, Response $response): void
    {
        if ($this->isLoggedIn()) {
            header('Location: /admin/dashboard');
            exit;
        }

        $username = Security::cleanText($_POST['username'] ?? '', 100);
        $password = (string)($_POST['password'] ?? '');
        $ipAddress = $request->getClientIp();
        $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        $old = [
            'username' => $username,
        ];

        if (!Security::validateCsrfToken($_POST['_csrf_token'] ?? null)) {
            $this->renderLoginError('La sesión expiró. Actualiza la página e intenta nuevamente.', $old);
            return;
        }

        if ($username === '' || $password === '') {
            $this->renderLoginError('Usuario y contraseña son obligatorios.', $old);
            return;
        }

        if ($this->isLoginBlocked($username, $ipAddress)) {
            $this->registerLoginAttempt($username, $ipAddress, $userAgent, false);
            $this->renderLoginError('Demasiados intentos fallidos. Intenta nuevamente en 15 minutos.', $old);
            return;
        }

        $adminModel = new AdminUserModel();
        $admin = $adminModel->verifyCredentials($username, $password);

        if ($admin === null) {
            $this->registerLoginAttempt($username, $ipAddress, $userAgent, false);
            $this->renderLoginError('Credenciales inválidas o usuario inactivo.', $old);
            return;
        }

        $this->registerLoginAttempt($username, $ipAddress, $userAgent, true);
        $this->startAdminSession((int)$admin['id'], (string)$admin['username']);

        $this->updateLastLogin((int)$admin['id'], $ipAddress);

        header('Location: /admin/dashboard');
        exit;
    }

    /**
     * Cierra sesión del administrador.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function logout(Request $request, Response $response): void
    {
        Session::destroy();

        header('Location: /admin/login');
        exit;
    }

    /**
     * Renderiza el login con error.
     *
     * @param string $error Mensaje de error.
     * @param array<string,mixed> $old Datos anteriores.
     * @return void
     */
    private function renderLoginError(string $error, array $old): void
    {
        $this->render('admin/auth/login', [
            'pageTitle' => 'Acceso administrador · Villa Quiniela',
            'error'     => $error,
            'old'       => $old,
            'csrfInput' => Security::csrfInput(),
        ]);
    }

    /**
     * Verifica si hay sesión admin activa.
     *
     * @return bool
     */
    private function isLoggedIn(): bool
    {
        $adminId = Session::get('admin.id');

        return $adminId !== null && (int)$adminId > 0;
    }

    /**
     * Crea sesión admin segura.
     *
     * @param int $adminId
     * @param string $username
     * @return void
     */
    private function startAdminSession(int $adminId, string $username): void
    {
        Session::regenerate();

        Session::set('admin.id', $adminId);
        Session::set('admin.username', $username);
        Session::set('admin.logged_in_at', date('Y-m-d H:i:s'));
    }

    /**
     * Determina si el login debe bloquearse por intentos fallidos.
     *
     * @param string $identifier Usuario o email.
     * @param string $ipAddress IP del cliente.
     * @return bool
     */
    private function isLoginBlocked(string $identifier, string $ipAddress): bool
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('
            SELECT COUNT(*) AS failed_attempts
            FROM login_attempts
            WHERE success = 0
              AND attempted_at >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
              AND (identifier = :identifier OR ip_address = :ip_address)
        ');

        $stmt->execute([
            ':minutes'    => self::LOCK_MINUTES,
            ':identifier' => $identifier,
            ':ip_address' => $ipAddress,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)($row['failed_attempts'] ?? 0) >= self::MAX_FAILED_ATTEMPTS;
    }

    /**
     * Registra intento de login.
     *
     * @param string $identifier Usuario o email.
     * @param string $ipAddress IP.
     * @param string $userAgent Navegador.
     * @param bool $success Resultado.
     * @return void
     */
    private function registerLoginAttempt(
        string $identifier,
        string $ipAddress,
        string $userAgent,
        bool $success
    ): void {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('
            INSERT INTO login_attempts
            (identifier, ip_address, user_agent, success, attempted_at)
            VALUES
            (:identifier, :ip_address, :user_agent, :success, NOW())
        ');

        $stmt->execute([
            ':identifier' => $identifier,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent,
            ':success'    => $success ? 1 : 0,
        ]);
    }

    /**
     * Actualiza último acceso del administrador.
     *
     * @param int $adminId
     * @param string $ipAddress
     * @return void
     */
    private function updateLastLogin(int $adminId, string $ipAddress): void
    {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('
            UPDATE admin_users
            SET last_login_at = NOW(),
                last_login_ip = :ip_address
            WHERE id = :id
        ');

        $stmt->execute([
            ':id' => $adminId,
            ':ip_address' => $ipAddress,
        ]);
    }
}