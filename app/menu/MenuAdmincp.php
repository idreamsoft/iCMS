<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class MenuAdmincp extends AdmincpBase
{

    public function do_nav()
    {
        return ['html' => Menu::buildNav()];
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
        Menu::cache();
    }
}
