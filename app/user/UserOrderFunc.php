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

class UserOrderFunc extends AppsFuncCommon
{
    protected static $app = User::APP;
    protected static $appid = User::APPID;

    public static function free($vars = null)
    {
        $max = $vars['max'] ?: 10;
        if ($vars['ckey'] && $vars['app']) {
            $key = sprintf('%s.%s', $vars['app'], $vars['ckey']);
            $max = (int)Config::get($key);
        }
        $count = UserOrder::checkFree($vars['app'], $vars['date'], $vars['userid']);
        echo intval($max - $count);
    }

    public static function get($vars = null)
    {
        $resource = [];
        $where[] = ['userid', $vars['userid']];
        isset($vars['type']) && $where[] = ['type', $vars['type']];
        isset($vars['value']) && $where[] = ['value', $vars['value']];
        isset($vars['product_type']) && $where[] = ['product_type', $vars['product_type']];

        $resource = UserOrderModel::where($where)->get();
        return $resource;
    }

    public static function lists($vars = null)
    {
        $whereNot = [];
        $resource = [];
        $model = UserOrderModel::field('id');

        isset($vars['status']) && $where[] = ['status', $vars['status']];
        isset($vars['userid']) && $where[] = ['userid', $vars['userid']];
        isset($vars['type']) && $where[] = ['type', $vars['type']];
        isset($vars['value']) && $where[] = ['value', $vars['value']];
        isset($vars['product_type']) && $where[] = ['product_type', $vars['product_type']];

        self::inited($vars, $model, $where, $whereNot);

        self::orderby([], 'id');
        self::where();
        return self::getResource(__METHOD__);
    }

    public static function resource($idsArray = null)
    {
        $vars = self::$vars;
        $resource = UserOrderModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        $payment = Config::get('payment');
        $names = $payment['names'] ? $payment['names'] : UserOrder::$nameMap;
        foreach ($resource as $key => &$value) {
            // $value['url'] = Route::url('userOrder/details', ['id' => $value['id']]);
            $value['subject'] = Lang::get($value['subject']);
            $value['name_text'] = $names[$value['name']];
            empty($value['name_text']) && $value['name_text'] = UserOrder::$nameMap[$value['name']];
            if ($value['name'] == 'time') {
                $value['name_text'] = format_time($value['value'], 'cn');
            }
        }
        return $resource;
    }
}
