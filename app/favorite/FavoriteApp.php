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

class FavoriteApp extends AppsBase
{
    public static $LOGIN = true;

    //cms:favorite:dialog
    public function done_dialog()
    {
        $params = Request::post('params');
        $template = Request::post('template');
        $userid = User::$id;
        if ($template == 'dialog.list') {
            Favorite::default();
            // $id = (int)$params['id'];
            // $appid = (int)$params['appid'];
            // $favorited = Favorite::check($id, $userid, $appid);
            // View::assign('comment_vlist', $params);
            $html = View::fetch('iCMS://favorite/dialog.list.htm');
            return compact('html');
        }
        if ($template == 'dialog.create') {
            $html = View::fetch('iCMS://favorite/dialog.create.htm');
        }
        return compact('html');
    }

    // public function API_list()
    // {
    //     $array = favoriteFunc::lists(['userid' => User::$id]);
    //     $appid = (int) $_POST['appid'];
    //     $iid = (int) $_POST['iid'];
    //     $cid = (int) $_POST['cid'];
    //     $suid = (int) $_POST['suid'];
    //     $row = FavoriteDataModel::get(compact('appid', 'iid'));
    //     $fids = array_column($row, 'fid', 'id');

    //     if ($array) {
    //         foreach ($array as $key => &$value) {
    //             $value['favorited'] = false;
    //             if (array_search($value['id'], $fids) !== false) {
    //                 $value['favorited'] = true;
    //             }
    //         }
    //     }
    //     iJson::display($array);
    // }

    // public function API_data()
    // {
    //     $uid = User::$id;
    //     $fid = (int) $_POST['fid'];
    //     $appid = (int) $_POST['appid'];
    //     $iid = (int) $_POST['iid'];
    //     $cid = (int) $_POST['cid'];
    //     $suid = (int) $_POST['suid'];
    //     $url = Request::post('url');

    //     isset($_POST['appid']) && $where['appid'] = " AND `appid`='$appid'";
    //     isset($_POST['iid']) && $where['iid'] = " AND `iid`='$iid'";
    //     isset($_POST['fid']) && $where['fid'] = " AND `fid`='$fid'";
    //     isset($_POST['url']) && $where['url'] = " AND `url`='$url'";
    //     $where['uid'] = $uid;
    //     $array['favorited'] = FavoriteDataModel::field('id')->where($where)->value();
    //     $array['count'] = FavoriteDataModel::where($where)->count();
    //     iJson::display($array);
    // }

    // /**
    //  * do_delete 删除收藏
    //  */
    // public function do_delete()
    // {
    //     $uid = User::$id;
    //     $fid = (int) $_POST['fid'];
    //     $id = (int) $_POST['id'];
    //     $appid = (int) $_POST['appid'];
    //     $iid = (int) $_POST['iid'];
    //     $cid = (int) $_POST['cid'];
    //     $suid = (int) $_POST['suid'];
    //     $title = Request::post('title');
    //     $url = Request::post('url');

    //     // if(isset($_POST['fid'])){
    //     //     empty($fid) && Script::code(0,'iCMS:error',0,'json');
    //     // }
    //     // if(isset($_POST['url']) || empty($id)){
    //     //     Script::code(0,'iCMS:error',0,'json');
    //     // }

    //     $where['uid'] = $uid;
    //     isset($_POST['appid']) && $where['appid'] = $appid;
    //     isset($_POST['fid']) && $where['fid'] = $fid;
    //     isset($_POST['iid']) && $where['iid'] = $iid;
    //     isset($_POST['url']) && $where['url'] = $url;

    //     if ($appid && ($iid || $url)) {
    //         FavoriteDataModel::where($where)->delete();
    //         Apps::updateDec('favorite', $iid, $appid);
    //         User::updateDec('favorite', $uid);
    //         Favorite::updateDec('count', $fid, $uid);
    //         Script::code(1, 0, 0, 'json');
    //     } else {
    //         self::alert(0, 0, 'json');
    //     }
    // }

