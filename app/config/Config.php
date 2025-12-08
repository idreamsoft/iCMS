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

    const VAPPID = '999999'; //虚拟应用 不缓存在iCMS/config.php
    public static $appid = 0;
    public static $siteid = 1;
    public static $siteMap = [];

    public static function setSiteId($id)
    {
        self::$siteid = $id;
        if (iPHP_MULTI_SITE && iPHP_APP_SITE != iPHP_APP) {
            $table = 'config_' . str_replace('.', '_', iPHP_APP_SITE);
            // var_dump($table);
            ConfigModel::setTable($table);
        }
    }

    /**
     * 从缓存中获取配置
     * @return mixed
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

        // is_array($config) && array_walk_recursive($config, function (&$v, $k) {
        //     if(is_numeric($v)){
        //         $v = (int)$v;
        //     }
        // });
        $type = gettype($config);
        return $config ?: ($type == 'string'?'':[]);
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
     * 更新配置缓存
     *
     * @return void
     */
    public static function cache()
    {
        if ($config = self::data()) {
            $config['route']['routing'] = Apps::getRouting();
            $config['route']['appRule'] = Apps::getAppRules();
            $config['route']['urlRule'] = Apps::getUrlRules();
            $config['route']['tag'] = $config['tag']; //标签配置;
            $config['route']['callback'] = [
                'domain' => ['nodeApp', 'domain'], //绑定域名回调
                'device' => ['Adapter', 'urls'], //设备网址
            ];
            $config['template']['device'] = $config['device'];
            unset($config['device']);
            $config['apps'] = Apps::getIds();
            $config['hooks']['run'] = AppsHooks::getRun();
            $config['hooks']['app'] = AppsHooks::getHooks('app_');
            $config['hooks']['admincp'] = AppsHooks::getHooks('admincp_');
            $config['hooks']['rewrite'] = AppsHooks::getHooks('rewrite_');
            // $config['hooks']['fields'] = AppsHooks::getHooks('field_run');
            $config['site']['meta'] = (array)AppsMeta::get(iCMS_APP_CONFIG, 1);
            self::put($config);
        }
    }

    /**
     * 应用配置保存
     *
     * @param Integer|Array $appid [应用ID 或 数组]
     * @param String|Array $name [应用名 或 数组]
     * @param Array $data [数据]
     * @return void
     * Config::save([配置数组])
     * Config::save('appid',[配置数组])
     * Config::save('appid','name',[配置数组])
     */
    public static function save($appid = 0, $name = null, $data = [])
    {
        //Config::save([配置数组])
        if (is_array($appid)) {
            $data = $appid;
            $appid = AdmincpBase::$APPID;
            $name = null;
        }
        //Config::save('appid',[配置数组])
        if (is_array($name)) {
            $data = $name;
            $name = null;
        }

        $name === null && $name = Admincp::$APP_NAME;
        // if (empty($appid) && self::$aId) {
        //     $appid = self::$aId;
        // }
        empty($appid) && iPHP::alert('配置程序出错缺少APPID!');
        self::set($data, $name, $appid);
        self::cache();
        return true;
    }

    /**
     * 获取配置
     * @param  Integer $appid [应用ID]
     * @param  String  $name   [应用名]
     * @return Array
     */
    public static function data($appid = null, $name = null)
    {
        $appid && self::$appid = $appid;
        if ($name === null) {
            $where['siteid'] = self::$siteid;
            $where['appid'] = ['<', self::VAPPID];
            $appid === null or $where['appid'] = $appid;
            $result = ConfigModel::where($where)->select();
            foreach ((array) $result as $c) {
                $config[$c['name']] = $c['value'];
            }
        } else {
            $config = ConfigModel::field('value')
                ->where([
                    'siteid' => self::$siteid,
                    'appid'  => $appid,
                    'name'   => $name,
                ])->value();
        }
        return $config;
    }

    /**
     * 获取虚拟应用配置
     *
     * @param String $name
     * @return Array|String
     */
    public static function vget($name)
    {
        return self::data(self::VAPPID, $name);
    }

    /**
     * 设置虚拟应用配置
     *
     * @param String $name
     * @param Array $data
     * @return void
     */
    public static function vset($name, $data)
    {
        return self::set($data, $name, self::VAPPID);
    }

    /**
     * 保存虚拟应用配置
     *
     * @param String $name
     * @param Array $data
     * @return void
     */
    public static function vsave($name, $data = [])
    {
        return self::save(self::VAPPID, $name, $data);
    }

    /**
     * 保存配置
     *
     * @param String|Integer|Array $value
     * @param String $name
     * @param Integer $appid
     * @return void
     */
    public static function set($value, $name, $appid = 0)
    {
        $siteid = self::$siteid;
        $where = [
            'siteid' => $siteid,
            'appid'  => $appid,
            'name'   => $name,
        ];
        is_array($value) && array_walk_recursive($value, function (&$v, $k) {
            if ($v === 'true') {
                $v = true;
            } elseif ($v === 'false') {
                $v = false;
            } elseif (is_numeric($v) && (ctype_digit((string)$v))) {
                $v = (int)$v;
            }
        });
        $has = ConfigModel::field('name')->where($where)->value();
        $fields = ['siteid', 'appid', 'name', 'value'];
        $data = compact($fields);
        if ($has) {
            ConfigModel::update($data, $where);
        } else {
            ConfigModel::create($data);
        }
    }

    public static function delete($appid, $name)
    {
        if ($name && $appid) {
            $where = [
                'siteid' => self::$siteid,
                'appid'  => $appid,
                'name'   => $name,
            ];
            ConfigModel::delete($where);
        }
    }

    /**
     * write 配置写入文件
     * @param  Array $config 配置
     * @param  String $name [文件名]
     * @return void
     */
    public static function put($config = null, $name = null, $dir = null)
    {
        is_null($config) && $config = self::data();
        $output = "<?php\ndefined('iPHP') OR exit('Access Denied');\n";
        $output .= is_array($config) ? 'return ' . var_export($config, true) : $config;
        $output .= ';';
        $path = iPHP_APP_CONFIG;
        if ($name) {
            $dir = $dir ?: iPHP_APP_CONFDIR;
            $path = $dir . '/' . $name . '.php';
        }
        File::put($path, $output);
    }

    /**
     * update 单个配置更新
     * @param  [type] $k [description]
     * @return void
     */
    public static function update($k, $appid = 0)
    {
        self::set(iCMS::$config[$k], $k, $appid);
        self::cache();
    }

    public static function template()
    {
        $template = self::get('template');
        $device = self::get('template.device');
        unset($template['device']);
        if ($device) {
            $template = array_merge($template, $device);
            // $device = array_column($device,null,'name');
        }
        $template = array_map(function ($item) {
            $item['index'] = str_replace(View::TPL_FLAG_1, $item['tpl'], $item['index']);
            return $item;
        }, $template);
        return $template;
    }
}
