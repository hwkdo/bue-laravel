<?php

// config for Hwkdo/BueLaravel
return [
    'database' => [
        'connection' => env('BUEDB_CONNECTION', 'universal-utf8'),
        'admin_connection' => env('BUE_ADMIN_CONNECTION', 'universalAdmin'),
        'admin' => [
            'driver' => 'oracle',
            'host' => env('BUE_ADMIN_HOST', env('BUE_HOST', '10.37.100.17')),
            'port' => env('BUE_ADMIN_PORT', env('BUE_PORT', '1521')),
            'database' => env('BUE_ADMIN_DATABASE', env('BUE_DATABASE', 'hwkDO.universal')),
            'service_name' => env('BUE_ADMIN_SERVICE_NAME', env('BUE_SERVICE_NAME', 'hwkDO.universal')),
            'username' => env('BUE_ADMIN_USERNAME'),
            'password' => env('BUE_ADMIN_PASSWORD'),
            'charset' => env('BUE_ADMIN_CHARSET', 'WE8ISO8859P1'),
            'prefix' => '',
        ],
    ],
];
