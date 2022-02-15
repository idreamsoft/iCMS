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

    public static function check($iid, $uid, $appid)
    {
        $where = compact('uid', 'iid', 'appid');
        $id  = FavoriteDataModel::field('id')->where($where)->value();
        return $id ? true : false;
    }
    public static function delete($id = 0, $uid = 0)
    {
        $uid && $where['uid'] = $uid;
        FavoriteModel::where($where)->delete($id);
        $where['fid'] = $id;
        FavoriteDataModel::where($where)->delete();
        FavoriteFollowModel::where($where)->delete();
    }
    public static function updateInc($field, $id, $uid = 0, $step = 1)
    {
        return self::updateCount($field, $id, $uid, $step, 'inc');
    }
    public static function updateDec($field, $id, $uid = 0, $step = 1)
    {
        return self::updateCount($field, $id, $uid, $step, 'dec');
    }
    public static function updateCount($field, $id, $uid = 0, $step = 1, $func = 'inc')
    {
        $where = compact('id');
        $uid && $where['uid'] = $uid;
        return FavoriteModel::where($id)->$func($field, $step);
    }
}
