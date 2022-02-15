<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
ini_set('display_errors', 'ON');
error_reporting(E_ALL & ~E_NOTICE);

define('iPHP', TRUE);
define('iPHP_APP', 'iCMS'); //应用名
define('iPHP_DEBUG', true);
define('iPHP_APP_MAIL', 'support@iCMSdev.com');
define('iPHP_APP_SITE', iPHP_APP);
define('iPHP_KEY', createRandom(64));

//加载iPHP框架
require_once __DIR__ . '/../iPHP/bootstrap.php';

if (Request::post('action') == 'install') {
    $db_host     = trim(Request::post('DB_HOST'));
    $db_user     = trim(Request::spost('DB_USER'));
    $db_password = trim(Request::post('DB_PASSWORD'));
    $db_name     = trim(Request::post('DB_NAME'));
    $db_prefix   = trim(Request::post('DB_PREFIX'));
    $db_port     = (int)Request::spost('DB_PORT');
    $db_charset  = trim(Request::spost('DB_CHARSET'));
    $db_engine   = trim(Request::spost('DB_ENGINE'));

    $route_url      = trim(Request::post('ROUTE_URL'), '/');
    $admin_name     = trim(Request::post('ADMIN_NAME'));
    $admin_password = trim(Request::post('ADMIN_PASSWORD'));
    $setup_mode     = trim(Request::post('SETUP_MODE'));

    $db_prefix = rtrim($db_prefix, '_') . '_';
    define('DB_PREFIX', $db_prefix);

    $lock_file = sprintf('%s/config/install.lock', iPHP_PATH);
    file_exists($lock_file) && iJson::error('请先删除 config/install.lock 文件。');

    $db_host or iJson::error("请填写数据库服务器地址", '#DB_HOST');
    $db_user or iJson::error("请填写数据库用户名", '#DB_USER');
    $db_password or iJson::error("请填写数据库密码", '#DB_PASSWORD');
    is_numeric($db_port) or iJson::error("数据库端口出错", '#DB_PORT');
    $db_name or iJson::error("请填写数据库名", '#DB_NAME');
    preg_match('/^[a-zA-z0-9\_]+$/is', $db_name) or iJson::error("数据库名包含非法字符，请返回修改", '#DB_NAME');
    strstr($db_prefix, '.') && iJson::error("您指定的数据表前缀包含点字符，请返回修改", '#DB_PREFIX');
    preg_match('/^[a-zA-z0-9\_]+$/is', $db_prefix) or iJson::error("您指定的数据表前缀包含非法字符，请返回修改", '#DB_PREFIX');
    in_array(strtolower($db_charset), array('utf8', 'utf8mb4')) or iJson::error("非法字符集", '#DB_CHARSET');
    $admin_name or iJson::error("请填写超级管理员账号", '#ADMIN_NAME');
    $admin_password or iJson::error("请填写超级管理员密码", '#ADMIN_PASSWORD');
    strlen($admin_password) < 6 && iJson::error("超级管理员密码不能小于6位字符", '#ADMIN_PASSWORD');
    //检测数据库文件
    $table_sql_file = __DIR__ . '/data/iCMS.sql';
    $data_sql_file  = __DIR__ . '/data/iCMS-data.sql';
    is_readable($table_sql_file) or iJson::error('数据库文件[iCMS.sql]不存在或者读取失败');
    is_readable($data_sql_file)  or iJson::error('数据库文件[iCMS-data.sql]不存在或者读取失败');

    $config = [
        'default' => 'mysql',
        'connections' => [
            'mysql' => array(
                'sticky'    => true,
                'driver'    => 'mysql',
                'url'       => '',
                'host'      => $db_host,
                'port'      => $db_port,
                'database'  => $db_name,
                'username'  => $db_user,
                'password'  => $db_password,
                'charset'   => $db_charset,
                'collation' => $db_charset . '_unicode_ci',
                'prefix'    => $db_prefix,
            )
        ]
    ];

    try {
        // var_dump($config);
        $path = sprintf('%s/%s/database.php', iPHP_CONFIG_DIR, iAPP::site());
        $content = sprintf("<?php\nreturn %s;", var_export($config, true));
        File::put($path, $content, false);
        $CREATE_DATABASE = (int)Request::post('CREATE_DATABASE');
        if ($CREATE_DATABASE) {
            //选择创建数据库时，默认连接不带数据库名配置
            unset($config['connections']['mysql']['database']);
        }
        DB::config($config);
        DB::version();
        $CREATE_DATABASE && createDatabase($db_name, $db_charset);
    } catch (\Exception $ex) {
        $msg = $ex->getMessage();
        $code = $ex->getCode();
        if ($text = DB::errorText($code)) {
            $msg = sprintf('%s %s <hr />%s', $text, $state, $msg);
        }
        iJson::error($msg . '[ex0001]', 'DB');
    }

    //开始安装 数据库 结构

    $TableSql = File::get($table_sql_file);
    $DataSql = File::get($data_sql_file);

    $db_charset == "utf8mb4" &&  transCharset($TableSql, $db_charset, $db_engine);
    $db_engine == "MyISAM" && InnoDB2MyISAM($TableSql);

    // DB::debug();

    $DROP_TABLE_IF_EXISTS = $setup_mode == 'cover' ? true : false; //覆盖安装
    try {
        runQuery($TableSql, DB_PREFIX, $DROP_TABLE_IF_EXISTS); //创建数据表
    } catch (\Exception $ex) {
        $msg = $ex->getMessage();
        $code = $ex->getCode();
        $error = DB::errorInfo();
        if ($text = DB::errorText($error[1])) {
            $msg = sprintf('%s<hr />%s', $text, $msg);
        } else {
            // $msg .= sprintf('%s', var_export($error, true));
        }
        iJson::error($msg . '[ex0002]', 'DB');
    }
    try {
        runQuery($DataSql, DB_PREFIX); //导入默认数据
    } catch (\Exception $ex) {
        $msg = $ex->getMessage();
        $code = $ex->getCode();
        $error = DB::errorInfo();
        if ($text = DB::errorText($error[1])) {
            $msg = sprintf('%s<hr />%s', $text, $msg);
        } else {
            // $msg .= sprintf('%s', var_export($error, true));
        }
        iJson::error($msg . '[ex0003]', 'DB');
    }
    //数据导入完成

    //开始更新缓存
    require_once iPHP_PATH . '/iCMS.php';

    //设置超级管理员
    MemberModel::update([
        'account' => $admin_name,
        'password' => Member::password($admin_password),
    ], '1');
    UserModel::update([
        'account' => $admin_name,
        'password' => User::password(random(6)),
    ], '1');

    //配置程序
    // define('iPHP_APP_CONFIG', File::path(iPHP_CONFIG_DIR . '/' . iPHP_APP . '/config.php')); //网站配置文件
    Cache::init(array(
        'engine'     => 'file',
        'prefix'     => iPHP_APP,
        'host'       => '',
        'time'       => '300',
        'compress'   => '1',
        'page_total' => '300',
    ));

    $config = Config::get();
    $config['route']['url']    = $route_url;
    $config['route']['public'] = $route_url . '/public';
    $config['route']['user']   = $route_url . '/user';
    $config['route']['404']    = $route_url . '/public/404.htm';
    $config['FS']['url']        = $route_url . '/res/';
    $config['template']['mobile']['domain'] = $route_url;
    foreach ($config as $n => $v) {
        Config::set($v, $n, 0);
    }
    Config::cache();
    cleanPatchFiles();
    createSecretKey();
    //写入数据库配置<hr />开始安装数据库<hr />数据库安装完成<hr />设置超级管理员<hr />更新网站缓存<hr />
    File::put($lock_file, 'iCMS.' . time(), false);
    File::rmdir(__DIR__);
    iJson::success();
}
function createRandom($length)
{
    $hash  = '';
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
    $max   = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $chars[mt_rand(0, $max)];
    }
    return $hash;
}
function createSecretKey()
{
    $path = sprintf('%s/config/secretkey.php', iPHP_PATH);
    $content = File::get($path);
    $content = preg_replace("/define\('iPHP_KEY',\s*'.*?'\)/is", "define('iPHP_KEY','" . iPHP_KEY . "')", $content);
    File::put($path, $content, false);
}
function createDatabase($db_name, $db_charset)
{
    $CREATE_DATABASE = (int)Request::post('CREATE_DATABASE');
    $hasDatabase = false;
    if ($databases = DB::select("show databases")) {
        $databases = array_column($databases, 'Database');
        if (array_search($db_name, $databases) === false) {
            $hasDatabase = true;
        }
    }
    if ($CREATE_DATABASE && $hasDatabase) {
        $sql = sprintf(
            'CREATE DATABASE `%s` CHARACTER SET %s COLLATE %s_unicode_ci',
            $db_name,
            $db_charset,
            $db_charset
        );
        DB::exec($sql);
    }
    DB::exec(sprintf('use `%s`', $db_name));
}

function cleanPatchFiles()
{
    $files = glob(iPHP_PATH . "app/patch/files/*.*");
    if (is_array($files)) foreach ($files as $file) {
        File::rm($file);
    }
}

function InnoDB2MyISAM(&$sql)
{
    $sql = str_replace('ENGINE=InnoDB', 'ENGINE=MyISAM', $sql);
}
function transCharset(&$sql, $charset, $engine)
{
    $sql = str_replace('SET NAMES utf8', 'SET NAMES ' . $charset, $sql);
    $sql = str_replace('CHARSET=utf8;', 'CHARSET=' . $charset . ';', $sql);
}
