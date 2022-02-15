<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilterAdmincp extends AdmincpCommon
{
    public function __construct()
    {
        parent::__construct();
    }
    public function do_config()
    {
        if (Request::isPost()) {
            return $this->save_config();
        }
        $GLOBALS['CONFIG_APPID'] = self::$appId;
        Config::vapp('filter');
    }

    public function save_config()
    {
        Config::$data = Request::post('config');
        Config::$data['filter']  = array_unique(explode("\n", Config::$data['filter']));
        Config::$data['disable'] = array_unique(explode("\n", Config::$data['disable']));
        Config::vsave('filter');
        $this->autoCache();
        // self::success('保存成功');
    }
    /**
     * [autoCache 在更新所有缓存时，将会自动执行]
     */
    public static function autoCache($config = null)
    {
        $config === null && $config  = Config::vget('filter');
        Cache::set('filter/array', $config['filter'], 0);
        Cache::set('filter/disable', $config['disable'], 0);
    }
}
