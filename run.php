<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

$_SERVER = array_merge($_SERVER, [
    'SERVER_SOFTWARE' => 'iCMS SHELL/8.0.0 (CLI)', // 服务器软件
    'SERVER_NAME'     => 'localhost',               // 服务器名称
    'SERVER_ADDR'     => '127.0.0.1',               // 服务器 IP 地址
    'SERVER_PORT'     => '80',                      // 服务器端口
    'REMOTE_ADDR'     => '127.0.0.1',             // 客户端 IP 地址
    'DOCUMENT_ROOT'   => __DIR__,         // 文档根目录
    'REQUEST_SCHEME'  => 'run',                 // 请求协议 (http 或 https)
    'REQUEST_METHOD'  => 'shell',                  // 请求方法 (GET, POST, etc.)
    'REQUEST_URI'     => '/run.php',              // 请求的 URI
    // 'QUERY_STRING' => '',       // 查询字符串
    'HTTP_HOST'            => 'localhost',                 // HTTP Host 头
    'HTTP_USER_AGENT'      => 'iCMS SHELL/5.0',         // 用户代理
    'HTTP_ACCEPT'          => 'text/html',               // Accept 头
    'HTTP_ACCEPT_LANGUAGE' => 'en-US',          // Accept-Language 头
    'HTTP_CONNECTION'      => 'keep-alive',          // Connection 头
    'SCRIPT_FILENAME'      => __FILE__, // 脚本文件名
    'SCRIPT_NAME'          => '/run.php',              // 脚本名称
    'PHP_SELF'             => '/run.php',                 // 当前脚本路径
]);

require __DIR__ . '/iCMS.php';

function shutdown_pid()
{
    if (!isset($GLOBALS['shutdown_pid'])) {
        return 'Shutdown PID not defined.';
    }

    $pfile = $GLOBALS['shutdown_pid'];
    if ($pfile && file_exists($pfile)) {
        echo "$pfile delete;" . PHP_EOL;
        @unlink($pfile);
    } else {
        return 'PID file does not exist.';
    }
}
if (!function_exists('pcntl_signal')) {
    function pcntl_signal($a = null, $b = null)
    {
        return;
    }
}
declare(ticks=1);
defined('SIGTERM') or define('SIGTERM', 15);
defined('SIGHUP') or define('SIGHUP', 1);
defined('SIGINT') or define('SIGINT', 2);

pcntl_signal(SIGTERM, 'sig_handler');
pcntl_signal(SIGHUP, 'sig_handler');
pcntl_signal(SIGINT, 'sig_handler');
//信号处理函数
function sig_handler($signo)
{
    switch ($signo) {
        case SIGTERM:
            // 处理kill
            echo PHP_EOL . 'kill' . PHP_EOL;
            shutdown_pid();
            exit;
            break;
        case SIGHUP:
            //处理SIGHUP信号
            break;
        case SIGINT:
            //处理ctrl+c
            echo PHP_EOL . 'ctrl+c' . PHP_EOL;
            shutdown_pid();
            exit;
            break;
        default:
            // 处理所有其他信号
    }
}

function shutdown()
{
    shutdown_pid();
}

register_shutdown_function('shutdown');

Utils::buffer();
iCMS::cli();
