<?php
return array(
    'default' => 'mysql',
    'connections' =>
    array(
        'mysql' =>
        array(
            'sticky' => true,
            'driver' => 'mysql',
            'url' => '',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'icms8',
            'username' => 'root',
            'password' => '123456',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => 'icms_',
        ),
    ),
);
