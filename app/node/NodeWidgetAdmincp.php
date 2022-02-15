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

class NodeWidgetAdmincp extends AdmincpBase
{
    public $ACCESC_TITLE = '{app.name}小工具';

    public function __construct()
    {
        parent::__construct();
    }
    public static function do_stats()
    {
        $total = NodeModel::count();
        $widget['title'] = '节点';
        $widget['total'] = array($total, '全部');
        $result = NodeModel::field('id,appid,status')->select();
        $appids = array_column($result, 'appid', 'appid');

        $counts = $counts2 = [];
        foreach ($result as $i => $node) {
            $appid = $node['appid'];
            $status = $node['status'];
            $counts2[$status][$appid]++;
        }
        foreach ($appids as $i => $appid) {
            try {
                $apps = Apps::getData($appid);
                $labels[] = $apps['title'].'节点';
            } catch (\FalseEx $ex) {
            }
        }
        $color = array(
            '0' => '#e56767',//隐藏
            '1' => '#30c78d',//显示
            '2' => '#272e38',//不调用
        );
        foreach (Node::$statusMap as $status => $text) {
            $appcounts = $counts2[$status];
            $data = [];
            foreach ($appids as $i => $appid) {
                $data[] = $appcounts[$appid];
            }
            $datasets[] = [
                'data' => $data,
                'backgroundColor'=>$color[$status]?:'rgba('.rand(100,200).','.rand(100,200).','.rand(100,200).',.5)',
                'label' => $text
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }
}
