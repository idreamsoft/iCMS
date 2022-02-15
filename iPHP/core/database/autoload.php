<?php

// function classLoader($class)
// {
//     $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
//     $path = str_replace('DataBase' . DIRECTORY_SEPARATOR, '', $path);

//     $file = __DIR__ . '/src/' . $path . '.php';

//     if (file_exists($file)) {
//         require_once $file;
//     }
// }
// spl_autoload_register('classLoader');
/**
 * 此数据库操作类基础来源于laravel框架中的数据库类
 * 基于laravel的数据库类，及相关说明文档，相关著作权归原作者所有。
 * 本框架根据自己的需求重写了DB.php及Builder.php
 */
require_once __DIR__ .'/src/Arr.php';
require_once __DIR__ .'/src/Connection/Connection.php';
require_once __DIR__ .'/src/Connection/ConnectionFactory.php';
require_once __DIR__ .'/src/Connection/MySqlConnection.php';
require_once __DIR__ .'/src/Connection/PostgresConnection.php';
require_once __DIR__ .'/src/Connection/SQLiteConnection.php';
require_once __DIR__ .'/src/Connection/SqlServerConnection.php';
require_once __DIR__ .'/src/Connectors/Connector.php';
require_once __DIR__ .'/src/Connectors/ConnectorInterface.php';
require_once __DIR__ .'/src/Connectors/MySqlConnector.php';
require_once __DIR__ .'/src/Connectors/PostgresConnector.php';
require_once __DIR__ .'/src/Connectors/SQLiteConnector.php';
require_once __DIR__ .'/src/Connectors/SqlServerConnector.php';
require_once __DIR__ .'/src/ConfigurationUrlParser.php';
require_once __DIR__ .'/src/Manager.php';

require_once __DIR__ .'/src/Builder.php';
require_once __DIR__ .'/src/DB.php';

