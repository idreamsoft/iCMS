<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class Apps
{
    const APP = 'APPS';
    const APPID = '1';
    const CONTENT_TYPE = '3';

    public static $table = 'article';
    public static $primary = 'id';
    public static $etc = 'etc';
    public static $array = [];

    public static function uninstall($apps)
    {
        if ($apps) {
            // DB::beginTransaction();
            //test::__uninstall($data)
            iPHP::callback([$apps['app'], '__uninstall'], [$apps]);
            //testAdmincp::__uninstall($app)
            iPHP::callback([$apps['app'] . 'Admincp', '__uninstall'], [$apps]);
            self::deleteAll($apps);
            // DB::commit();
            Apps::cache();
            Menu::cache();
        }
    }

    private static function deleteAll($app)
    {
        //删除分类
        Node::deleteAppData($app['id']);
        //删除属性
        Prop::deleteAppData($app['id'], $app['app']);
        //删除文件数据
        Files::delete($app['id']);
        //删除配置
        Config::delete($app['id'], $app['app']);
        //删除数据
        AppsModel::delete($app['id']);
        AppsStore::delete($app['id']);

        //删除自定义应用的etc
        if ($app['apptype'] == Apps::CONTENT_TYPE) {
            $menu = sprintf('menu/admincp.%s*', $app['app']);
            //content/etc/menu/admincp.%s*
            $paths = Etc::path('content', $menu);
            $paths && array_map(['File', 'rm'], $paths);

            $stats = sprintf('admincp/home.stats.%s*', $app['app']);
            //content/etc/admincp/home.stats.%s*
            $paths = Etc::path('content', $stats);
            $paths && array_map(['File', 'rm'], $paths);
        }

        File::check($app['app']);
        if ($app['app']) {
            //查找app目录
            $appdir = Application::path($app['app']);
            // 删除应用
            file_exists($appdir) && File::rmdir($appdir);
        }

        //最后 删除表
        self::dropTable($app['table']);
    }

    public static function installed($app, $r = false)
    {
        $path = Application::path($app);
        $path .= 'etc/install.lock';
        return $r ? $path : file_exists($path);
    }

    public static function dropTable($table)
    {
        if ($table) {
            foreach ((array) $table as $key => $value) {
                $value['table'] && DB::table($value['table'])->drop();
            }
        }
    }

    public static function item(&$value)
    {
        // if ($value) {
        //     $value = (array) $value;
        //     is_string($value['table']) && $value['table']  = json_decode($value['table'], true);
        //     is_string($value['config']) && $value['config']  = json_decode($value['config'], true);
        //     is_string($value['fields']) && $value['fields']  = json_decode($value['fields'], true);
        // }
        return $value;
    }

    public static function id($app = null, $trans = false)
    {
        if (strpos($app, 'App') !== false) {
            $app = substr($app, 0, -3);
        } elseif (strpos($app, 'Admincp') !== false) {
            $app = substr($app, 0, -7);
        }

        $array = Config::get('apps');
        $trans && $array = array_flip($array);
        if ($array[$app]) {
            return $array[$app];
        }
        return '0';
    }

    /**
     * 获取应用模型
     *
     * @param array|string|int $param 应用数据|应用名|应用ID
     * @return Model
     */
    public static function model($param)
    {
        $apps = is_array($param) ? $param : self::get($param);
        $app = $apps['app'];
        $modelName = $app . 'Model';
        if (class_exists($modelName)) {
            $model = new $modelName();
        } else {
            $model = ContentModel::setTable($app);
        }
        return $model;
    }

    public static function get($vars = 0, $field = 'id')
    {
        if (empty($vars)) {
            return [];
        }
        if (is_array($vars)) {
            $vars = array_unique($vars);
        } else {
            !is_numeric($vars) && $field == 'id' && $field = 'app'; //非数字，使用app字段
        }

        $where[$field] = $vars;

        $key = md5(json_encode($where));
        return Cache::temp($key, function () use ($where, $vars, $field) {
            $model = AppsModel::where($where);
            if (is_array($vars)) {
                $array = $model->select();
                $result = array_column($array, null, $field);
            } else {
                $result = $model->find();
            }
            return $result;
        });
    }

    public static function check($app)
    {
        $app = strtolower($app);
        $apps = Config::get('apps');
        if (is_numeric($app)) {
            return array_search($app, $apps);
        } else {
            return array_key_exists($app, $apps);
        }
    }

    public static function getTableArray()
    {
        return self::getArray([
            'table' => ['<>', '']
        ]);
    }

    public static function getMenuArray()
    {
        return self::getArray([
            'menu'   => ['<>', ''],
            'status' => '1'
        ], 'id,app,name,title,config,menu,apptype,type', 'id ASC');
    }

    public static function getArray($where = null, $field = '*', $orderby = '')
    {
        is_null($where) && $where = ['status' => '1'];
        empty($orderby) && $orderby = 'id ASC';
        $array = AppsModel::field($field)
            ->where($where)
            ->orderBy($orderby)
            ->select();
        $result = array_column($array, null, 'id');
        return $result;
    }

    public static function getUrlRules()
    {
        return Etc::many('*', 'route/iurl*', true);
    }
    /**
     * 获取应用内容链接规则
     *
     * @return void
     */
    public static function getAppRules()
    {
        $rs = Apps::getArray();
        foreach ((array) $rs as $key => $value) {
            $rule = self::getUrlType($value);
            $rule && $array[$value['app']] = $rule;
        }
        return $array;
    }

    public static function getUrlType($rs)
    {
        //apps/etc/urlType*
        empty($GLOBALS['urlTypeMap']) && $GLOBALS['urlTypeMap'] = Etc::many('*', 'route/urlType*', true);
        $urlTypeMap = $GLOBALS['urlTypeMap'];
        if ($rs['table'] && $rs['apptype'] == Apps::CONTENT_TYPE && $rs['type'] > 1) {
            $rule = $urlTypeMap['content'];
            $table = reset($rs['table']);
            $rule['primary'] = $table['primary'] ?: 'id';
        } else {
            $rule = $urlTypeMap[$rs['app']];
            empty($rule) && $rule = $urlTypeMap['content'];//单入口
            if (empty($rule) && $rs['config']['iurl']) {
                $rule = $rs['config']['iurl'];
            }
        }
        return $rule;
    }

    public static function getIds()
    {
        $array = [];
        if ($rs = Apps::getArray()) {
            $array = array_column($rs, 'id', 'app');
            self::makeDefine($array);
        }
        return $array;
    }

    public static function constName($app)
    {
        return sprintf(
            '%s_APP_%s',
            iPHP_APP,
            is_numeric($app) ? $app : Security::safeStr(strtoupper($app))
        );
    }

    public static function makeDefine($array)
    {
        foreach ($array as $_app => $_appid) {
            if ($_app) {
                ////define("iCMS_APP_ARTICLE",1);
                $define[] = sprintf(
                    'define("%s",%d);',
                    self::constName($_app),
                    $_appid
                );
                //define("iCMS_APP_1","article");
                $define[] = sprintf(
                    'define("%s","%s");',
                    self::constName($_appid),
                    Security::safeStr(strtolower($_app))
                );
            }
        }
        Config::put(implode(PHP_EOL, $define), 'app.define');
    }

    public static function getRouting()
    {
        //route/route*.json
        $route = Etc::many('*', 'route/route*', true, null);
        ksort($route);
        // Config::put($route, 'app.route');
        return $route;
    }

    public static function cache()
    {
        $rs = AppsModel::select();

        Cache::clean('app/*');

        foreach ((array) $rs as $a) {
            Apps::item($a);
            $appid_array[$a['id']] = $a;
            $app_array[$a['app']] = $a;

            Cache::set('app/' . $a['id'], $a, 0);
            Cache::set('app/' . $a['app'], $a, 0);
            // if ($a['menu']) {
            //     $menu = Apps::menuData($a);
            //     empty($menu) && Apps::menuData($a, $a['menu']);
            // }
        }
        Cache::set('app/idarray', $appid_array, 0);
        Cache::set('app/array', $app_array, 0);
        AppsMeta::cache();
        Config::cache();
        return true;
    }

    public static function getPathArr($app, $type = 'app')
    {
        //test/TestApp
        $obj = ucfirst($app) . ucfirst($type);
        $path = Application::path($app, $obj);
        return [$path, $obj];
    }

    public static function getData($appid = 1)
    {
        try {
            $key = sprintf('app/%s', $appid);
            //后台 空数据时直接从数据库获取
            $data = Cache::KV($key, function () use ($appid) {
                return defined('ADMINCP') ? self::get($appid) : [];
            }, 0);
        } catch (\CacheException $ex) {
            //后台缓存报错的时，直接从数据库获取
            if (defined('ADMINCP')) {
                $data = self::get($appid);
            } else {
                throw $ex;
            }
        }
        empty($data) && Application::nil('[appid:' . $appid . '] application no exist', '0005');
        return (array)$data;
    }

    public static function getDataLite($data = null)
    {
        is_array($data) or $data = self::getData($data);
        unset($data['table'], $data['config'], $data['fields'], $data['menu']);
        return $data;
    }

    public static function getUrl($appid = 1, $primary = '')
    {
        $data = self::getData($appid);
        if ($data['table']) {
            $table = reset($data['table']);
            $key = $table['primary'];
        }
        empty($key) && $key = 'id';

        return iCMS_URL . '/' . $data['app'] . '.php?' . $key . '=' . $primary;
    }

    public static function getTable($app = 1, $master = true)
    {
        if(empty($app)){
            return;
        }
        if (is_array($app)) {
            $rs = $app;
        } else {
            $rs = self::getData($app);
        }

        $table = $rs['table'];
        $master && is_array($rs['table']) && $table = reset($rs['table']);
        return (array) $table;
    }

    public static function getGitVersion($app)
    {
        $verArray = [];
        $ver_file = Application::path($app, 'version');
        if (@is_file($ver_file)) {
            $verArray = include $ver_file;
        }
        return $verArray;
    }

    public static function updateInc($field, $id, $appid = 1, $step = 1)
    {
        return self::updateCount($field, $id, $appid, $step, 'inc');
    }

    public static function updateDec($field, $id, $appid = 1, $step = 1)
    {
        return self::updateCount($field, $id, $appid, $step, 'dec');
    }

    public static function updateCount($field, $id, $appid = 1, $step = 1, $func = 'inc')
    {
        $data = self::getData($appid);
        $app = $data['app'];
        $where = ['id' => $id];
        try {
            $model = Apps::model($app);
            $model->where($where)->$func($field, $step);
        } catch (\Exception $ex) {
            if ($tables = $data['table']) {
                $table = $tables[$app];
                empty($table) && $table = reset($tables);
                if ($table) {
                    $name = $table['name'];
                    DB::table($name)->where($where)->$func($field, $step);
                }
            }
        }
    }

    public static function updateFieldCount($id, $table, $primary = 'id', $field = 'count', $math = '+', $count = 1)
    {
        $where[] = [$primary => $id];
        $math == '-' && $where = [$field, '>', '0'];
        DB::table($table)->where($id)->update([$field, $math, $count]);
    }

    public static function getTplFunc($app, $tag = false)
    {
        list($path, $obj_name) = Apps::getPathArr($app, 'func');
        if (is_file($path)) {
            $class_methods = get_class_methods($obj_name);
            if ($tag) {
                foreach ((array) $class_methods as $key => $value) {
                    if (strpos($value, '__') === false && strpos($value, $app . '_') !== false) {
                        $tag_array[] = iPHP_APP . ':' . str_replace('_', ':', $value);
                    }
                }
                return $tag_array;
            } else {
                return $class_methods;
            }
        }
    }

    public static function getTplTag($apps, $flag = false)
    {
        //模板标签
        if ($apps['app']) {
            $_app = $apps['app'];
            if ($apps['apptype'] == Apps::CONTENT_TYPE) {
                $_app = 'content';
            }
            $template = (array) self::getTplFunc($_app, true);
            list($path, $obj_name) = self::getPathArr($_app, 'app');

            if (is_file($path)) {
                //判断是否有APP同名方法存在 如果有 $appname 模板标签可用
                $class_methods = get_class_methods($obj_name);
                if ($class_methods && array_search($_app, $class_methods) !== false) {
                    array_push($template, '$' . $_app);
                }
            }
        }
        if ($apps['apptype'] == Apps::CONTENT_TYPE) {
            foreach ((array) $template as $key => $value) {
                $template[$key] = str_replace(
                    [':content:', '$content'],
                    [':' . $apps['app'] . ':', '$' . $apps['app']],
                    $value
                );
            }
        }
        return is_array($flag) ? (array) $template : implode("\n", (array) $template);
    }
}
