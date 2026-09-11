<?php

/*
|--------------------------------------------------------------------------
| Configuración de base de datos
|--------------------------------------------------------------------------
| Este proyecto se conecta EXCLUSIVAMENTE a SQL Server (Microsoft) alojado en
| Hostinger / DuckDNS (nominarc4.duckdns.org:1433) usando el driver "sqlsrv".
|
| La extensión PHP "pdo_sqlsrv" / "sqlsrv" debe estar habilitada en el server
| de Hostinger. Las credenciales se leen desde variables de entorno (.env)
| para NO exponerlas en el repositorio de GitHub.
*/

use Illuminate\Support\Str;

return [

    'default' => env('DB_CONNECTION', 'sqlsrv'),

    'connections' => [

        // Driver SQL Server vía pdo_sqlsrv (recomendado en Hostinger + SQL Server)
        'sqlsrv' => [
            'driver'         => 'sqlsrv',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', 'nominarc4.duckdns.org'),
            'port'           => env('DB_PORT', '1433'),
            'database'       => env('DB_DATABASE', 'ERPBMS'),
            'username'       => env('DB_USERNAME', ''),
            'password'       => env('DB_PASSWORD', ''),
            'charset'        => 'utf8',
            'prefix'         => '',
            'prefix_indexes' => true,
            'encrypt'        => env('DB_ENCRYPT', 'no'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'true'),
            'options'        => extension_loaded('pdo_sqlsrv')
                ? array_filter([
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ])
                : [],
        ],

        // Conexión alternativa idéntica, útil para pruebas o réplicas
        'sqlsrv_alt' => [
            'driver'         => 'sqlsrv',
            'host'           => env('SQLSRV_HOSTNAME', env('DB_HOST', 'nominarc4.duckdns.org')),
            'port'           => env('SQLSRV_PORT', '1433'),
            'database'       => env('SQLSRV_DATABASE', 'ERPBMS'),
            'username'       => env('SQLSRV_USERNAME', env('DB_USERNAME', '')),
            'password'       => env('SQLSRV_PASSWORD', env('DB_PASSWORD', '')),
            'charset'        => 'utf8',
            'prefix'         => '',
            'encrypt'        => 'no',
            'trust_server_certificate' => env('SQLSRV_TRUST_CERT', 'true'),
        ],

    ],

    // Tablas usadas por Laravel para colas/potencias (se crean vía migraciones)
    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

];