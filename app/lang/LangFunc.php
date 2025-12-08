<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');

class LangFunc
{
    public static function set($vars)
    {
        //设置语言
        if ($vars['lang']) {
            LangHook::setting($vars['lang']);
        }
        //加载指定模板语言包
        if ($vars['template']) {
            LangHook::setTpl($vars['template']);
        }
    }

    public static function get($vars)
    {
        return Lang::getLang();
    }
}
