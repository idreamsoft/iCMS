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

class TagWidgetAdmincp extends AdmincpBase
{
    public $ACCESC_TITLE = '{app.name}小工具';

    public function __construct()
    {
        parent::__construct();
    }
    public function do_aa()
    {
        include self::view("test");
    }
    public static function do_deck()
    {
        include self::view("user.deck");
    }
    public static function do_stats()
    {
        $total = TagModel::count();
        $widget['title'] = Admincp::$APP_DATA['title'];
        $widget['total'] = array($total, '全部');
        foreach (Tag::$statusMap as $status => $text) {
            $count = TagModel::where('status', $status)->count();
            $widget['datas'][] = array($count, $text);
        }
        return $widget;
    }
}
