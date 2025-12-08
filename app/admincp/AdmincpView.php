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
    // public static $VIEW_NAME   = '';
    // public static $VIEW_DIR   = '';
    public static $DIR = null;
    public static $BASE_DIR = 'views';
    public static $EXT = '.html';
    public static $TEMPLATE = [];
    public static $ASSIGN = [];
    public static $EXTENDS = [];
    public static $PAGES = [];
    public static $IS_MODAL = false;
    public static $IS_FRAME = false;

    public function __construct($appid = 0)
    {
        self::$PAGES = Etc::get('admincp', 'pages');
        self::$IS_MODAL = Request::get('modal') ? true : false;
        self::$IS_FRAME = Request::get('frame') === 'main' ? true : false;
        if (self::$IS_FRAME && !Request::isAjax() && Request::isGet()) {
            $path = Admincp::urlPath();
            Session::set('main-app', Admincp::$APP);
            Session::set('main-path', $path);
            Utils::jumpto(ADMINCP_URL);
            exit;
        }
    }

    public static function pageClasses()
    {
        $sidebar = Config::get('admincp.sidebar');
        $sidebar['enable'] && self::$PAGES['sidebar'] = $sidebar['enable'];
        $sidebar['right'] && self::$PAGES['sidebar.r'] = $sidebar['right'];
        $sidebar['mini'] && self::$PAGES['sidebar.mini'] = $sidebar['mini'];
        // [enable]
        $page_classes = ['enable-cookies'];
        if (self::$IS_MODAL) {
            self::set('modal.dialog', true);
            self::set(['page.header', 'page.common', 'sidebar', 'tabs', 'breadcrumb', 'page.footer'], null);
            $page_classes[] = 'modal-enable';
        }
        if (!self::$IS_FRAME) {
            self::set(['sidebar', 'page.header', 'page.common'], null);
        }
        self::$PAGES['side.overlay'] && $page_classes[] = self::$PAGES['side.overlay'];
        self::$PAGES['side.scroll'] && $page_classes[] = 'side-scroll';
        if (self::$PAGES['page.header']) {
            self::$PAGES['sidebar'] && $page_classes[] = 'sidebar-o';
            self::$PAGES['sidebar.right'] && $page_classes[] = 'sidebar-r';
            self::$PAGES['sidebar.mini'] && $page_classes[] = 'sidebar-mini';
            self::$PAGES['sidebar.dark'] && $page_classes[] = 'sidebar-dark';
        }
        self::$PAGES['remember.theme'] && $page_classes[] = 'remember-theme';
        self::$PAGES['page.overlay'] && $page_classes[] = 'enable-page-overlay';
        self::$PAGES['page.header.fixed'] && $page_classes[] = 'page-header-fixed';
        self::$PAGES['page.header.dark'] && $page_classes[] = 'page-header-dark';
        self::$PAGES['main.content'] && $page_classes[] = self::$PAGES['main.content'];
        self::$PAGES['page.classes'] = implode(' ', $page_classes);
        return self::$PAGES['page.classes'];
    }

    public static function get($key = null)
    {
        return $key ? self::$PAGES[$key] : self::$PAGES;
    }

    public static function set($keys = null, $value = null)
    {
        if (is_array($keys)) {
            foreach ($keys as $k) {
                self::$PAGES[$k] = $value;
            }
        } else {
            self::$PAGES[$keys] = $value;
        }
    }

    public static function assign($key = null, $value = null)
    {
        if (is_array($key)) {
            self::$ASSIGN = $key;
        } elseif (isset($key) && isset($value)) {
            self::$ASSIGN[$key] = $value;
        }
    }

    public static function fetch($name = null, $dir = null)
    {
        ob_start();
        extract(self::$ASSIGN);
        include self::display($name, $dir);
        return self::html();
    }

    public static function html()
    {
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    public static function show($name, $app, $data = [], $field = '')
    {
        include self::display($name, $app);
    }

    public static function view($name = null, $dir = null)
    {
        return self::display($name, $dir);
    }

    public static function jsonScan($fn, $dir = '*', $flag = false)
    {
        // $dirRoot = iPHP_PUBLIC . APP_ASSETS . $dir;
        $dirRoot = sprintf('%s/%s/%s', iPHP_APP_DIR, $dir, APP_ASSETS_NAME);
        $pattern = sprintf('%s/%s.json', $dirRoot, $fn);
        $files = (array) glob($pattern);
        return array_map(function ($file) use ($dirRoot) {
            $json = file_get_contents($file);
            $arr = json_decode($json, true);
            return $arr;
        }, $files);
    }

    /**
     * 扫描 APP_ASSETS_NAME 目录下的JS
     *
     * @param pattern $fn
     * @param string $app  $app/assets/
     * @param string $sdir 子目录 $app/assets/$sdir/
     * @return Array
     */
    public static function jsScan($fn, $app = '*', $sdir = '')
    {
        $appRoot = sprintf('%s/%s/%s', iPHP_APP_DIR, $app, APP_ASSETS_NAME);
        $format = $sdir ? '%s/%s/%s.js' : '%s/%s.js';
        $pattern = sprintf($format, $appRoot, $fn);
        // var_dump($pattern);
        $files = (array) glob($pattern);
        return array_map(function ($file) {
            $file = str_replace(iPHP_APP_DIR, rtrim(APP_ASSETS, '/'), $file);
            return ['jspath' => $file];
        }, $files);
    }

    public static function scan($fn, $dir = '*', $flag = false)
    {
        // */views/*.html
        $pattern = Application::path($dir) . "views/{$fn}.html";
        $files = (array) glob($pattern);
        if ($flag) {
            $views = [];
            if ($files) {
                foreach ($files as $key => $value) {
                    preg_match('@.*?/app/(.+)/views/(.+)\.html@', $value, $match);
                    $name = File::name($value);
                    $path = str_replace(iPHP_APP_DIR . '/', '', $value);
                    $views[] = [$match[1], $match[2], $name, $value, $path];
                }
            }
            return [$views, $pattern];
        }
        return [$files, $pattern];
    }

    public static function getTplList($dir, $name)
    {
        return [
            //app/test/views/$name.my.html 1
            sprintf('%s/%s.my%s', $dir, $name, self::$EXT),
            //app/test/views/$name.php 2
            sprintf('%s/%s.php', $dir, $name),
            //app/test/views/$name.html 3
            sprintf('%s/%s', $dir, $name . self::$EXT),
        ];
    }

    public static function display($name = null, $dir = null, $flag = true)
    {
        if ($name === null && Admincp::$APP_NAME) {
            $name = Admincp::$APP_NAME; //app=test
            Admincp::$APP_DO && $name = Admincp::$APP_DO; //do=aaa
        }

        // $func = sprintf('view_%s', $name);// function view_aaa
        // $class = get_called_class();
        // call_user_func([new $class,$func]);

        if ($name == '') {
            Admincp::error('Admincp template file name is empty', 'template');
        }
        if (!Security::safePath($name)) {
            Admincp::error('Admincp template file name is error', 'template');
        }
        // if(defined('ADMINCP_ONEUI')){
        // 	self::$DIR = 'views';
        // 	self::$EXT = '.html';
        // }
        //
        //app/test/views
        $tpl = rtrim(Admincp::$APP_PATH, '/') . '/' . self::$BASE_DIR;

        if ($name[0] == '/') {
            $name = substr($name, 1);
        } elseif ($sub = Admincp::$APP_SNAME) {
            //app/test/views/aa/
            $tpl .= '/' . strtolower($sub);
        }
        //app/test/views
        self::$DIR && $dir = self::$DIR;
        $dir && $tpl = Application::path($dir) . self::$BASE_DIR;
        $deftpl = Application::path('admincp') . self::$BASE_DIR . '/default';
        // 获取内容模板列表
        $contentTplList = [];
        if ($dir === 'content' && Admincp::$APP_NAME !== 'content') {
            $contentTplPath = Application::path(Admincp::$APP_NAME) . self::$BASE_DIR;
            $contentTplList = self::getTplList($contentTplPath, $name);
        }
        // 获取当前应用模板列表
        $appTplList = self::getTplList($tpl, $name);
        // 合并所有模板路径
        self::$TEMPLATE = array_merge(
            $contentTplList,
            $appTplList,
            [sprintf('%s/%s', $deftpl, $name . self::$EXT)] // 默认模板路径
        );
        // 查找第一个存在的模板文件
        foreach (self::$TEMPLATE as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        if ($flag) {
            echo implode('<br />', self::$TEMPLATE);
            // $error = sprintf('%s/admincp/views/error.html', iPHP_APP_DIR);
            Admincp::error('Admincp template file not found', 'template');
        }
    }
    public static $VUEDATA = [];

    public static function setVueData($data)
    {
        self::$VUEDATA = $data;
    }

    public static $CONTEXT = [];

    public static function getContext()
    {
        if (!isset(self::$CONTEXT['params'])) {
            self::$CONTEXT['params'] = Request::sget();
        }
        AdmincpBase::getFieldMap(self::$CONTEXT);
        return self::$CONTEXT;
    }

    public static function setContext($context)
    {
        if ($context['tablist']) {
            // "tablist" => [
            // 	'params',
            // 	'status'=>1,
            // 	'aaa'=>[
            // 		'status'=>'http://dfsdf',
            // 		'sdf'=>'111',
            // 	],
            // ]
            if (is_array($context['tablist'])) {
                foreach ($context['tablist'] as $key => $value) {
                    if ($value == 'params') {
                        // tablist = ['params']
                        $context['tablist']['params'] = $context['params'];
                        unset($context['tablist'][$key]);
                    } else {
                    }
                }
            } else {
                //"tablist" => 'params',
                $key = $context['tablist'];
                $context['tablist'] = [$key => $context[$key]];
            }
        }
        self::$CONTEXT = array_merge(self::$CONTEXT, $context);
    }
    public static $jsPath = '';

    public static function setJsPath($path = null)
    {
        self::$jsPath = is_null($path) ? null : APP_ASSETS . $path . '.js';
    }

    public static function appVars()
    {
        return [
            'init'        => 1,
            'debug'       => iPHP_DEBUG,
            'bootstrap'   => 'admincp',
            'SCRIPT_NAME' => ADMINCP_URL, // /admincp.php
            'CSRF_TOKEN'  => Security::$CSRF_TOKEN,
            'API_URL'     => APP_URL,
            'URL'         => APP_URL, // /admincp.php/app
            'DO_URL'      => APP_DOURL,
            'do'          => Admincp::$APP_DO,
            'method'      => Admincp::$APP_METHOD,
            'name'        => Admincp::$APP_NAME,
            'errorId'     => iDebug::$ID,
            'isModal'     => (int)self::$IS_MODAL,
            'file'        => Config::get('file'),
            'lang'        => Admincp::$LANGUAGE,
            'modPath'     => ADMINCP_URL . '/{{module}}/assets/js/{{method}}.js',
            'appModPath'  => ADMINCP_URL . '/{{module}}/assets/js/{{method}}.js',
            'jspath'      => self::$jsPath ?: self::getAppJs(),
            'jspaths'     => self::$jsPaths,
            'config'      => AdmincpBase::$CONFIG,
            'appId'       => AdmincpBase::$APPID,
            'context'     => self::getContext(),
            'ACCESS'      => Admin::$ACCESS['app'] ? array_values(Admin::$ACCESS['app']) : '',
            'const'       => [],
            'vuedata'     => self::$VUEDATA,
            'get'         => Request::get(),
            'admin'       => Admin::$DATA,
            'startFrame'  => Config::get('admincp.frame') ? true : false,
            'jsdebug'     => iPHP_JS_DEBUG,
            'gitTime'     => iCMS_GIT_TIME,
            'release'     => iCMS_RELEASE
        ];
    }

    public static function head($set = [])
    {
        if (Request::get('load') === 'page') {
            return;
        }
        $frame = Config::get('admincp.frame');
        if ($frame) {
            //框架模式 默认不加载sidebar,page.header
            if (empty($set)) {
                $set = [['sidebar', 'page.header'], null];
            }
        }

        is_array($set) && self::set($set[0], $set[1]);
        $APP = self::appVars();
        $v = self::ver();
        self::$PAGES['head.begin'] &&
            include self::display('head.begin', self::$PAGES['head.begin']);

        include self::display('base/head', 'admincp', true);

        self::$PAGES['head.after'] &&
            include self::display('head.after', self::$PAGES['head.after']);
    }

    public static function main()
    {
    }

    public static function foot($set = [])
    {
        if (Request::get('load') === 'page') {
            return;
        }
        $set && self::set($set[0], $set[1]);
        $v = self::ver();
        self::$PAGES['foot.begin'] &&
            include self::display('foot.begin', self::$PAGES['foot.begin']);

        include self::display('base/foot', 'admincp');

        self::$PAGES['foot.after'] &&
            include self::display('foot.after', self::$PAGES['foot.after']);
    }

    /**
     * 解析模板语法
     * {{AAA_BBB}} 大写字母 =>常量
     * {{aaa/bbb}} 其它字符 =>链接
     * {{#Lang()}}  函数
     *
     * @param string $html
     * @return void
     */
    public static function parse(&$html)
    {
        //{{APP_URL}}
        //{{iXXX}}
        preg_match_all('@\{\{([iA-Z_]+)\}\}@', $html, $matches);
        // var_dump($matches);
        $values = [];
        if ($matches[1]) {
            foreach ($matches[1] as $key => $name) {
                $values[$key] = defined($name) ? constant($name) : $name;
            }
        }
        $matches[0] && $html = str_replace($matches[0], $values, $html);
        //{{#lang('sd')}}
        //{{#lang('sd %s',['sdfsd'])}}
        preg_match_all('@\{\{\#(\w+)\((.+?)\)\}\}@', $html, $matches);
        // preg_match_all('@\{\{(\w+)\((.+?)\)\}\}@', $html, $matches);
        $values = [];
        if ($matches[1]) {
            foreach ($matches[1] as $key => $func) {
                $paramText = $matches[2][$key];
                preg_match_all('/[\"|\'](.*?)[\"|\']/is', $paramText, $param_match);
                $param = $param_match[1];
                $format = $param[0];
                unset($param[0]);
                $values[$key] = call_user_func_array($func, [$format, $param]);
            }
        }
        $matches[0] && $html = str_replace($matches[0], $values, $html);
        //{{./bb}}
        //{{aa/bb}}
        //{{aa/bb?id=1}}
        //{{./add?id=1}}
        preg_match_all('@\{\{(.+?)\}\}@is', $html, $matches);
        // var_dump($matches);
        $values = [];
        if ($matches[1]) {
            foreach ($matches[1] as $key => $url) {
                if (strpos($url, '/') !== false) {
                    if (strpos($url, 'self://') !== false) {
                        $url = str_replace('self:/', Admincp::$APP_NAME, $url);
                    }
                    if (strpos($url, './') === 0) {
                        $url = str_replace('./', Admincp::$APP_NAME . '/', $url);
                        // var_dump(Admincp::url($url));
                    }
                    $values[$key] = Admincp::url($url);
                } else {
                    //保持原样{{aa}}
                    $values[$key] = $matches[0][$key];
                }
            }
        }
        // var_dump($values);
        $html = str_replace($matches[0], $values, $html);
    }

    public static function form()
    {
        return new AdmincpForm();
    }

    /**
     * 加载 插件页面
     *
     * @param string $name
     * @param string $dir
     * @return void
     */
    public function plugin($name, $dir = '*')
    {
        self::load('app/' . $name, $dir);
    }

    /**
     * 加载 页面
     *
     * @param string $name
     * @param string $dir
     * @return void
     */
    public function load($name, $dir = '*')
    {
        //*/view/plugin/home/content.asd.html
        list($views, $pattern) = self::scan($name, $dir, true);
        if ($views) {
            foreach ($views as $key => $value) {
                include self::view($value[1], $value[0]);
            }
        }
    }

    /**
     * 加载 扩展页面
     *
     * @param string $fn
     * @param string $dir
     * @param boolean [true|false] [nav|tabs]
     * @return Array
     */
    public static function getExtends($fn = 'config.*', $dir = '*', $flag = true)
    {
        $array = [];
        // test/views/config.aa.bb.html
        // test/views/config.aa.html
        $fn && strpos($fn, '*') === false && $fn .= '.*';
        list($views, $pattern) = self::scan($fn, $dir, true);
        // var_dump($views, $pattern);
        if ($views) {
            foreach ($views as $key => $view) {
                // test/views/config.aa.bb.html
                /**
                 * test,
                 * config.aa.bb
                 * config.bb
                 */
                list($app, $viewName, $fileName, $rootpath, $path) = $view;

                //[config]
                //[config,bb]
                //[config,aa,bb]
                $info = explode('.', $fileName);
                $nkey = $info[0];
                $title = $name = $info[1] ?: $info[0];

                if ($flag) {
                    $apps = Apps::getData($app);
                    if (!isset($array[$app]['title'])) {
                        $array[$app]['title'] = $apps['title'] ?: strtoupper($nkey);
                    }
                }
                $nav = true;
                $subTitle = '';
                $icon = '';
                $header_enable = true;
                $header_class = 'block-header-default';
                $content_class = '';
                //加载模板 获取里面的变量
                ob_start();
                include $rootpath;
                ob_end_clean();
                if ($AppTitle) {
                    $array[$app]['title'] = $AppTitle;
                    unset($AppTitle);
                }
                $title == $name or $subTitle = $name;
                $target = short_md5($path);
                $active = $name == Request::sget('sdo') ? 'active' : '';
                $block = compact('header_enable', 'header_class', 'content_class');
                $tabs = compact('rootpath', 'path', 'target', 'fileName', 'name', 'active', 'title', 'subTitle', 'icon', 'block');
                isset($block) && $tabs['block'] = $block;

                if ($flag) {
                    $array[$app]['nav'] = $nav;
                    $array[$app]['active'] = $active;
                    $array[$app]['tabs'][$name] = $tabs;
                } else {
                    $array[] = $tabs;
                }
            }
        }
        return $array;
    }

    public static function url($query, $url = null)
    {
        is_string($query) && $query = parse_url_qs($query);
        // $a && $query = array_merge((array) $a, (array) $query);
        return Route::make($query, $url);
    }
    public static $jsPaths = [];

    public static function getAppJs($do = null)
    {
        $do = $do ?: Admincp::$APP_DO;
        $sdo = Request::sget('sdo');
        // admincp.php/testSss/index
        if (Admincp::$APP_SNAME) {
            // /app/test/sss/index_aa.js
            $sdo && $jsPaths[] = strtolower(Admincp::$APP_SNAME) . '/' . $do . '_' . $sdo . '.js';
            // /app/test/sss/index.js
            $jsPaths[] = strtolower(Admincp::$APP_SNAME) . '/' . $do . '.js';
            // /app/test/assets/sss.js
            $jsPaths[] = strtolower(Admincp::$APP_SNAME) . '.js';
        } else {
            // admincp.php/test/index
            // /app/test/assets/index_aa.js
            $sdo && $jsPaths[] = $do . '_' . $sdo . '.js';
            // /app/test/assets/index.js
            $jsPaths[] = $do . '.js';
        }
        // /app/test/assets/test.js
        $jsPaths[] = Admincp::$APP . '.js';
        self::$jsPaths = $jsPaths;
        foreach ($jsPaths as $key => &$path) {
            // iCMS::/app/test/assets/aaa.js
            $rpath = sprintf('%s/js/%s', APP_ASSETS_ROOT, $path);
            $path = sprintf('%s/js/%s', APP_ASSETS_DIR, $path);

            // /public/assets/admincp/app/test
            // $path = sprintf("%s/%s", APP_ASSETS_DIR, $path);
            // $rpath = sprintf("%s%s", iPHP_PUBLIC, $path);
            if ($path && file_exists($rpath)) {
                // $path = Admincp::url(Admincp::$APP_NAME,$path);
                return $path;
            }
        }
    }

    public static function ver()
    {
        return iPHP_JS_DEBUG ? time() : iCMS_GIT_TIME;
    }

    public static function getAppCss()
    {
        $name = '/css/' . Security::safeStr(Admincp::$APP_DO) . '.css';
        $path = APP_ASSETS_DIR . $name;
        $rpath = APP_ASSETS_ROOT . $name;
        file_exists($rpath) &&
            print('<link rel="stylesheet" href="' . $path . '?v=' . self::ver() . '" />');
    }
    public static function getAppData()
    {
        $APP = self::appVars();
        // $id = random(8);
        $html = '<script id="app-data" type="application/json">'.
        json_encode($APP ?:[], JSON_UNESCAPED_UNICODE)
        .'</script>'
        .'<script type="text/javascript">
        const dataElement = document.getElementById("app-data");
        const $APP = JSON.parse(dataElement.textContent);
        </script>';
        echo $html;
    }
    // public static function requirejs()
    // {
    //     $app_do_css = APP_ASSETS_DIR . "/" . Admincp::$APP_DO . ".css";
    //     $app_do_js  = APP_ASSETS_DIR . "/" . Admincp::$APP_DO . ".js";
    //     echo "<!--{$app_do_css}-->";
    //     echo "<!--{$app_do_js}-->";
    //     file_exists(iPHP_PUBLIC . '/' . substr($app_do_css, 2)) &&
    //         print('<link rel="stylesheet" href="' . $app_do_css . '?v=' . time() . '" />');
    //     file_exists(iPHP_PUBLIC . '/' . substr($app_do_js, 2)) &&
    //         print('<script src="' . $app_do_js . '?v=' . time() . '"></script>');
    // }
    // public static function error($msg, $type = "danger")
    // {
    //     printf('<div class="alert alert-%s" role="alert">%s</div>', $type, $msg);
    // }
}
