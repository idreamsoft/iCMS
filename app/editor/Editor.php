<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class Editor
{
    public static function ueditor($id, $config = array(), $gateway = true)
    {
        $gateway = $gateway ? 'admincp' : 'usercp';
        $app = $config['app']['app'];
        empty($app) && $app = 'content';
        $editor_id = self::getID($id,'UE');
        ob_start();
        include AdmincpView::view("ueditor.script", "editor");
        return AdmincpView::html();
    }
    public static function markdown($id, $config = array(), $gateway = true)
    {
        $gateway = $gateway ? 'admincp' : 'usercp';
        $app = $config['app']['app'];
        empty($app) && $app = 'content';
        $editor_id = self::getID($id,'MD');
        ob_start();
        include AdmincpView::view("markdown.script", "editor");
        return AdmincpView::html();
    }
    public static function getID($id,$prefix)
    {
        return sprintf("%s_%s",$prefix,md5($id));
    }
}
