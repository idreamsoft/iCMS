<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class ContentHook
{
    /**
     * Application::run file回调
     * @param  string $app 应用
     * @return void
     */
    public static function run_file($app)
    {
        $data = Apps::getData($app);
        if ($data) {
            Application::$PATH = Application::path('content');
            Application::$FILE = Application::$PATH . '/ContentApp.php';
            Application::$CLASS = 'ContentApp';
            Application::$INSTANCE = new ContentApp($data);
        } else {
            iPHP::error('Unable to find custom application <b>' . $app . '</b>', '0003');
        }
    }
}