    /**
     * 添加到收藏夹
     */
    public function api_add()
    {
        $params = Request::post('params');
        $fid = (int)Request::post('fid');
        $params or self::alert('errors:empty:params');

        $app = Security::safeStr($params['app']);
        $userid = User::$id;
        $iid = (int) $params['iid'];
        $cid = (int) $params['cid'];
        $suid = (int) $params['suid'];
        $id = (int) $params['id'];
        $appid = (int) $params['appid'];
        $title = $params['title'];
        $url = $params['url'];
        $addtime = time();
        $id or self::alert('errors:empty:id');

        $where['userid'] = $userid;
        $appid && $where['appid'] = $appid;
        $fid && $where['fid'] = $fid;
        $iid && $where['iid'] = $iid;
        $url && $where['url'] = $url;

        $id = FavoriteDataModel::field('id')->where($where)->value();
        if ($id) {
            Apps::updateDec('favorite', $iid, $appid);
            User::updateDec('favorite', $userid);
            Favorite::updateDec('count', $fid, $userid);
            FavoriteDataModel::delete($id);
            return [['id'=>0], 'FAVORITE:cancel'];
        } else {
            $fields = ['userid', 'appid', 'fid', 'iid', 'url', 'title', 'addtime'];
            $data = compact($fields);
            $id = FavoriteDataModel::create($data, true);
            Apps::updateInc('favorite', $iid, $appid);
            User::updateInc('favorite', $userid);
            Favorite::updateInc('count', $fid, $userid);
            return [['id'=>$id], 'FAVORITE:success'];
        }
    }

    /**
     * cms:favorite:vlist
     *
     * @return array
     */
    public function api_vlist()
    {
        $params = Request::post('params') ?: [];
        $vars = Request::post('vars') ?: [];
        $vars = array_filter_keys($vars, 'iid,appid,userid,page,row,page_ajax,page_style,status', true);
        $option = array_merge([
            'iid'       => $params['iid'],
            'appid'     => $params['appid'],
            'orderby'   => 'status DESC,id ASC',
            'row'       => 10,
            'userid'    => '@me',
            'show_active' => true,
        ], $vars);
        $rows = FavoriteFunc::lists($option);
        $page = [];
        if ($PAGES = FavoriteFunc::$PAGES) {
            $page = $PAGES->vars();
        }
        $pageData = FavoriteFunc::$pageData;

        $rows && $rows = array_map(function ($row) use ($vars) {
            return $row;
        }, $rows);
        $page = array_filter_keys($page, 'FIRST,LAST,NEXT,PREV', false);
        return ['rows'=>$rows, 'pageData'=>$pageData, 'page'=>$page];
    }

    /**
     * 创建新收藏夹
     */
    public function done_create()
    {
        $userid = User::$id;
        $nickname = User::$nickname;
        $title = Request::post('title');
        $description = Request::post('description');
        $mode = (int)Request::post('mode');

        empty($title) && self::alert('FAVORITE:create_empty');
        $fwd = iPHP::callback('Filter::run', [&$title], false);
        $fwd && self::alert('FAVORITE:create_filter');

        if ($description) {
            $fwd = iPHP::callback('Filter::run', [&$description], false);
            $fwd && self::alert('FAVORITE:create_filter');
        }
        $userMax = Config::get('user.favorite.max') ?: 10;
        $max = FavoriteModel::where(['userid' => $userid])->count();
        $max >= $userMax && self::alert('FAVORITE:create_max');
        $count = 0;
        $follow = 0;
        $fields = ['userid', 'nickname', 'title', 'description', 'follow', 'count', 'mode'];
        $data = compact($fields);
        $cid = FavoriteModel::create($data, true);
        return [true, 'FAVORITE:create_success'];
    }

    public static function items($vars, $resource)
    {
        $FavoriteData = [];
        if ($vars['show_active'] && $vars['iid']) {
            $FavoriteData = FavoriteDataModel::field('id,fid')->where(['iid'=>$vars['iid']])->pluck('fid', 'id');
        }
        $userInfo = User::column($resource, 'userid', 'nickname');
        foreach ($resource as $key => &$value) {
            $value['active'] = false;
            if ($FavoriteData[$value['id']]) {
                $value['active'] = 'active';
            }
            $value['url'] = Route::routing('favorite/{id}', [$value['id']]);
            empty($value['user']) && $value['user'] = $userInfo[$value['userid']];
        }
        return $resource;
    }
}
