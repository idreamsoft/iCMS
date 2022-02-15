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

class AppsWidget
{
    //子应用
    public static function subAppBtns($appid,$data)
    {
        $result = Apps::getArray(['rootid' => $appid]);
        // var_dump($array);
        include AdmincpView::display("widget/subAppBtn", "apps");
    }
}
