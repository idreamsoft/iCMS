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
    /**
     * 系统配置
     */
    public function do_system()
    {
        $config = Config::data();
        $redis = extension_loaded('redis');
        $memcache = extension_loaded('memcached');
        $tabsArray = $this->getMenuTabs('system');
        //cache/views/app/config/system.cache.html
        $extends = AdmincpView::getExtends('app/config/system');
        include self::view('index');
    }

    public function done_system()
    {
        return $this->save();
    }

    /**
     * 站点配置
     */
    public function do_site()
    {
        $config = Config::data();
        $tabsArray = $this->getMenuTabs('site');
        $extends = AdmincpView::getExtends('app/config/site');
        include self::view('index');
    }

    public function done_site()
    {
        return $this->save();
    }

    /**
     * 设备模板
     *
     * @return void
     */
    public function done_devices()
    {
        $devices = Config::data(0, 'device') ?: [];
        //删除时提交
        $remove = Request::post('remove');
        if (isset($remove)) {
            unset($devices[$remove]);
            $devices = array_values($devices);
            Config::set($devices, 'device');
            Config::cache();
            return true;
        }
        if ($i = (array)Request::post('i')) {
            $index = Request::post('index');

            if (isset($index)) {
                $devices[$index] = $i;
            } else {
                $devices[] = $i;
            }
            $devices = array_values($devices);
            Config::set($devices, 'device');
            Config::cache();
            return;
        }
        if ($items = (array)Request::post('items')) {
            $devices = array_values($items);
            Config::set($devices, 'device');
            Config::cache();
            return;
        }
        if ($flag = Request::param('flag')) {
            $devices = Config::data(0, 'device') ?: [];
            if ($flag == 2) {
                $arr = [
                    // iCMS::$config['template']['desktop'],
                    // iCMS::$config['template']['mobile']
                ];
                $arr = array_merge($arr, $devices);
                $list = [];
                if ($arr) {
                    foreach ($arr as $key => $device) {
                        $list[] = iJson::listItem(['name'=>$device['name']], $device['domain']);
                    }
                }
                return iJson::listData($list);
            }

            if ($flag == 1) {
                return ['data' =>array_values($devices)];
            }
        }
    }

    /**
     * 保存配置
     */
    public function save($config = [], $saveCall = [])
    {
        empty($config) && $config = (array)Request::post('config');
        $saveCall = (array)Request::post('saveCall');

        if ($saveCall) {
            foreach ($saveCall as $key => $call) {
                $data = $config[$key];
                // $call=='true' && $call='self::saveCall_'.$key;
                if (is_callable($call)) {
                    $config[$key] = call_user_func_array($call, [&$data, $config]);
                }
            }
        }

        $config && array_walk($config, function ($v, $n) {
            Config::set($v, $n, 0);
        });

        Config::cache();
    }

    /**
     * 保存配置
     * <input type="hidden" name="saveCall[template]" value="ConfigAdmincp::saveCall_config_template">
     */
    public function saveCall_config_template($data = [], $config = [])
    {
        $desktop_tpl_ext = File::getExt($data['desktop']['tpl']);
        if ($desktop_tpl_ext) {
            FilesClient::allowExt($desktop_tpl_ext) or self::alert('桌面端模板不合法');
        }
        $data['redirect'] = $config['route']['redirect'];
        return $data;
    }

    /**
     * 保存配置
     * <input type="hidden" name="saveCall[route]" value="ConfigAdmincp::saveCall_config_route">
     *
     * @param array $data
     * @param array $config
     * @return void
     */
    public function saveCall_config_route($data = [], $config = [])
    {
        FilesClient::allowExt(trim($data['ext'], '.')) or self::alert('URL设置 > 文件后缀设置不合法');
        $data['ext'] = '.' . trim($data['ext'], '.');
        $data['public'] = rtrim($data['public'], '/');
        $data['user'] = rtrim($data['user'], '/');
        $data['dir'] = rtrim($data['dir'], '/') . '/';
        return $data;
    }

    /**
     * 更新缓存
     *
     * @return void
     */
    public function done_cache()
    {
        $this->autoCache();
    }

    /**
     * autoCache 在更新所有缓存时，将会自动执行
     */
    public static function autoCache()
    {
        Config::cache();
    }

    public function getMenuTabs($id)
    {
        $tabsArray = [];
        $children = Menu::$DATA['settings']['children'][$id]['children'];
        if ($children) {
            foreach ($children as $index => $value) {
                list($app, $dir, $name) = $paths = explode('/', trim($value['href'], '/'));
                $id = sprintf('%s-%s', $dir, $name);
                $parse = parse_url($value['href']);

                if ($value['menuOnly']) {
                    continue;
                }

                if (empty($dir)) {
                    $dir = 'config';
                }

                $view = sprintf('%s/%s', $dir, $name);

                try {
                    if (File::check($view)) {
                        $dir = Security::safeStr($dir);
                        $tabsArray[$index] = [
                            'view'   => $view,
                            'title'  => $value['caption'],
                            'name'   => $name,
                            'app'    => $app,
                            'dir'    => $dir,
                            'active' => $name == Request::sget('sdo') ? 'active' : '',
                            'id'     => $id
                        ];
                    }
                } catch (\Exception $ex) {
                    $tabsArray[$index] = [
                        'view'  => 'config/error',
                        'title' => $value['caption'],
                        'app'   => 'config',
                        'dir'   => 'config',
                        'id'    => $id, $ex, $value
                    ];
                }
            }
        }
        return $tabsArray;
    }
}
