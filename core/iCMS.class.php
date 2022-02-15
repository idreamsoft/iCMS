<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
// use iPHP\core\Adapter;
// use iPHP\core\File;
// use iPHP\core\Cache;
// use iPHP\core\Route;
// use iPHP\core\View;
// use iPHP\core\Script;

class iCMS
{
    public static $config    = array();

    public static function init()
    {
        self::config();

        define('iCMS_URL',       self::$config['route']['url']);
        define('iCMS_URL_HOST',  parse_url(iCMS_URL, PHP_URL_HOST));
        define('iCMS_PUBLIC_URL', self::$config['route']['public']);
        define('iCMS_ASSETS_URL', iCMS_URL . '/assets');
        define('iCMS_USER_URL',  self::$config['route']['user']);
        define('iCMS_FS_URL',    self::$config['FS']['url']);
        define('iCMS_FS_HOST',   parse_url(iCMS_FS_URL, PHP_URL_HOST));
        define('iCMS_API',       iCMS_PUBLIC_URL . '/api.php');
        define('iCMS_API_URL',   iCMS_API . '?app=');

        self::set_tpl_const();
        self::send_access_control();
    }
    /**
     * [config 对框架各系统进行配置]
     * @return [type] [description]
     */
    public static function config()
    {
        //获取配置
        $config = (array)iAPP::config();
        //config.php 中开启后 此处设置无效
        iDefine::debug($config['debug']);
        iDefine::timezone($config['time']);
        iDefine::route($config['route']);

        //多终端适配
        Adapter::init($config['template'], array(
            'redirect' => $config['route']['redirect'],
        ));
        //终端URL一致性
        Adapter::identity($config['route']);
        Adapter::identity($config['FS']);
        //文件系统
        FilesClient::init($config['FS']);
        //缓存系统
        Cache::init($config['cache']);
        //路由系统
        Route::init($config['route'], array(
            'routing'  => $config['routing'],
            'tag'      => $config['tag'], //标签配置
            'iurl'     => $config['iurl'], //应用路由定义
            'callback' => array(
                "domain" => array('nodeApp', 'domain'), //绑定域名回调
                'device' => array('Adapter', 'urls'), //设备网址
            )
        ));

        iDefine::info(
            Adapter::$device_name, //设备标识 iPHP_DEVICE
            Adapter::$IS_MOBILE //是否移动设设备 iPHP_MOBILE
        );

        //模板系统
        View::init(array(
            'template' => array(
                'device' => Adapter::$device_name,  //设备
                'dir'    => Adapter::$device_tpl,   //模板名
                'index'  => Adapter::$device_index, //模板首页
            ),
            'define' => array(
                'apps' => $config['apps'],
                'func' => 'content',
            ),
            'callback' => array(
                'output' => array('Adapter', 'output'),
            )
        ));
        //UI
        Script::set_dialog('title', $config['site']['name']);

        self::$config = $config;
        //加载应用定义，iCMS_APP_ARTICLE等
        iAPP::loadConf('app.define', true);
    }
    /**
     * 运行应用程序
     * @param string $app 应用程序名称
     * @param string $do 动作名称
     */
    public static function run($app = NULL, $do = NULL, $args = NULL, $prefix = iPHP_GET_PREFIX)
    {
        iAPP::$callback['app'] = array('ContentApp', 'run');
        iAPP::$callback['begin'][] = function () {
            iPHP::callback(array('UserApp', 'callback_begin'));
            View::setGlobal(array(
                "MOBILE" => iPHP_MOBILE,
                'COOKIE_PRE' => iPHP_COOKIE_PRE,
                'CSRF_TOKEN' => iPHP_COOKIE_PRE,
                'REFER' => iPHP_REFERER,
                "APP" => array(
                    'NAME' => iAPP::$NAME,
                    'DO' => iAPP::$DO,
                    'METHOD' => iAPP::$METHOD,
                )
            ));
            View::setGlobal(iAPP::$NAME, 'SAPI', true);
        };
        return iAPP::run($app, $do, $args, $prefix);
    }
    public static function API($app = NULL, $do = NULL, $args = NULL)
    {
        return self::run($app, $do, $args, 'API_');
    }
    public static function send_access_control()
    {
        @header("Access-Control-Allow-Origin: " . iCMS_URL);
        @header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
    }
    //向下兼容[暂时保留]
    public static function check_view_html($tpl, $C, $key)
    {
        return AppsApp::isHtml($tpl, $C, $key);
    }
    //向下兼容[暂时保留]
    public static function redirect_html($iurl)
    {
        return AppsApp::redirectToHtml($iurl);
    }
    //分页数缓存
    public static function page_total_cache($sql, $type = null, $cachetime = 3600)
    {
        return Paging::totalCache($sql, $type, $cachetime);
    }

    public static function set_tpl_const()
    {
        $APPID = array();
        foreach ((array)self::$config['apps'] as $_app => $_appid) {
            $APPID[strtoupper($_app)] = $_appid;
        }
        // $dir           = trim(Config::get('route.dir'),'/');
        // $template_url  = iCMS_URL.'/'.($dir?$dir.'/':'').'template';
        $template_url  = iCMS_URL . '/template';
        $URLS  = array(
            "template" => $template_url,
            "tpl"      => $template_url . '/' . View::$config['template']['dir'],
            "public"   => iCMS_PUBLIC_URL,
            "user"     => iCMS_USER_URL,
            "res"      => iCMS_FS_URL,
            "app"      => iCMS_URL . '/app',
            "assets"   => iCMS_URL . '/assets',
            "ui"       => iCMS_PUBLIC_URL . '/ui',
            "avatar"   => iCMS_FS_URL . 'avatar/',
        );

        View::setGlobal(array(
            'VERSION' => iCMS_VERSION,
            'API'     => iCMS_API,
            'SAPI'    => iCMS_API_URL,
            'DEVICE'  => iPHP_DEVICE,
            'CONFIG'  => self::$config,
            'URLS'    => $URLS,
            'APPID'   => $APPID
        ));
        Adapter::domain($URLS);
        self::$config['URLS'] = $URLS;
        View::call_func_system('site', true);
    }
}
