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

class FavoriteApp
{
    public $methods    = array('add', 'delete', 'create', 'list', 'data');
    public function __construct()
    {
        $this->id = (int) $_GET['id'];
        UserCP::status();
    }

    public function API_list()
    {
        $array = favoriteFunc::lists(array('userid' => User::$id));
        $appid = (int) $_POST['appid'];
        $iid   = (int) $_POST['iid'];
        $cid   = (int) $_POST['cid'];
        $suid  = (int) $_POST['suid'];
        $row   = FavoriteDataModel::get(compact("appid", "iid"));
        $fids  = array_column($row, 'fid', 'id');

        if ($array) foreach ($array as $key => &$value) {
            $value['favorited'] = false;
            if (array_search($value['id'], $fids) !== false) {
                $value['favorited'] = true;
            }
        }
        iJson::display($array);
    }
    public function API_data()
    {
        $uid     = User::$id;
        $fid     = (int) $_POST['fid'];
        $appid   = (int) $_POST['appid'];
        $iid     = (int) $_POST['iid'];
        $cid     = (int) $_POST['cid'];
        $suid    = (int) $_POST['suid'];
        $url     = Request::post('url');

        isset($_POST['appid']) && $where['appid'] = " AND `appid`='$appid'";
        isset($_POST['iid'])  && $where['iid'] = " AND `iid`='$iid'";
        isset($_POST['fid'])  && $where['fid'] = " AND `fid`='$fid'";
        isset($_POST['url'])  && $where['url'] = " AND `url`='$url'";
        $where['uid'] = $uid;
        $array['favorited']  = FavoriteDataModel::field('id')->where($where)->value();
        $array['count'] = FavoriteDataModel::where($where)->count();
        iJson::display($array);
    }
    /**
     * [do_delete 删除收藏]
     */
    public function do_delete()
    {

        $uid     = User::$id;
        $fid     = (int) $_POST['fid'];
        $id      = (int) $_POST['id'];
        $appid   = (int) $_POST['appid'];
        $iid     = (int) $_POST['iid'];
        $cid     = (int) $_POST['cid'];
        $suid    = (int) $_POST['suid'];
        $title   = Request::post('title');
        $url     = Request::post('url');

        // if(isset($_POST['fid'])){
        //     empty($fid) && Script::code(0,'iCMS:error',0,'json');
        // }
        // if(isset($_POST['url']) || empty($id)){
        //     Script::code(0,'iCMS:error',0,'json');
        // }

        $where['uid'] = $uid;
        isset($_POST['appid']) && $where['appid'] = $appid;
        isset($_POST['fid'])  && $where['fid'] = $fid;
        isset($_POST['iid'])  && $where['iid'] = $iid;
        isset($_POST['url'])  && $where['url'] = $url;

        if ($appid && ($iid || $url)) {
            FavoriteDataModel::where($where)->delete();
            Apps::updateDec('favorite', $iid, $appid);
            User::updateDec('favorite', $uid);
            Favorite::updateDec('count', $fid, $uid);
            Script::code(1, 0, 0, 'json');
        } else {
            Script::code(0, 0, 0, 'json');
        }
    }
    /**
     * [ACTION_add 添加到收藏夹]
     */
    public function ACTION_add()
    {
        $post = Request::post();
        $post['param'] or iJson::error('iCMS:empty:param');
		$param = is_array($post['param']) ? $post['param'] : json_decode($post['param'], true);
        $app = Security::safeStr($param['app']);

        $uid     = User::$id;
        $iid     = (int) $param['iid'];
        $cid     = (int) $param['cid'];
        $suid    = (int) $param['suid'];
        $id      = (int) $param['id'];
        $fid     = (int) $param['fid'];
        $appid   = (int) $param['appid'];
        $title   = $param['title'];
        $url     = $param['url'];
        $addtime = time();
        $id or iJson::error('iCMS:empty:id');

        $where['uid'] = $uid;
        $appid && $where['appid'] = $appid;
        $fid  && $where['fid'] = $fid;
        $iid  && $where['iid'] = $iid;
        $url  && $where['url'] = $url;

        try {
            $id  = FavoriteDataModel::field('id')->where($where)->value();
            if ($id) {
                // $id && iJson::error('favorite:already');
                Apps::updateDec('favorite', $iid, $appid);
                User::updateDec('favorite', $uid);
                Favorite::updateDec('count', $fid, $uid);
                FavoriteDataModel::delete($id);
                iJson::success([0], 'favorite:cancel');
            }else{
                $fields = array('uid', 'appid', 'fid', 'iid', 'url', 'title', 'addtime');
                $data   = compact($fields);
                $id   = FavoriteDataModel::create($data, true);
                Apps::updateInc('favorite', $iid, $appid);
                User::updateInc('favorite', $uid);
                Favorite::updateInc('count', $fid, $uid);
                iJson::success([$id], 'favorite:success');
            }
        } catch (\sException $ex) {
            // iJson::$exception = $ex->getMessage();
            iJson::error($ex->getMessage());
        }
    }
    /**
     * [ACTION_create 创建新收藏夹]
     */
    public function ACTION_create()
    {

        $uid         = User::$id;
        $nickname    = User::$nickname;
        $title       = Request::post('title');
        $description = Request::post('description');
        $mode        = (int) $_POST['mode'];

        empty($title) && Script::code(0, 'favorite:create_empty', 0, 'json');
        $fwd  = iPHP::callback('Filter::run', array(&$title), false);
        $fwd && Script::code(0, 'favorite:create_filter', 0, 'json');

        if ($description) {
            $fwd  = iPHP::callback('Filter::run', array(&$description), false);
            $fwd && Script::code(0, 'favorite:create_filter', 0, 'json');
        }

        $max  = FavoriteModel::where(['uid' => $uid])->count();
        $max >= Config::get("favorite.max") && Script::code(0, 'favorite:create_max', 0, 'json');
        $count  = 0;
        $follow = 0;
        $fields = array('uid', 'nickname', 'title', 'description', 'follow', 'count', 'mode');
        $data   = compact($fields);
        $cid    = FavoriteModel::create($data, true);
        $cid && Script::code(1, 'favorite:create_success', $cid, 'json');
        Script::code(0, 'favorite:create_failure', 0, 'json');
    }
    public static function items($vars, $variable)
    {
        foreach ($variable as $key => &$value) {
            $value['url']  = Route::routing('favorite/{id}', [$value['id']]);
            $vars['user'] && $value['user'] = User::info($value['uid'], $value['nickname']);
        }
        return $variable;
    }
}
