<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class Vote 
{
    public static function add($app, $userid, $appid, $id, $event)
    {
        $model = Apps::model($app);
        $model = $model->where($id);
        $data = [];
        if ($utId = UserTimeline::id($appid, $id, $event)) {
            $model->where($event, '>', 0)->dec($event);
            UserTimeline::delete($utId);
            $data['num'] = -1;
        } else {
            $model->inc($event);
            UserTimeline::add($appid, $id, $event, $userid);
            $data['num'] = +1;
        }
        return $data;
    }
}
