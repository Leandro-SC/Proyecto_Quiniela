<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Registro simple de logs a archivo.
 */
class Logger
{
    /**
     * Escribir un mensaje en el archivo de log principal.
     *
     * @param string $level   Nivel (info, warning, error).
     * @param string $message Mensaje principal.
     * @param array<string,mixed> $context Datos adicionales para depuración.
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $configPath = dirname(__DIR__, 2) . '/config/app.php';

        if (!is_file($configPath)) {
            // No se puede loguear sin configuración.
            return;
        }

        /** @var array<string,mixed> $config */
        $config = require $configPath;
        $paths  = $config['paths'] ?? [];
        $logsPath = $paths['logs_path'] ?? (dirname(__DIR__, 2) . '/storage/logs');

        if (!is_dir($logsPath)) {
            if (!mkdir($logsPath, 0775, true) && !is_dir($logsPath)) {
                throw new RuntimeException('No se pudo crear el directorio de logs: ' . $logsPath);
            }
        }

        $file = rtrim($logsPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app.log';

        $date = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $line = sprintf(
            "[%s] %s: %s %s\n",
            $date,
            strtoupper($level),
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
        );

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }
}
