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

class UserTimeline extends Model
{
    public static $eventMap = [
        'good' => '1',
        'bad' => '2',
        'comment' => '3',
        'favorite' => '4',
        'up' => '5',
        'down' => '6',
    ];
    public static function id($appid, $iid, $event)
    {
        is_numeric($event) or $event = self::$eventMap[$event];
        $where  = compact('appid', 'iid', 'event');
        return self::field('id')->where($where)->value();
    }
    public static function del($appid, $iid, $event)
    {
        is_numeric($event) or $event = self::$eventMap[$event];
        $where  = compact('appid', 'iid', 'event');
        return self::where($where)->delete();
    }
    public static function add($appid, $iid, $event, $uid = 0)
    {
        is_numeric($event) or $event = self::$eventMap[$event];

        $userid = User::$id;
        $ip = Request::ip();
        $create_time = time();
        $status = 1;
        $uid = (int)$uid;
        $data = compact('userid', 'appid', 'iid', 'uid', 'event',  'ip', 'create_time', 'status');
        $data['id'] = self::create($data, true);
        return $data;
    }
}
