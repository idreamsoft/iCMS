<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ConfigFunc extends AppsFuncCommon
{
    public static function get($vars = null)
    {
        if (empty($vars['name'])) {
            return;
        }
        $config = Config::get($vars['name']);
        if (isset($vars['key']) && is_array($config)) {
            $config = $config[$vars['key']];
        }
        return $config;
    }
}
