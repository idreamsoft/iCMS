<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class CacheAdmincp extends AdmincpBase
{
    /**
     * 执行所有自动更新接口
     *
     * @param string   $pat  查找规则
     * @return array
     */
    public static function auto($pat = '*Admincp')
    {
        //test/testAdmincp::do_autoCache
        //test/testAdmincp::autoCacheAaBb
        //test/testAdmincp::makeCache
        $result = AppsHooks::run($pat, function ($class, $method) {
            return (stripos($method, 'autoCache') !== false ||
                $method == 'makeCache');
        });
        return $result;
    }

    /**
     * autoCache 在更新所有缓存时，将会自动执行
     */
    public static function autoCache()
    {
        Cache::clean('page_total/*');
    }

    /**
     * 更新所有缓存
     * @return void
     */
    public function do_all()
    {
        $this->done_all();
        self::success('缓存更新成功');
        return true;
    }

    public function done_all()
    {
        set_time_limit(0);
        self::auto('*Admincp');
    }

    /**
     * 更新模板缓存
     * @return void
     */
    public function done_clearTpl()
    {
        self::autoCacheTpl();
    }

    public static function autoCacheTpl()
    {
        View::clearTpl();
    }

    public function do_index()
    {
        $children = Menu::$DATA['tools']['children']['cache']['children'];
        include self::view();
    }

    /**
     * 清除语言包缓存
     *
     * @return void
     */
    public function done_clearlang()
    {
        Lang::all(true);
    }

    /**
     * 清除所有文件类型缓存
     *
     * @return void
     */
    public function done_clearAll()
    {
        return CacheHelper::clearAllFileCache();
    }

    public static function saveCall_config_cache($data = [], $config = [])
    {
        if ($data && $data['engine'] != 'file') {
            iPHP::callback(
                ['CacheHelper', 'test'],
                [$data]
            );
        }
        return $data;
    }
}
