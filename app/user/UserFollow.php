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

class UserFollow
{
    public static function is($userid = 0, $fuid = 0)
    {
        $fuid = UserFollowModel::field('fuid')->where(compact('userid', 'fuid'))->value();
        return $fuid ? $fuid : false;
    }

    public static function gets($userid = 0, $fuid = 0)
    {
        if ($userid === 'all') { //all fans
            $model = UserFollowModel::field('userid,name')->where('fuid', $fuid);
        } elseif ($fuid === 'all') { // all follow
            $model = UserFollowModel::field('fuid AS userid', 'fname AS name')->where('userid', $userid);
        }
        $data = $model->pluck('name', 'userid');
        return $data;
    }
}
