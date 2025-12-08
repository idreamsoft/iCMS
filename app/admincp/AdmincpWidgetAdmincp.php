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
/**
 * 后台小工具
 */
class AdmincpWidgetAdmincp extends AdmincpWidget
{
    public $ACCESC_TITLE = '后台小工具';

    public function __construct()
    {
        parent::__construct();
    }

    public static function do_stats()
    {
        $ul = '<ul class="nav nav-pills flex-row fs-sm">%s</ul>';
        $li = '<li class="nav-item">
                    <a class="nav-link d-flex justify-content-between align-items-center %s" href="javascript:;">
                        <span>
                            <i class="%s me-1"></i> %s
                        </span>
                        <span class="badge rounded-pill bg-secondary">%s</span>
                    </a>
                </li>';

        $counts['comment'] = iPHP::callback(['CommentAdmincp',   'widget_count']);
        $counts['files'] = iPHP::callback(['FilesAdmincp',   'widget_count']);
        $counts['apps'] = iPHP::callback(['AppsAdmincp',   'widget_count']);
        $counts['chain'] = iPHP::callback(['ChainAdmincp',   'widget_count']);
        $counts['links'] = iPHP::callback(['LinksAdmincp',   'widget_count']);
        $counts['prop'] = iPHP::callback(['PropAdmincp',   'widget_count']);
        $list = '';
        foreach ($counts as $app => $values) {
            $apps = Apps::getData($app);
            $title = $apps['title'];
            $list .= sprintf($li, $active, $icon, $title, $values[0][0]);
            // foreach ($values as $idx => $value) {
            //     $lis.= sprintf($li,$active,$icon,$title.$value[1],$value[0]);
            // }
        }

        return [
            'html' => sprintf($ul, $list)
        ];
    }
}
