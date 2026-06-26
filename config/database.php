<?php
declare(strict_types=1);


return [
    'driver'   => getenv('DB_CONNECTION') !== false ? (string)getenv('DB_CONNECTION') : 'mysql',
    'host'     => getenv('DB_HOST')       !== false ? (string)getenv('DB_HOST')       : 'localhost',
    'port'     => getenv('DB_PORT')       !== false ? (int)getenv('DB_PORT')         : 3306,
    'dbname'   => getenv('DB_DATABASE')   !== false ? (string)getenv('DB_DATABASE')   : 'u131981230_quinielasvilla',
    'username' => getenv('DB_USERNAME')   !== false ? (string)getenv('DB_USERNAME')   : 'u131981230_dev_quiniela',
    'password' => getenv('DB_PASSWORD')   !== false ? (string)getenv('DB_PASSWORD')   : 'quinielasE$4@',
    'charset'  => getenv('DB_CHARSET')    !== false ? (string)getenv('DB_CHARSET')    : 'utf8mb4',
];
