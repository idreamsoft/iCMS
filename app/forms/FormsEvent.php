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

class FormsEvent extends DBEvent
{
    public static $appid = iCMS_APP_FORMS;

    public static function changed($response, $Builder, $event, $isMulti)
    {
        if ($event == 'updated') {
            $idArr = $Builder->field('id')->pluck();
        } else {
            $idArr = [$response['id']];
        }
        array_map(function ($id) use ($response, $Builder, $event, $isMulti) {
            if (isset($response['node_id'])) {
                /** 栏目变化 */
                Node::change('node_id', $response['node_id'], $event, $id, self::$appid);
            }
            //缩略图变化
            if (isset($response['pic']) || isset($response['bpic']) || isset($response['mpic']) || isset($response['spic'])) {
                FilesPic::change($response, self::$appid, $event, $id);
            }
        }, $idArr);
    }

    public static function delete($response, $Builder, $event, $isMulti)
    {
        $idArr = $Builder->field('id')->pluck();
        array_map(function ($id) {
            if (!$id) {
                return;
            }
            AppsMap::delete(self::$appid, $id, 'Node');
            Files::delete(self::$appid, $id);
        }, $idArr);
    }
}