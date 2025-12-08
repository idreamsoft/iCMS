<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class VoteApp extends AppsBase
{
    public static $LOGIN = ['update'];

    public static function api_update()
    {
        $params = Request::post('params');
        $params or self::alert('iCMS:empty:params');
        $event = Request::spost('event');
        return self::update($params, $event);
    }

    public static function update($params, $event)
    {
        $id = (int) $params['id'];
        $appid = (int) $params['appid'];
        $userid = (int) User::$id;
        $app = Security::safeStr($params['app']);

        $id or self::alert('iCMS:empty:id');
        $app or self::alert('iCMS:empty:app');

        $data = Vote::add($app, $userid, $appid, $id, $event);
        $message = $data['num'] > 0 ?
            sprintf('vote:%s:success', $event) :
            sprintf('vote:%s:cancel', $event);
        return compact('data', 'message');
    }
}
