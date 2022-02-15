<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iCMS_APP_CONFIG') or define('iCMS_APP_CONFIG', '11');

class Config
{
    const APP = 'config';
    const APPID = iCMS_APP_CONFIG;

    const VAPPID = "999999";
    public static $aId = 0;
    public static $siteid = 1;
    public static $setting = array();
    public static $data  = array();
    /**
     * 从缓存中获取配置
     */
    public static function get($key = null, $key2 = null)
    {
        $config = iCMS::$config;
        if (is_null($key) && is_null($key2)) {
            return $config;
        }
        $keyArray = explode('.', $key);
        foreach ($keyArray as $k) {
            $config = $config[$k];
        }
        if ($key2 && is_array($config)) {
            $key2Array = explode('.', $key2);
            foreach ($key2Array as $k) {
                $config = $config[$k];
            }
        }
        // (is_string($key2) && is_array($config)) && $config = $config[$key2];
        return $config;
    }
    public static function table()
    {
        $table = 'config';
        defined('iPHP_APP_SITE') or define('iPHP_APP_SITE', iPHP_APP);
        if (iPHP_APP_SITE != iPHP_APP) {
            $table = 'config_' . str_replace('.', '_', iPHP_APP_SITE);
        }
        return $table;
    }
    /**
     * [cache 更新配置]
     * @return [type] [description]
     */
    public static function cache()
    {
        if ($config = self::data()) {
            $config['apps'] = Apps::getIds();
            $config['iurl'] = Apps::getUrlRules();
            $config['routing'] = Apps::getRoute();
            $config['meta'] = array();
            $data = AppsMeta::data(iCMS_APP_CONFIG, 1);
            is_array($data) && $config['meta'] = $data['meta'];
            self::put($config);
        }
    }
    // public static function head($title = null, $action = "config")
    // {
    //     return include AdmincpView::display("config.head", "config");
    // }
    // public static function foot()
    // {
    //     return include AdmincpView::display("config.foot", "config");
    // }
    /**
     * [app 其它应用配置接口]
     * @param  integer $appid [应用ID]
     * @param  [sting] $name   [应用名]
     */
    public static  function app($appid = 0, $name = null, $ret = false, $suffix = "config")
    {
        $name === null && $name = Admincp::$APP_NAME;
        if (empty($appid) && self::$setting['appid']) {
            $appid = self::$setting['appid'];
        }
        empty($appid) && Admincp::exception("配置程序出错缺少APPID!");
        $config = self::data($appid, $name);

        if ($ret) {
            return $config;
        }

        if ($appid == Config::VAPPID && $GLOBALS['CONFIG_APPID']) {
            $appid = $GLOBALS['CONFIG_APPID'];
        }

        $apps = Apps::getData($appid);

        $title         = self::$setting['title'] ?: $apps['title'] . '系统';
        $subTitle      = self::$setting['subTitle'] ?: $name;
        $action        = self::$setting['action'] ?: 'config';
        $icon          = self::$setting['icon'];
        $header_enable = self::$setting['header_enable'];
        $header_class  = self::$setting['header_class'];
        $content_class = self::$setting['content_class'];
        $file          = self::$setting['file'] ?: $apps['app'] . '.config';

        ob_start();
        include AdmincpView::display($name . '.' . $suffix, $apps['app']); //test.config.html,test
        $content = AdmincpView::html();
        
        $extends       = Config::scan($file, $apps['app']); //test.config.asd.html
        $tabs          = array();
        if ($extends) {
            $extends = current($extends);
            $tabs = $extends['tabs'];
        }
        // var_dump($extends);
        include AdmincpView::display("app.config", "config");
    }
    /**
     * [save 其它应用配置保存]
     * @param  integer $appid [应用ID]
     * @param  [sting] $app   [应用名]
     */
    public static function save($appid = 0, $name = null)
    {
        $name === null   && $name = Admincp::$APP_NAME;
        if (empty($appid) && self::$aId) {
            $appid = self::$aId;
        }
        empty($appid) && iPHP::alert("配置程序出错缺少APPID!");
        self::set(self::$data, $name, $appid, false);
        self::cache();
        return true;
    }
    /**
     * [data 获取配置]
     * @param  integer $appid [应用ID]
     * @param  [type]  $name   [description]
     * @return [type]       [description]
     */
    public static function data($appid = NULL, $name = NULL)
    {
        $appid && self::$aId = $appid;
        if ($name === NULL) {
            $where['siteid'] = self::$siteid;
            $where['appid'] = array('<', self::VAPPID);
            $appid === NULL or $where['appid'] = $appid;
            $result = ConfigModel::where($where)->select();
            foreach ((array) $result as $c) {
                $config[$c['name']] = $c['value'];
            }
            self::$data = $config;
            return $config;
        } else {
            $value = ConfigModel::field('value')
                ->where(array(
                    'siteid' => self::$siteid,
                    'appid' => $appid,
                    'name' => $name,
                ))->value();
            self::$data = $value;
            return $value;
        }
    }

