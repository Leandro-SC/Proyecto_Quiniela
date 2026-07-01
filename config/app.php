<?php

declare(strict_types=1);

/*
 * Configuración principal de la aplicación.
 * Mantiene PHP nativo y centraliza valores base desde .env.
 */

return [
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'QuinielaPro',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',

        /*
         * Zona horaria base del sistema.
         * Para mercado latino en EE. UU. puede cambiarse luego desde settings.
         */
        'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/Mexico_City',

        /*
         * WhatsApp principal.
         * Se elimina la configuración duplicada que existía fuera de app.
         */
        'whatsapp' => [
            'phone' => $_ENV['WHATSAPP_NUMBER'] ?? '+51904472452',
        ],
    ],

    'database' => [
        'driver' => 'mysql',
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => (int)($_ENV['DB_PORT'] ?? 3306),
        'database' => $_ENV['DB_DATABASE'] ?? '',
        'username' => $_ENV['DB_USERNAME'] ?? '',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],

    'paths' => [
        'base_path' => dirname(__DIR__),
        'public_path' => dirname(__DIR__),
        'storage_path' => dirname(__DIR__) . '/storage',
        'logs_path' => dirname(__DIR__) . '/storage/logs',
        'cache_path' => dirname(__DIR__) . '/storage/cache',
    ],

    'leagues' => [
        'default_slug' => 'liga-mx',
        'available' => ['liga-mx', 'uefa-champions'],
    ],

    'currency_defaults' => [
        'MX' => 'MXN',
        'US' => 'USD',
        'DEFAULT' => 'USD',
    ],

    'sports_api' => [
        'primary' => [
            'provider' => 'THESPORTSDB',
            'base_url' => 'https://www.thesportsdb.com/api/v1/json',
            'api_key' => $_ENV['SPORTSDB_KEY'] ?? '',
        ],
        'fallback' => [
            'provider' => 'API_FOOTBALL',
            'base_url' => 'https://v3.football.api-sports.io',
            'api_key' => $_ENV['API_FOOTBALL_KEY'] ?? '',
        ],
        'timeout' => 10,
    ],

    'geo_api' => [
        'primary' => [
            'provider' => 'IP_API',
            'base_url' => 'http://ip-api.com/json',
            'timeout' => 5,
        ],
        'fallback' => [
            'provider' => 'IPINFO',
            'base_url' => 'https://ipinfo.io',
            'token' => $_ENV['IPINFO_TOKEN'] ?? '',
            'timeout' => 5,
        ],
    ],
];