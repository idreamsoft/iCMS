<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class iCMS
{
    public static $config = [];

    public static function bootstrap($ini = null)
    {
        // $iniArr = Helper::ini($ini);
        Application::init();
        self::config();
        self::defined();
        self::appDefines();
        self::accessControl();
    }

    public static function appDefines()
    {
        $path = iPHP_APP_CONFDIR . '/app.define.php';
        if (is_file($path)) {
            require_once $path;
        }
    }

    public static function defined()
    {
        // var_dump(Adapter::$main_url,self::$config['route']['url']);

        define('iCMS_URL', self::$config['route']['url']);
        define('iCMS_URL_HOST', parse_url(iCMS_URL, PHP_URL_HOST));
        define('iCMS_URL_404', self::$config['route']['notfound']); //404定义

        $public_url = rtrim(self::$config['route']['public'], '/');
        $user_url = rtrim(self::$config['route']['user'], '/');
        $api_base = str_replace(Adapter::$main_url, iCMS_URL, $public_url);
        if (Adapter::$device['agent'] != 'DIR') {
            $public_url = str_replace(Adapter::$main_url, iCMS_URL, $public_url);
            $user_url = str_replace(Adapter::$main_url, iCMS_URL, $user_url);
        }
        define('iCMS_PUBLIC_URL', $public_url);
        // define('iCMS_ASSETS_URL', iCMS_URL . '/assets');
        define('iCMS_USER_URL', $user_url);
        define('iCMS_FILE_URL', self::$config['file']['url'] ?: '');
        define('iCMS_FILE_HOST', parse_url(iCMS_FILE_URL, PHP_URL_HOST));
        define('iCMS_API_NAME', '/api.php');
        // define('iCMS_API_NAME', self::$config['route']['rewrite']?'/api':'/api.php');
        define('iCMS_API_BASE', $api_base);
        define('iCMS_API_URL', iCMS_API_BASE . iCMS_API_NAME);
    }

    /**
     * config 对框架各系统进行配置
     */
    public static function config()
    {
        self::$config = Application::config();
        //调试
        iDefine::debug(self::$config['debug']);
        //时区
        iDefine::timezone(self::$config['time']);
        //文件系统
        FilesClient::init(self::$config['file']);
        //缓存系统
        Cache::init(self::$config['cache']);
        //加解密
        Crypt::init(iPHP_KEY);
        //多终端适配
        Adapter::init(self::$config['template']);
        iDefine::device(Adapter::$device);
        iDefine::mobile(Adapter::$IS_MOBILE);
        iDefine::framework(self::$config['route']);
        empty(self::$config['hooks']) && self::$config['hooks'] = [];
        //路由系统
        self::$config['route']['url'] = Adapter::$device['domain'];
        if (Adapter::$device['agent'] != 'DIR') {
            self::$config['route']['url'] = rtrim(self::$config['route']['url'], '/');
        }
        Route::init(self::$config['route'], ['device'=>Adapter::$device]);

        //模板系统
        View::init([
            'template' => Adapter::$device,
            'apps'     => self::$config['apps'],
            'callback' => [
                'content'  => 'contentFunc',
                'output'   => self::$config['hooks']['run']['view'],
            ]
        ]);
    }

    /**
     * 运行应用程序
     * @param string $app 应用程序名称
     * @param string $do 动作名称
     */
    public static function run($app = null, $do = null, $args = null, $prefix = null)
    {
        try {
            Session::init('cms');
            AppsHook::init();
            return Application::run($app, $do, $args, $prefix);
        } catch (\Exception $ex) {
            return self::exRespond($ex);
        }
    }
    public static $exDebug = false;

    public static function exRespond($ex)
    {
        $url = '';
        $exvar = iPHP::getException($ex);
        $message = $exvar['message'];
        $trace = $exvar['trace'];
        $code = $exvar['code'];
        $state = $exvar['state'];

        if (iPHP_DEBUG || self::$exDebug) {
            if ($ex instanceof PDOException) {
                $code = -999;
                list($pdocode, $message, $driver) = DB::getError($ex);
                // throw $ex; //拋出错误由iDebug处理
            }
            if (iPHP_DEBUG_TRACE) {
                $message .= $exvar['trace'];
            }
        }

        if (is_array($state)) {
            list($url, $state) = $state;
        }
        if (iPHP_SHELL) {
            $message = str_replace('<br />', PHP_EOL, $message);
            $message = html2text($message) . PHP_EOL;
            echo Utils::cliPrints($message, 's');
            exit();
        }

        if (Request::isAjax() || Request::isPost()) {
            return iJson::fail($message, $url, $code, $state);
        }
        
        $url && Utils::jumpto($url);
        exit($message);
    }

    public static function cli($app = null, $do = null, $args = null)
    {
        if (!iPHP_SELF) {
            return;
        }

        $prefix = iPHP_SHELL_PREFIX;
        return self::run($app, $do, $args, $prefix);
    }

    public static function api($app = null, $do = null, $args = null)
    {
        $prefix = iPHP_API_PREFIX;
        return self::run($app, $do, $args, $prefix);
    }

    public static function accessControl()
    {
        @header('Access-Control-Allow-Origin: ' . iCMS_URL);
        @header('Access-Control-Allow-Headers: X-Requested-With');
    }
}
