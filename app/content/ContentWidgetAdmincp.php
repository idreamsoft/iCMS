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

class ContentWidgetAdmincp extends AdmincpBase
{
    public $ACCESC_TITLE = '{app.name}小工具';

    public function __construct()
    {
        parent::__construct();
    }
    public static function do_deck()
    {
        include self::view("content.deck");
    }
    public static function do_stats()
    {
        $appId = Request::sget('appId');
        $apps = Apps::get($appId);
        $model = Content::model($apps);
        $total = $model->count();
        $widget['title'] = $apps['title'];
        $widget['total'] = array($total, '全部');
        foreach (Content::$statusMap as $status => $text) {
            $count = $model->where('status', $status)->count();
            $widget['datas'][] = array($count, $text);
        }
        return $widget;
    }
}
