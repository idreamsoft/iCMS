<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');
// use iPHP\core\Security;

class AdmincpView
{
    public static $DIR   = 'views';
    public static $PATH  = null;
    public static $EXT   = '.html';
    public static $ASSIGN   = array();
    public static $IS_MODAL   = false;
    public static $CONFIG     = array(

        'side.overlay'     => null,    //侧栏 留空默认关闭 side-overlay-hover 鼠标悬停 side-overlay-o 默认打开
        'side.scroll'      => true,    //侧栏滚动条使用自定义样式
        'sidebar'          => true,    //是否显示侧边栏
        'sidebar.r'        => false,   //侧边栏右侧
        'sidebar.mini'     => false,    //侧边栏最小化
        'sidebar.dark'     => true,    //暗色侧边栏
        'page.overlay'     => true,
        'page.header'      => false,    //null移除页头 true 页头固定 false 页头滚动
        'page.header.dark' => false,    //暗色页头
        'page.common'      => true,
        'modal.dialog'     => true,
        'page.footer'      => true,
        'main.content'     => null,  //默认为空 main-content-boxed 最大100% main-content-narrow 最大95%
        'breadcrumb'       => true,  //是否显示 面包屑
        'page.classes'     => null,
        'script.init'      => true,
        'vue'              => true,
        'script'           => true,
    );

    public static function isModal()
    {
        self::$IS_MODAL = Request::get('modal') ? true : false;
        return self::$IS_MODAL;
    }
    public static function page_classes()
    {
        $sidebar = Config::get('admincp.sidebar');
        $sidebar['enable'] && self::$CONFIG['sidebar'] = $sidebar['enable'];
        $sidebar['right'] && self::$CONFIG['sidebar.r'] = $sidebar['right'];
        $sidebar['mini'] && self::$CONFIG['sidebar.mini'] = $sidebar['mini'];
        // [enable]
        $page_classes = array('enable-cookies');
        if (self::isModal()) {
            self::set('modal.dialog', true);
            self::set(array('page.header', 'page.common', 'sidebar', 'breadcrumb', 'page.footer'), null);
            $page_classes[] = 'modal-enable';
        }
        self::$CONFIG['side.overlay'] && $page_classes[] = self::$CONFIG['side.overlay'];
        self::$CONFIG['side.scroll']  && $page_classes[] = 'side-scroll';
        if (self::$CONFIG['page.header'] !== null) {
            self::$CONFIG['sidebar']  && $page_classes[] = 'sidebar-o';
            self::$CONFIG['sidebar.r']    && $page_classes[] = 'sidebar-r';
            self::$CONFIG['sidebar.mini'] && $page_classes[] = 'sidebar-mini';
            self::$CONFIG['sidebar.dark'] && $page_classes[] = 'sidebar-dark';
        }
        self::$CONFIG['page.overlay']      && $page_classes[] = 'enable-page-overlay';
        self::$CONFIG['page.header']       && $page_classes[] = 'page-header-fixed';
        self::$CONFIG['page.header.dark']  && $page_classes[] = 'page-header-dark';
        self::$CONFIG['main.content']      && $page_classes[] = self::$CONFIG['main.content'];
        self::$CONFIG['page.classes'] = implode(' ', $page_classes);
        return self::$CONFIG['page.classes'];
    }
    public static function get($key = null)
    {
        return $key ? self::$CONFIG[$key] : self::$CONFIG;
    }
    public static function set($keys = null, $value = null)
    {
        if (is_array($keys)) {
            foreach ($keys as $k) {
                self::$CONFIG[$k] = $value;
            }
        } else {
            self::$CONFIG[$keys] = $value;
        }
    }
    public static function assign($key = null, $value = null)
    {
        if ($key && $value) {
            self::$ASSIGN[$key] =  $value;
        } elseif (is_array($key)) {
            self::$ASSIGN = $key;
        }
    }

    public static function fetch($name = null, $dir = null)
    {
        ob_start();
        extract(self::$ASSIGN);
        include self::display($name, $dir, $flag);
        return self::html();
    }
    public static function html()
    {
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
    public static function show($name, $app, $data)
    {
        include self::display($name, $app);
    }
    public static function view($name = null, $dir = null)
    {
        return self::display($name, $dir);
    }
    public static function scan($fn, $dir = '*', $data = null)
    {
        $array = array();
        // */views/*.html
        $pattern = iAPP::path($dir) . "views/{$fn}.html";
        $files = (array) glob($pattern);
        return $files;
        // foreach ($files as $key => $path) {
        //     include $path;
        // }
    }
    public static function display($name = null, $dir = null)
    {
        self::$CONFIG['name'] && $name = self::$CONFIG['name'];
        if ($dir === null && self::$CONFIG['dir']) {
            $dir = self::$CONFIG['dir'];
        }
        if ($name === null && Admincp::$APP_NAME) {
            $name = Admincp::$APP_NAME;
            Admincp::$APP_DO && $name .= '.' . Admincp::$APP_DO;
        }

        // if(defined('ADMINCP_ONEUI')){
        // 	self::$DIR = 'views';
        // 	self::$EXT = '.html';
        // }
        //
        //app/views
        $tpl = rtrim(Admincp::$APP_PATH,'/') . '/' . self::$DIR;
        //dir/views
        $dir && $tpl = iAPP::path($dir). self::$DIR;
        //app/views/index.my.html 1
        $my   = $tpl . '/' . $name . '.my' . self::$EXT;
        //app/views/index.html 2
        $html = $tpl . '/' . $name . self::$EXT;
        //app/views/index.php 3
        $php  = $tpl . '/' . $name . '.php';
        self::$PATH  = file_exists($my) ? $my : (file_exists($php) ? $php : $html);
        iDebug::$DATA[__CLASS__][] = self::$PATH;
        return self::$PATH;
    }

    public static function head()
    {
        self::$CONFIG['head:begin'] &&
            include self::display("head.begin", self::$CONFIG['head:begin']);

        include self::display("base/head", 'admincp', true);

        self::$CONFIG['head:after'] &&
            include self::display("head.after", self::$CONFIG['head:after']);
    }
    public static function main()
    {
    }
    public static function foot()
    {
        self::$CONFIG['foot:begin'] &&
            include self::display("foot.begin", self::$CONFIG['foot:begin']);

        include self::display("base/foot", 'admincp');

        self::$CONFIG['foot:after'] &&
            include self::display("foot.after", self::$CONFIG['foot:after']);
    }
    public static function url($query, $a = null)
    {
        is_string($query) && $query = parse_url_qs($query);
        // $a && $query = array_merge((array) $a, (array) $query);
        return Route::make($query);
    }
}
