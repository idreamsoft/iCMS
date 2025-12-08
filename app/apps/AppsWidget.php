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
    public static function subApp($appid)
    {
        $result = Apps::getArray(['rootid' => $appid]);
        $buttons = [];
        foreach ($result as $key => $value) {
            $buttons[] = [
                'href'    => sprintf('/%s/index/%s_id/{{id}}', $value['app'], Admincp::$APP),
                'text'    => $value['title'] . '管理',
                'ui'      => 'cms:ui:modal'
            ];
            $buttons[] = [
                'href'    => sprintf('/%s/add/%s_id/{{id}}', $value['app'], Admincp::$APP),
                'text'    => '添加' . $value['title'],
                'ui'      => 'cms:ui:modal'
            ];
            $buttons[] = 'divider';
        }
        return $buttons;
    }

    //子应用
    public static function subAppBtns($appid, $data)
    {
        $result = Apps::getArray(['rootid' => $appid]);
        // var_dump($array);
        include AdmincpView::display('widget/subAppBtn', 'apps');
    }

    //应用扩展页面
    public static function extend($data = null)
    {
        include AdmincpView::display('widget/extend', 'apps');
    }

    public static function credit($data = [], $tpl = 'credit', $field = 'rs')
    {
        $names = [
            'money'  => '金币',
            'credit' => '积分',
            'scores' => '点数',
        ];
        if (iCMS::$config['payment']) {
            $names = iCMS::$config['payment']['names'];
        }
        $name_format = $field ? '%s[%s]' : '%s%s';
        $money_name = sprintf($name_format, $field, 'money');
        $credit_name = sprintf($name_format, $field, 'credit');
        $scores_name = sprintf($name_format, $field, 'scores');
        isset($data) or $data = [];
        include AdmincpView::display('widget/' . $tpl, 'apps');
    }

    public static function metadata($id = '', $appid = 0)
    {
        empty($appid) && $appid = AdmincpBase::$APPID;
        empty($appid) && $appid = Admincp::$APP_ID;
        empty($appid) && $appid = Admincp::$APP;
        if (empty($id)) {
            // return AdmincpView::error('编辑状态下使用');
        }
        include AdmincpView::display('widget/metadata', 'apps');
    }

    public static function attribute($data = [], $name = 'rs[attribute]')
    {
        include AdmincpView::display('widget/attribute', 'apps');
    }
}