    public static function vapp($name, $ret = false)
    {
        return self::app(self::VAPPID, $name, $ret);
    }
    public static function vget($name)
    {
        return self::data(self::VAPPID, $name);
    }
    public static function vset($data, $name)
    {
        return self::set($data, $name, self::VAPPID);
    }
    public static function vsave($name)
    {
        return self::save(self::VAPPID, $name);
    }

    /**
     * [set 更新配置]
     * @param [type]  $v     [description]
     * @param [type]  $n     [description]
     * @param [type]  $appid   [description]
     * @param boolean $cache [description]
     */
    public static function set($value, $name, $appid, $cache = false)
    {
        $cache && Cache::set('config/' . $name, $value, 0);
        $siteid = self::$siteid;
        $where = array(
            'siteid' => $siteid,
            'appid'  => $appid,
            'name'   => $name,
        );
        $has    = ConfigModel::field('name')->where($where)->value();
        $fields = array('siteid', 'appid', 'name', 'value');
        $data   = compact($fields);
        if ($has) {
            ConfigModel::update($data, $where);
        } else {
            ConfigModel::create($data);
        }
    }
    public static function delete($appid, $name)
    {
        if ($name && $appid) {
            $where = array(
                'siteid' => self::$siteid,
                'appid'  => $appid,
                'name'   => $name,
            );
            ConfigModel::delete($where);
        }
    }
    /**
     * [write 配置写入文件]
     * @param  [type] $config [description]
     * @param  [type] $name [文件名]
     * @return [type]         [description]
     */
    public static function put($config = null, $name = null, $dir = null)
    {
        is_null($config) && $config = self::data();
        $output = "<?php\ndefined('iPHP') OR exit('Access Denied');\n";
        $output .= is_array($config) ? 'return ' . var_export($config, true) : $config;
        $output .= ';';
        $path = iPHP_APP_CONFIG;
        if ($name) {
            $dir  = $dir ?: iPHP_APP_CONFDIR;
            $path = $dir . '/' . $name . '.php';
        }
        File::put($path, $output);
    }
    /**
     * [update 单个配置更新]
     * @param  [type] $k [description]
     * @return [type]    [description]
     */
    public static function update($k, $appid = 0)
    {
        self::set(iCMS::$config[$k], $k, $appid);
        self::cache();
    }

    public static function scan($fn = 'config.*', $dir = '*', $flag = true)
    {
        $array = array();
        // test/views/config.aa.bb.html
        // test/views/config.aa.html
        $pattern = iAPP::path($dir) . "views/{$fn}.*.html";
        $files = (array) glob($pattern);
// var_dump($pattern,$files);

        iDebug::$DATA['config.scan'][$dir] = $pattern;
        iDebug::$DATA['config.scan']['glob'] = $files;

        foreach ($files as $key => $path) {
            // test/views/config.aa.bb.html
            $file = str_replace(iPHP_APP_DIR . '/', '', $path);
            //test
            $app = strstr($file, '/', true);

            //[config.aa.bb]
            //[config.bb]
            $sub = File::name($file);
            //[config,aa,bb]
            //[config,bb]
            $info = explode('.', $sub);
            $nkey = $info[1];
            $title = $name = $info[2];
            if ($file) {
                if ($flag) {
                    $apps = Apps::getData($app);
                    $array[$app]['title'] = $apps['title'] ?: strtoupper($nkey);
                }
                $nav = true;
                $subTitle = '';
                $icon = '';
                $header_enable = true;
                $header_class = 'block-header-default';
                $$content_class = '';
                //加载模板 获取里面的变量
                ob_start();
                include $path;
                ob_end_clean();

                $title == $name or $subTitle = $name;

                $block = compact('header_enable', 'header_class', 'content_class');
                $tabs  = compact('path', 'sub', 'name', 'title', 'subTitle', 'icon', 'block');
                isset($block) && $tabs['block'] = $block;

                if ($flag) {
                    $array[$app]['nav'] = $nav;
                    $array[$app]['tabs'][$name] = $tabs;
                } else {
                    $array[] = $tabs;
                }
            }
        }
        return $array;
    }
}
