<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Conexión PDO única de la aplicación.
 *
 * Decisiones:
 * - No usa credenciales quemadas.
 * - Desactiva prepared statements emulados.
 * - Fuerza utf8mb4.
 * - Lee timezone desde config/settings cuando esté disponible.
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Obtiene una conexión PDO reutilizable.
     *
     * @return PDO
     *
     * @throws RuntimeException
     * @throws PDOException
     */
    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $configPath = dirname(__DIR__, 2) . '/config/app.php';

        if (!is_file($configPath)) {
            throw new RuntimeException('No se encontró config/app.php.');
        }

        /** @var array<string,mixed> $config */
        $config = require $configPath;

        $db = $config['database'] ?? [];

        $driver   = (string)($db['driver'] ?? 'mysql');
        $host     = (string)($db['host'] ?? 'localhost');
        $port     = (int)($db['port'] ?? 3306);
        $database = (string)($db['database'] ?? '');
        $username = (string)($db['username'] ?? '');
        $password = (string)($db['password'] ?? '');
        $charset  = (string)($db['charset'] ?? 'utf8mb4');

        if ($database === '' || $username === '') {
            throw new RuntimeException('Faltan credenciales de base de datos en variables de entorno.');
        }

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $driver,
            $host,
            $port,
            $database,
            $charset
        );

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

        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

        self::$connection = $pdo;

        return self::$connection;
    }
}