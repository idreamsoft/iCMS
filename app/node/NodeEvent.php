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

class NodeEvent extends DBEvent
{
    public static $appid = iCMS_APP_NODE;

    public static function changed($response, $Builder, $event, $isMulti)
    {
        if ($event == 'updated') {
            $idArr = $Builder->field('id')->pluck();
        } else {
            $idArr = [$response['id']];
        }
        array_map(function ($id) use ($response, $Builder, $event, $isMulti) {
            if (!$id) {
                return;
            }
            if (isset($response['pid'])) {
                /** 属性变化 */
                AppsMap::change('pid', self::$appid, $response['pid'], $event, $id, 'Prop');
            }
            //缩略图变化
            if (isset($response['pic']) || isset($response['bpic']) || isset($response['mpic']) || isset($response['spic'])) {
                FilesPic::change($response, self::$appid, $event, $id);
            }
        }, $idArr);
    }

    public static function deleted($response, $Builder, $event, $isMulti)
    {
        $idArr = $Builder->field('id')->pluck();
        array_map(function ($id) {
            if (!$id) {
                return;
            }
            AppsMap::delete(self::$appid, $id, 'Prop');
            Files::delete(self::$appid, $id);
        }, $idArr);
    }
}
