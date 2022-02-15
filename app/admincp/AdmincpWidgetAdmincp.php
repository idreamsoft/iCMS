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

class AdmincpWidgetAdmincp extends AdmincpBase
{
    public $ACCESC_TITLE = '后台小工具';

    public function __construct()
    {
        parent::__construct();
    }
    public static function do_stats()
    {
        $ul = '<ul class="nav nav-pills flex-row font-size-sm">%s</ul>';
        $li = '<li class="nav-item">
                    <a class="nav-link d-flex justify-content-between align-items-center %s" href="javascript:;">
                        <span>
                            <i class="%s mr-1"></i> %s
                        </span>
                        <span class="badge badge-pill badge-secondary">%s</span>
                    </a>
                </li>';

        $counts['comment'] = iPHP::callback(array("CommentAdmincp",   "widget_count"));
        $counts['files']   = iPHP::callback(array("FilesAdmincp",   "widget_count"));
        $counts['apps']    = iPHP::callback(array("AppsAdmincp",   "widget_count"));
        $counts['chain']   = iPHP::callback(array("ChainAdmincp",   "widget_count"));
        $counts['links']   = iPHP::callback(array("LinksAdmincp",   "widget_count"));
        $counts['prop']    = iPHP::callback(array("PropAdmincp",   "widget_count"));
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
            "html" => sprintf($ul, $list)
        ];
    }
}
