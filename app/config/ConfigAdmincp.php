<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ConfigAdmincp extends AdmincpBase
{
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * [系统配置]
     */
    public function do_iCMS()
    {
        $config = Config::data();
        if (Request::isPost()) {
            return $this->save();
        }
        $redis     = extension_loaded('redis');
        $memcache  = extension_loaded('memcached');
        $tabsArray = $this->getMenuTabs('config');
        $extends   = Config::scan();
        Menu::setData('nav.active', $_SERVER['REQUEST_URI']);
        AppsMeta::get(self::$appId, Config::$siteid);
        include self::view("config.index");
    }
    /**
     * [站点配置]
     */
    public function do_site()
    {
        $config = Config::data();
        if (Request::isPost()) {
            return $this->save_site();
        }
        $tabsArray = $this->getMenuTabs('site');
        $extends   = Config::scan('config.site');
        Menu::setData('nav.active', $_SERVER['REQUEST_URI']);
        AppsMeta::get(self::$appId, Config::$siteid);
        include self::view("config.index");
    }
    public function save_site()
    {
        $config = (array)Request::post('config');
        if ($msg = FilesClient::checkConf($config['FS'])) {
            self::alert($msg);
        }

        FilesClient::allowExt(trim($config['route']['ext'], '.')) or self::alert('URL设置 > 文件后缀设置不合法');

        $desktop_tpl_ext = File::getExt($config['template']['desktop']['tpl']);
        if ($desktop_tpl_ext) FilesClient::allowExt($desktop_tpl_ext) or self::alert("桌面端模板不合法");

        $config['route']['ext']    = '.' . trim($config['route']['ext'], '.');
        $config['route']['url']    = trim($config['route']['url'], '/');
        $config['route']['public'] = rtrim($config['route']['public'], '/');
        $config['route']['user']   = rtrim($config['route']['user'], '/');
        $config['route']['dir']    = rtrim($config['route']['dir'], '/') . '/';
        $config['FS']['url']        = trim($config['FS']['url'], '/') . '/';
        $config['template']['desktop']['domain'] = $config['route']['url'];

        $this->save($config);
    }
    /**
     * [保存配置]
     */
    public function save($config = [])
    {
        empty($config) && $config = (array)Request::post('config');
        if ($config['cache'] && $config['cache']['engine'] != 'file') {
            iPHP::callback(
                ["CacheHelper", "test"],
                [$config['cache']]
            );
        }
        $config && array_walk($config, function ($v, $n) {
            Config::set($v, $n, 0);
        });

        AppsMeta::save(self::$appId, Config::$siteid);
        Config::cache();
    }
    /**
     * [更新系统设置缓存]
     *
     * @return void
     */
    public function do_cache()
    {
        $this->autoCache();
    }
    /**
     * [autoCache 在更新所有缓存时，将会自动执行]
     */
    public static function autoCache()
    {
        Config::cache();
    }
    public function getMenuTabs($id)
    {
        $tabsArray = array();
        $children = Menu::$DATA['system']['children'][$id]['children'];
        if ($children) foreach ($children as $index => $value) {
            parse_str($value['href'], $output);
            $active = $_GET['tab'] ? ($_GET['tab'] == $output['tab'] ? 'active' : '') : ($index ?: 'active');
            $id  = str_replace('.', '-', $output['tab']);
            $dir = $output['dir'];
            if (empty($dir)) {
                $dir = 'config';
                $output['tab'] = str_replace('.', '/', $output['tab']);
            }
            try {
                if (File::check($output['tab'])) {
                    $dir = Security::safeStr($dir);
                    $tabsArray[$index] = array($output['tab'], $value['caption'], $dir, $id, $active);
                }
            } catch (\Exception $ex) {
                $tabsArray[$index] = array('config/error', $value['caption'], 'config', $id, $active, $ex, $value);
            }
        }
        return $tabsArray;
    }
}
