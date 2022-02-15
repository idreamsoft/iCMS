<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class Plugin
{
    const APP = 'plugin';
    const APPID = iCMS_APP_PLUGIN;

    public static $flag = array();
    public static function init($class = null)
    {
        $class = iString::ltrim($class, 'Plugin');
        $class && self::$flag[$class] = true;
    }
    public static function library($file)
    {
        require_once sprintf('%s/library/%s.php', __DIR__, $file);
    }
    public static function import($file)
    {
        require_once sprintf('%s/%s.php', __DIR__, $file);
    }
}
