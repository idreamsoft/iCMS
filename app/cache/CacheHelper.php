<?php

class CacheHelper
{
    const APP = 'cache';
    const APPID = iCMS_APP_CACHE;

    public static function test($config)
    {
        $cache = Cache::init($config, true);
        $cache->set('cache_test', 1);
        $cache->delete('cache_test');
    }
    /**
     * 清除所有文件类型缓存
     *
     * @return void
     */
    public static function clearAllFileCache()
    {
        if (Config::get('cache.engine') == 'file') {
            @set_time_limit(0);
            Cache::clean('*');
        }
    }
}
