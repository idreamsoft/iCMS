<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ConfigView
{
    public static $VAPPID = null;
    /**
     * 当 配置为 虚拟应用时
     * 设置应用目录
     *
     * @var [type]
     */
    public static $APPID = null;
    public static $ISALL = false;

    private static $SETTING = [];

    public static function fetch($name, $ret = false)
    {
        return self::display(Config::VAPPID, $name, $ret);
    }

    public static function set($key, $value = null)
    {
        self::$SETTING[$key] = $value;
    }

    public static function get($key, $default = null)
    {
        // self::$SETTING['title'] ?: $apps['title'] . '系统';
        // $value = self::$SETTING[$key];
        // if(is_null($value) && !is_null($default)){
        //     $value = $default;
        // }
        // return $value;
        return isset(self::$SETTING[$key]) ? self::$SETTING[$key] : $default;
    }

    /**
     * app 其它应用配置接口
     * @param  integer $appid [应用ID]
     * @param  [sting] $name   [应用名]
     */
    public static function display($appid = 0, $name = null, $ret = false, $suffix = 'config')
    {
        $sname = $name;
        $name === null && $sname = $name = Admincp::$APP_NAME;
        self::$SETTING['name'] === false && $sname = null;

        if (empty($appid) && self::$SETTING['appid']) {
            $appid = self::$SETTING['appid'];
        }
        empty($appid) && iPHP::alert('application config miss [appid]!');
        $config = Config::data($appid, $sname) ?: [];

        if ($ret || Request::isAjax()) {
            return $config;
        }

        if ($appid == Config::VAPPID && ConfigView::$APPID) {
            $appid = ConfigView::$APPID;
        }
        $apps = Apps::getData($appid);
        $navData = Menu::getNav();
        $title = $navData[1]['name'];
        empty($title) && $title = self::get('title', $apps['title'] . '系统');
        $subTitle = self::get('subTitle', $name);
        $action = self::get('action', 'config');
        $icon = self::get('icon');
        $submitBtn = self::get('submitBtn', true);
        $header_enable = self::get('header_enable');
        $header_class = self::get('header_class');
        $content_class = self::get('content_class');
        $file = self::get('file', $apps['app']);

        ob_start();
        /**
         * AdmincpView::display('article.config', 'article');
         * return //app/article/views/app/config/article.html
         */

        include AdmincpView::display(
            sprintf('app/%s/%s', $suffix, $name),
            $apps['app']
        );
        $content = AdmincpView::html();
        //app/aaa/views/app/config/article.1.html
        //app/bbb/views/app/config/article.2.html
        $extends = AdmincpView::getExtends(
            sprintf('app/%s/%s', $suffix, $file),
            $apps['app']
        ); //test.config.asd.html

        $tabs = [];
        if ($extends) {
            $extends = current($extends);
            $tabs = $extends['tabs'];
        }
        $footer = AdmincpView::display(
            sprintf('app/%s/footer', $suffix),
            $apps['app'],
            false
        );
        $block_footer = AdmincpView::display(
            sprintf('app/%s/block_footer', $suffix),
            $apps['app'],
            false
        );
        // var_dump($extends);
        include AdmincpView::display('app.config', 'config');
    }
}
