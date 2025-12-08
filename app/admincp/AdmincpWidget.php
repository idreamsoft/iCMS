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

class AdmincpWidget extends AdmincpBase
{
    public static function do_deck()
    {
        include self::view("user.deck");
    }
    public static function do_stats()
    {
        $total = self::$MODEL::count();
        $widget['title'] = Admincp::$APP_DATA['title'];
        $widget['total'] = array($total, '全部');
        foreach (self::$CLASS::$statusMap as $status => $text) {
            $count = self::$MODEL::where('status', $status)->count();
            $widget['datas'][] = array($count, $text);
        }
        return $widget;
    }
}
