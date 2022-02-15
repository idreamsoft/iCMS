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

class NodeEvent
{
    public static function changed($response, $event, $model)
    {
        $event == 'updated' && $model->field('id')->get();
        $id = $model->getResponse('id');
        $appid = iCMS_APP_NODE;
        if (isset($response['pid'])) {
            /** 属性变化 */
            AppsMap::change('pid', $appid, $response['pid'], $event, $id, 'Prop');
        }
        //缩略图变化
        if (isset($response['pic']) || isset($response['bpic']) || isset($response['mpic']) || isset($response['spic'])) {
            FilesPic::change($response, $appid, $event, $id);
        }
    }
    public static function deleted($response, $model)
    {
        $row = $model->field('id')->get();
        $iid = $row['id'];
        $appid = iCMS_APP_NODE;
        if ($iid) {
            AppsMap::delete($appid, $iid, 'Prop');
            Files::delete($appid, $iid);
        }
    }
}
