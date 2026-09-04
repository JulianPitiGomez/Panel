<?php
// config/database.php
// Reemplaza el archivo completo de config/database.php de Laravel

return [

    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [

        // ========================================
        // DB PRINCIPAL DEL CRM (lectura/escritura)
        // Aquí viven: users, tickets, onboardings,
        // invoices, contacts (migrados), etc.
        // ========================================
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'crm_app'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => 'InnoDB',
        ],

        // ========================================
        // DB BCNSOFT (lectura + escritura)
        // Tablas: config_clientes, config_deuda,
        // config_pagos, config_cajas, etc.
        // ========================================
        'bcnsoft' => [
            'driver' => 'mysql',
            'host' => env('DB_BCNSOFT_HOST', '127.0.0.1'),
            'port' => env('DB_BCNSOFT_PORT', '3306'),
            'database' => env('DB_BCNSOFT_DATABASE', 'bcn_config'),
            'username' => env('DB_BCNSOFT_USERNAME', 'root'),
            'password' => env('DB_BCNSOFT_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false, // Legacy DB, strict OFF
            'engine' => null,
        ],

        // ========================================
        // DB PEDIDOSBCN (lectura + escritura)
        // Tablas: comercios, comercios_pagos,
        // pedidos, ventas_encab, perfiles, etc.
        // ========================================
        'pedidosbcn' => [
            'driver' => 'mysql',
            'host' => env('DB_PEDIDOSBCN_HOST', '127.0.0.1'),
            'port' => env('DB_PEDIDOSBCN_PORT', '3306'),
            'database' => env('DB_PEDIDOSBCN_DATABASE', 'pedidospru'),
            'username' => env('DB_PEDIDOSBCN_USERNAME', 'root'),
            'password' => env('DB_PEDIDOSBCN_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false, // Legacy DB, strict OFF
            'engine' => null,
        ],

    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],

];
