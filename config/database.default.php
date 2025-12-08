<?php
return [
    'default'     => 'mysql',
    'connections' => [
        'mysql' => [
            'sticky'    => true,
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => 3306,
            'database'  => 'icms8',
            'username'  => 'root',
            'password'  => '123456',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => 'icms_',
        ]
    ]
];

// return [
//     'default'     => 'mysql',
//     'connections' => [
//         'mysql' => [
//             'sticky'    => true,
//             'driver'    => 'mysql',
//             'url'       => '',
//             'host'      => 'localhost',
//             'port'      => 3306,
//             'database'  => 'icms8',
//             'username'  => 'root',
//             'password'  => '123456',
//             'charset'   => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//             'prefix'    => 'icms_',
//         ],
//         'sqlite' => [
//             'sticky'    => true,
//             'driver'    => 'sqlite',
//             'host'      => 'localhost',
//             'port'      => 3306,
//             'database'  => 'icms8',
//             'username'  => 'root',
//             'password'  => '123456',
//             'charset'   => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//             'prefix'    => 'icms_',
//         ],
//         'pgsql' => [
//             'sticky'    => true,
//             'driver'    => 'pgsql',
//             'url'       => '',
//             'host'      => 'localhost',
//             'port'      => 3306,
//             'database'  => 'icms8',
//             'username'  => 'root',
//             'password'  => '123456',
//             'charset'   => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//             'prefix'    => 'icms_',
//         ],
//         'sqlsrv' => [
//             'sticky'    => true,
//             'driver'    => 'sqlsrv',
//             'url'       => '',
//             'host'      => '127.0.0.1',
//             'port'      => 1433,
//             'database'  => 'icms8',
//             'username'  => 'root',
//             'password'  => '123456',
//             'charset'   => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//             'prefix'    => 'icms_',
//         ],
//         'read_write' => [
//             'read' => [[
//                 'driver'    => 'mysql',
//                 'host'      => '127.0.0.2',
//                 'port'      => 3306,
//                 'database'  => 'icms8',
//                 'username'  => 'root',
//                 'password'  => '123456',
//                 'charset'   => 'utf8mb4',
//                 'collation' => 'utf8mb4_unicode_ci',
//                 'prefix'    => 'icms_',
//             ],[
//                 'driver'    => 'mysql',
//                 'host'      => '127.0.0.3',
//                 'port'      => 3306,
//                 'database'  => 'icms8',
//                 'username'  => 'root',
//                 'password'  => '123456',
//                 'charset'   => 'utf8mb4',
//                 'collation' => 'utf8mb4_unicode_ci',
//                 'prefix'    => 'icms_',
//             ]],
//             'write' => [
//                 'driver'    => 'mysql',
//                 'host'      => '127.0.0.1',
//                 'port'      => 3306,
//                 'database'  => 'icms8',
//                 'username'  => 'root',
//                 'password'  => '123456',
//                 'charset'   => 'utf8mb4',
//                 'collation' => 'utf8mb4_unicode_ci',
//                 'prefix'    => 'icms_',
//             ]
//         ],
//     ]
// ];
