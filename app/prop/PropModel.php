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

class PropModel extends Model
{
    // protected $casts = [
    //     'info' => 'array',
    // ];
    public static function changed($response, $event, $model)
    {
        $event == 'updated' && $model->field('id,cid')->get();
        $id = $model->getResponse('id');
        $appid = iCMS_APP_PROP;
        if (isset($response['cid'])) {
            /** 栏目变化 */
            Node::change('cid', $response['cid'], $event, $id, $appid);
        }
    }
    public static function deleted($response, $model)
    {
        $row = $model->field('id,cid')->get();
        $iid = $row['id'];
        $appid = iCMS_APP_PROP;
        if ($iid) {
            AppsMap::delete($appid, $iid, 'Node');
        }
    }
}
