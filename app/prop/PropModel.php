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
    public static $statusMap = ['禁用', '启用'];

    // protected $casts = [
    //     'info' => 'array',
    // ];
    public static $appid = iCMS_APP_PROP;

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
            if (isset($response['cid'])) {
                /** 栏目变化 */
                Node::change('cid', $response['cid'], $event, $id, self::$appid);
            }
        }, $idArr);
    }

    /**
     * 删除前
     *
     * @param [type] $response
     * @param [type] $Builder
     * @param [type] $event
     * @param [type] $isMulti
     * @return void
     */
    public static function delete($response, $Builder, $event, $isMulti)
    {
        $idArr = $Builder->field('id')->pluck();
        array_map(function ($id) {
            if (!$id) {
                return;
            }
            AppsMap::delete(self::$appid, $id, 'Node');
        }, $idArr);
    }
}
