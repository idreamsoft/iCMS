<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class Favorite
{
    const APP = 'favorite';
    const APPID = iCMS_APP_FAVORITE;

    public static function default()
    {
        $data = [
            'userid'  => User::$id,
            'title'   => '默认收藏夹',
            'mode'    => 0,
            'status'  => 2,
        ];
        $id = FavoriteModel::field('id')->where($data)->value();
        if (empty($id)) {
            $data['nickname'] = User::$nickname;
            $data['created'] = time();
            $id = FavoriteModel::create($data, true);
        }
        return $id;
    }

    public static function check($iid, $userid, $appid)
    {
        $where = compact('userid', 'iid', 'appid');
        $id = FavoriteDataModel::field('id')->where($where)->value();
        return $id ? true : false;
    }

    public static function delete($id = 0, $userid = 0)
    {
        $userid && $where['userid'] = $userid;
        FavoriteModel::where($where)->delete($id);
        $where['fid'] = $id;
        FavoriteDataModel::where($where)->delete();
        FavoriteFollowModel::where($where)->delete();
    }

    public static function updateInc($field, $id, $userid = 0, $step = 1)
    {
        return self::updateCount($field, $id, $userid, $step, 'inc');
    }

    public static function updateDec($field, $id, $userid = 0, $step = 1)
    {
        return self::updateCount($field, $id, $userid, $step, 'dec');
    }

    public static function updateCount($field, $id, $userid = 0, $step = 1, $func = 'inc')
    {
        $where = compact('id');
        $userid && $where['userid'] = $userid;
        return FavoriteModel::where($id)->$func($field, $step);
    }
}
