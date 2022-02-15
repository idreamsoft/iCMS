<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PluginQRcodeHook
{
    /**
     * [插件:生成二维码]
     * @param [type] $content  [参数]
     */
    public static function run($content, $output = false)
    {
        return PluginQRcode::make($content, $output);
    }
}
