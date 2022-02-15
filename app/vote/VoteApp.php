<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class VoteApp extends AppsApp
{
    public $methods = array(iPHP_APP);
    public function __construct()
    {
        // parent::__construct('vote');
    }
    public static function ACTION_add()
    {
        UserCP::status();

        $post = Request::post();
        $post['param'] or iJson::error('iCMS:empty:param');
		$param = is_array($post['param']) ? $post['param'] : json_decode($post['param'], true);
        $event = Request::spost('event');
        $id = (int) $param['id'];
        $appid = (int) $param['appid'];
        $userid = (int) User::$id;
        $app = Security::safeStr($param['app']);

        $id or iJson::error('iCMS:empty:id');
        $app or iJson::error('iCMS:empty:app');

        $modelName = $app . 'Model';
        // var_dump($modelName);
        $model = new $modelName;
        self::add($model, $userid, $appid, $id, $event);
    }
    public static function add($model, $userid, $appid, $id, $event)
    {
        $model = $model->where($id);
        if ($utId = UserTimeline::id($appid, $id, $event)) {
            $model->where($event, '>', 0)->dec($event);
            UserTimeline::delete($utId);
            $c = -1;
        } else {
            $model->inc($event);
            UserTimeline::add($appid, $id, $event, $userid);
            $c = +1;
        }
        iJson::success(
            [$c],
            $c > 0 ?
                'vote:' . $event . ':success' :
                'vote:' . $event . ':cancel'
        );
    }
}
