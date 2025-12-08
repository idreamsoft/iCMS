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

class FavoriteFunc extends AppsFuncCommon
{
    public static function lists($vars = null)
    {
        $whereNot = [];
        $resource = [];
        $model = FavoriteModel::field('id');

        isset($vars['mode']) && $where['mode'] = $vars['mode'];
        isset($vars['status']) && $where['status'] = $vars['status'];
        if (isset($vars['userid'])) {
            $userid = $vars['userid'];
            $userid == '@me' && $userid = User::$id ?: 0;
            $where['userid'] = $userid;
        }
        self::inited($vars, $model, $where, $whereNot);
        self::orderby(['hot' => 'count'], 'id');
        self::where();
        return self::getResource(__METHOD__);
    }

    public static function resource($idsArray = null)
    {
        $vars = self::$vars;
        $resource = FavoriteModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        $resource = FavoriteApp::items($vars, $resource);
        return $resource;
    }

    public static function data($vars = null)
    {
        $whereNot = [];
        $resource = [];
        $model = FavoriteDataModel::field('id');

        $vars['fid'] && $where['fid'] = $vars['fid'];
        isset($vars['iid']) && $where['iid'] = $vars['iid'];
        isset($vars['appid']) && $where['appid'] = $vars['appid'];

        if (isset($vars['userid'])) {
            $userid = $vars['userid'];
            $userid == '@me' && $userid = User::$id ?: 0;
            $where['userid'] = $userid;
        }

        self::orderby([], 'id');
        self::where();

        $whereNot && $model->where($whereNot);
        $where && $model->where($where);
        $hash = md5($model->getSql());

        self::paging($hash, __CLASS__);

        $resource = self::getCache(self::$cacheName);
        if (empty($resource)) {
            $idsArray = self::getIds();
            if ($idsArray) {
                $resource = FavoriteDataModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
                if (!isset($vars['loop']) && isset($vars['column'])) {
                    $column = array_column($resource, $vars['column'], 'id');
                    return $column;
                }
                $userInfo = User::column($resource, 'userid');
                if ($resource) {
                    foreach ($resource as $key => $value) {
                        if ($userInfo) {
                            $value['user'] = (array) $userInfo[$value['userid']];
                        }
                        $value['param'] = [
                            'id'       => $value['id'],
                            'fid'      => $value['fid'],
                            'appid'    => $value['appid'],
                            'iid'      => $value['iid'],
                            'userid'   => $value['userid'],
                            'title'    => $value['title'],
                            'url'      => $value['url'],
                        ];
                        $resource[$key] = $value;
                    }
                }
            }
            $cacheTime = isset($vars['time']) ? (int) $vars['time'] : -1;
            $vars['cache'] && Cache::set(self::$cacheName, $resource, $cacheTime);
        }
        return $resource;
    }
}
