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
        'good'           => '1',
        'bad'            => '2',
        'comment'        => '3',
        'favorite'       => '4',
        'up'             => '5',
        'down'           => '6',
        'reply_up'       => '7',
        'reply_down'     => '8',
    ];
    public static $eventNames = [
        'good'           => 'cms:vote:good',
        'bad'            => 'cms:vote:bad',
        'comment'        => 'cms:comment:add',
        'favorite'       => 'cms:favorite:dialog',
        'up'             => 'cms:comment:up',
        'down'           => 'cms:comment:down',
        'reply_up'       => 'cms:comment:reply:up',
        'reply_down'     => 'cms:comment:reply:down',
    ];

    public static function user_iid_events($event, $appid, $userid, $iid)
    {
        return self::field('id,event')->where([
            'appid' => $appid,
            'iid'   => $iid,
            'userid'=> $userid,
            'event' => $event,
        ])->pluck('event', 'id');
    }
    /**
     * Undocumented function
     *
     * @param array $eventArr ['up', 'down']||['reply_up'=>'up', 'reply_down'=>'down']
     * @param [type] $appid
     * @param [type] $userid
     * @return void
     */
    public static function user_events($event, $appid, $userid = null)
    {
        $data = [];
        // $data = array_fill_keys($event,false);
        foreach ($event as $key => $value) {
            if (is_numeric($key)) {
                unset($event[$key]);
                $event[$value] = $value;
            }
        }
        is_null($userid) && $userid = User::$id;
        if ($userid) {
            $timeLines = UserTimeline::field('id,iid,event')->where([
                'userid'=> $userid,
                'appid' => $appid,
                'event' => array_keys($event),
            ])->select();
            foreach ($timeLines as $k => $v) {
                $key = sprintf('is_%s', $v['event']);
                if ($ek = $event[$v['event']]) {
                    $key = sprintf('is_%s', $ek);
                }
                $data[$v['iid']][$key] = $v['id'];
            }
        }
        return $data;
    }

    public static function events($appid, $iid, $userid)
    {
        $where = compact('appid', 'iid', 'userid');
        return self::field('id,event')->where($where)->pluck('event', 'id');
    }

    public static function ids($appid, $iid, $event)
    {
        $where = compact('appid', 'iid', 'event');
        return self::field('id,event')->where($where)->pluck('event', 'id');
    }

    public static function id($appid, $iid, $event)
    {
        $where = compact('appid', 'iid', 'event');
        return self::field('id')->where($where)->value();
    }

    public static function del($appid, $iid, $event)
    {
        $where = compact('appid', 'iid', 'event');
        return self::where($where)->delete();
    }

    public static function add($appid, $iid, $event, $uid = 0)
    {
        $eventId = self::$eventMap[$event];
        $userid = User::$id;
        $ip = Request::ip();
        $create_time = time();
        $status = 1;
        $uid = (int)$uid;
        $data = compact('userid', 'appid', 'iid', 'uid', 'eventId', 'event', 'ip', 'create_time', 'status');
        $data['id'] = self::create($data, true);
        return $data;
    }
}
