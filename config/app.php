<?php
declare(strict_types=1);

/*
 * Configuración principal de la aplicación.
 * AHORA MODIFICADO PARA LEER DEL ARCHIVO .ENV
 */

return [
    'app' => [
        // Nombre: Lee del .env o usa 'QuinielaPro' por defecto
        'name'     => $_ENV['APP_NAME'] ?? 'QuinielaPro',

        // Entorno: Lee del .env (ej: 'production')
        'env'      => $_ENV['APP_ENV'] ?? 'production',

        // Debug: Convierte el valor del .env a booleano real
        'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),

        // URL: Lee del .env (ej: https://mickeyquinielass.com)
        'url'      => $_ENV['APP_URL'] ?? 'http://localhost',

        'timezone' => 'America/Mexico_City',
        
        'whatsapp' => [
    'phone' => $_ENV['WHATSAPP_NUMBER'] ?? '+19513770102',
],
    ],


    /*
     * Configuración de base de datos MySQL.
     * CRUCIAL: Ahora lee $_ENV para conectarse con los datos de Hostinger.
     */
    'database' => [
        'driver'    => 'mysql',
        'host'      => $_ENV['DB_HOST'] ?? 'localhost',
        'port'      => (int)($_ENV['DB_PORT'] ?? 3306),
        'database'  => $_ENV['DB_DATABASE'] ?? '', // Se llenará con u131981230_... del .env
        'username'  => $_ENV['DB_USERNAME'] ?? '', // Se llenará con u131981230_... del .env
        'password'  => $_ENV['DB_PASSWORD'] ?? '', // Se llenará con tu contraseña del .env
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],

    /*
     * Teléfono principal de WhatsApp.
     */
    'whatsapp' => [
        'phone' => '+51904472452',
    ],

    /*
     * Rutas del sistema.
     * Usamos __DIR__ para ubicar las carpetas correctamente en el servidor.
     */
    'paths' => [
        'base_path'    => dirname(__DIR__),
        'public_path'  => dirname(__DIR__) . '/public', // O simplemente __DIR__ . '/../' si config está en public_html
        'storage_path' => dirname(__DIR__) . '/storage',
        'logs_path'    => dirname(__DIR__) . '/storage/logs',
        'cache_path'   => dirname(__DIR__) . '/storage/cache',
    ],

    /*
     * Ligas
     */
    'leagues' => [
        'default_slug' => 'liga-mx',
        'available'    => ['liga-mx', 'uefa-champions'],
    ],

    /*
     * Monedas
     */
    'currency_defaults' => [
        'MX'      => 'MXN',
        'US'      => 'USD',
        'DEFAULT' => 'USD',
    ],

    /*
     * APIs externas (Ahora preparadas para leer del .env si quisieras)
     */
    'sports_api' => [
        'primary' => [
            'provider' => 'THESPORTSDB',
            'base_url' => 'https://www.thesportsdb.com/api/v1/json',
            'api_key'  => $_ENV['SPORTSDB_KEY'] ?? 'TU_API_KEY_THESPORTSDB',
        ],
        'fallback' => [
            'provider' => 'API_FOOTBALL',
            'base_url' => 'https://v3.football.api-sports.io',
            'api_key'  => $_ENV['API_FOOTBALL_KEY'] ?? 'TU_API_KEY_API_FOOTBALL',
        ],
        'timeout' => 10,
    ],

    'geo_api' => [
        'primary' => [
            'provider' => 'IP_API',
            'base_url' => 'http://ip-api.com/json',
            'timeout'  => 5,
        ],
        'fallback' => [
            'provider' => 'IPINFO',
            'base_url' => 'https://ipinfo.io',
            'token'    => $_ENV['IPINFO_TOKEN'] ?? '',
            'timeout'  => 5,
        ],
    ],
];