<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ConfigTmplDist
{
    public static function function_dist($vars, &$tpl, $tag)
    {
        list($app, $method, $name) = explode(':', $tag);
        $body = ConfigDistModel::where()->where('name', $name)->value('body');
        return  $body;
    }
}
