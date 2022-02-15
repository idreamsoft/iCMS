<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class CacheAdmincp extends AdmincpCommon
{

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * [执行所有自动更新接口]
     *
     * @param   [type]   $pat  [$pat description]
     * @param   Admincp        [ description]
     *
     * @return  [type]         [return description]
     */
    public static function auto($pat = "*Admincp")
    {
        //test/testAdmincp::do_autoCache
        //test/testAdmincp::do_autoCache
        //test/testAdmincp::autoCacheAaBb
        //test/testAdmincp::makeCache
        $result = AppsHooks::run($pat, function ($class, $method) {
            return (stripos($method, 'autoCache') !== false ||
                $method == "makeCache");
        });
        return $result;
    }

    /**
     * [更新所有缓存]
     * @return [type] [description]
     */
    public function do_all()
    {
        $result = self::auto('*Admincp');
        // self::success('更新完成');
    }

    /**
     * [更新模板缓存]
     * @return [type] [description]
     */
    public function do_tpl()
    {
        $this->autoCacheTpl();
    }
    public function autoCacheTpl()
    {
        View::clearTpl();
    }

    public  function do_clearAll()
    {
        if (Request::isPost()) {
            return CacheHelper::clearAllFileCache();
        }
        include self::view("clearall", "cache");
    }
}
