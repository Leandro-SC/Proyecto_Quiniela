<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Utilidades de seguridad para formularios y vistas.
 */
class Security
{
    /**
     * Escapa valores para salida HTML.
     *
     * @param mixed $value Valor a imprimir.
     * @return string
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Genera o devuelve el token CSRF activo.
     *
     * @return string
     */
    public static function csrfToken(): string
    {
        Session::start();

        $token = Session::get('_csrf_token');

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf_token', $token);
        }

        return $token;
    }

    /**
     * Devuelve el input hidden CSRF.
     *
     * @return string
     */
    public static function csrfInput(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . self::e(self::csrfToken()) . '">';
    }

    /**
     * Valida el token CSRF recibido.
     *
     * @param mixed $token Token recibido por POST.
     * @return bool
     */
    public static function validateCsrfToken(mixed $token): bool
    {
        Session::start();

        $sessionToken = Session::get('_csrf_token');

        return is_string($token)
            && is_string($sessionToken)
            && $token !== ''
            && hash_equals($sessionToken, $token);
    }

    /**
     * Limpia texto simple de entrada.
     *
     * @param mixed $value Valor recibido.
     * @param int $maxLength Longitud máxima.
     * @return string
     */
    public static function cleanText(mixed $value, int $maxLength = 191): string
    {
        $text = trim((string)$value);
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }

        return $text;
    }
}