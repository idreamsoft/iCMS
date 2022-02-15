<?php

/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
ini_set('display_errors', 'ON');
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
header('Content-Type: text/html; charset=UTF-8');
version_compare('5.6', PHP_VERSION, '>') && die('iPHP requires PHP version 5.6 or higher. You are running version ' . PHP_VERSION . '.');

require_once __DIR__ . '/version.php'; //框架版本
require_once __DIR__ . '/define.php'; //常量定义
require_once __DIR__ . '/compat.php'; //兼容性处理
require_once __DIR__ . '/exception.php'; //异常类
require_once __DIR__ . '/function.php'; //常用函数
require_once __DIR__ . '/core/Helper.php'; //助手类
require_once __DIR__ . '/core/iDebug.php'; //调试类
require_once __DIR__ . '/iAPP.php'; //应用类
require_once __DIR__ . '/iPHP.php'; //框架主类

iPHP::bootstrap();
