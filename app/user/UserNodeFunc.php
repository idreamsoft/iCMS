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

class UserNodeFunc extends AppsFuncCommon
{
    protected static $app = User::APP;
    protected static $appid = User::APPID;

    public static function lists($vars = null)
    {
        $whereNot  = array();
        $resource  = array();
        $model     = UserNodeModel::field('id');
        $status    = isset($vars['status']) ? (int) $vars['status'] : 1;
        $where     = [['status', $status]];

        $where[] = ['userid', $vars['userid']];
        $where[] = ['appid', $vars['appid']];
        
        self::inited($vars, $model, $where, $whereNot);
        self::keywords();
        self::orderby([
            'id'    => 'id',
            'hot'   => 'count',
        ], 'id');
        self::where();
        return self::getResource(__METHOD__);
    }
    public static function resource($idsArray = null)
    {
        $vars = self::$vars;

        $resource = UserNodeModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        if ($resource) foreach ($resource as $key => &$value) {
            if ($value['appid'] == iCMS_APP_ARTICLE) {
                $route = '{userid}/{cid}';
            } else if ($value['appid'] == iCMS_APP_FAVORITE) {
                $route = '{userid}/fav/{cid}';
            }
            $value['url'] = Route::routing($route, [$value['userid'], $value['id']]);
        }
        return $resource;
    }
}
