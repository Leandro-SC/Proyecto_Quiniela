<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Conexión PDO a la base de datos (singleton).
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Obtener conexión PDO única para toda la aplicación.
     *
     * @throws RuntimeException|PDOException
     */
    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $configPath = dirname(__DIR__, 2) . '/config/app.php';

        if (!is_file($configPath)) {
            throw new RuntimeException('Archivo de configuración no encontrado: ' . $configPath);
        }

        /** @var array<string,mixed> $config */
        $config = require $configPath;

        if (!isset($config['database']) || !is_array($config['database'])) {
            throw new RuntimeException('Configuración de base de datos inválida en config/app.php');
        }

        $db = $config['database'];

        $driver    = $db['driver']    ?? 'mysql';
        $host      = $db['host']      ?? '127.0.0.1';
        $port      = (int)($db['port'] ?? 3306);
        $dbname    = $db['database']  ?? 'u131981230_quinielasvilla';
        $charset   = $db['charset']   ?? 'utf8mb4';
        $username  = $db['username']  ?? 'u131981230_dev_quiniela';
        $password  = $db['password']  ?? 'quinielasE$4@';

        if ($dbname === '' || $username === '') {
            throw new RuntimeException('Faltan datos obligatorios de conexión en config/app.php');
        }

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $driver,
            $host,
            $port,
            $dbname,
            $charset
        );

        try {
            $pdo = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );

            // --- CAMBIO PARA OKLAHOMA (UTC-6) ---
            // Forzamos la zona horaria en la sesión de la base de datos
            $pdo->exec("SET time_zone = '-06:00'");
            // ------------------------------------

        } catch (PDOException $e) {
            throw new PDOException(
                'Error de conexión a la base de datos: ' . $e->getMessage(),
                (int)$e->getCode()
            );
        }

        self::$connection = $pdo;
        return $pdo;
    }
}