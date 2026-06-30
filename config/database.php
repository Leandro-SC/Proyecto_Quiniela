<?php
declare(strict_types=1);

/**
 * Configuración de base de datos.
 *
 * No colocar credenciales reales como valores por defecto.
 * En producción deben venir exclusivamente desde .env.
 */

return [
    'driver'   => $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: 'mysql',
    'host'     => $_ENV['DB_HOST']       ?? getenv('DB_HOST')       ?: 'localhost',
    'port'     => (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT')       ?: 3306),
    'dbname'   => $_ENV['DB_DATABASE']   ?? getenv('DB_DATABASE')   ?: '',
    'username' => $_ENV['DB_USERNAME']   ?? getenv('DB_USERNAME')   ?: '',
    'password' => $_ENV['DB_PASSWORD']   ?? getenv('DB_PASSWORD')   ?: '',
    'charset'  => $_ENV['DB_CHARSET']    ?? getenv('DB_CHARSET')    ?: 'utf8mb4',
];