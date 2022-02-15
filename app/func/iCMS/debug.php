<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

function iCMS_debug($vars = null)
{
    if ($vars['sql']===true) {
        iDebug::$DATA['Func.sql'] = [];
        return;
    }
    if (is_numeric($vars['flag'])) {
        DB::debug($vars['flag']);
        return;
    }
    if ($vars['query']) {
        var_dump(DB::getQueryLog());
    } elseif ($vars['trace']) {
        var_dump(DB::getQueryTrace(
            is_bool($vars['trace']) ? null : $vars['trace']
        ));
    }
    if ($vars['data']) {
        var_dump(iDebug::$DATA);
    }
    if ($vars['sql']) {
        var_dump(iDebug::$DATA['Func.sql']);
    }
}
