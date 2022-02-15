<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
function tpl_modifier_pluck($array = null, $key = null, $value = null)
{
    // $column = array_column($array,null,'field');
    if ($key && $value) {
        $column = array_column($array, $value, $key);
    } else {
        $column = array_column($array, $key);
    }
    return $column;
}
