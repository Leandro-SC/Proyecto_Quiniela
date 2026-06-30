<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Manejo centralizado de sesión.
 */
class Session
{
    /**
     * Inicia sesión de forma segura.
     *
     * @return void
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        );

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_name('QVSESSID');
        session_start();
    }

    /**
     * Guarda un valor en sesión.
     *
     * @param string $key Clave.
     * @param mixed $value Valor.
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Obtiene un valor de sesión.
     *
     * @param string $key Clave.
     * @param mixed $default Valor por defecto.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Elimina un valor de sesión.
     *
     * @param string $key Clave.
     * @return void
     */
    public static function remove(string $key): void
    {
        self::start();

        unset($_SESSION[$key]);
    }

    /**
     * Regenera el ID de sesión.
     *
     * @return void
     */
    public static function regenerate(): void
    {
        self::start();

        session_regenerate_id(true);
    }

    /**
     * Destruye la sesión activa.
     *
     * @return void
     */
    public static function destroy(): void
    {
        self::start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool)$params['secure'],
                (bool)$params['httponly']
            );
        }

        session_destroy();
    }
}