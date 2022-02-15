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

class ArticleWidgetAdmincp extends AdmincpBase
{
    public $ACCESC_TITLE = '文章小工具';

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
        include self::view("article.deck");
    }
    public static function do_stats()
    {
        $total = ArticleModel::count();
        $widget['title'] = Admincp::$APP_DATA['title'];
        $widget['total'] = array($total, '全部');
        foreach (Article::$statusMap as $status => $text) {
            $count = ArticleModel::where('status', $status)->count();
            $widget['datas'][] = array($count, $text);
        }
        return $widget;
    }
}
