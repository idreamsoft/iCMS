<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilterAdmincp extends AdmincpBase
{
    /**
     * 配置{title}
     *
     * @return void
     */
    public function do_config()
    {
        return ConfigView::display(self::$APPID);
    }
    public function done_config()
    {
        $data = Request::post('config');
        $data['filter']  = array_unique(explode("\n", trim($data['filter'])));
        $data['disable'] = array_unique(explode("\n", trim($data['disable'])));
        Config::vsave('filter',$data);
        $this->autoCache();
    }
    /**
     * autoCache 在更新所有缓存时，将会自动执行
     */
    public static function autoCache()
    {
        $config  = Config::vget('filter');
        Cache::set('filter/array', $config['filter'], 0);
        Cache::set('filter/disable', $config['disable'], 0);
    }
}
