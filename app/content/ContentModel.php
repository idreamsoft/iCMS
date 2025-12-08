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

class ContentModel extends Model
{
    public static $statusMap = array(
        '0' => '草稿',
        '1' => '正常',
        '2' => '回收站',
        '3' => '待审核',
        '4' => '未通过'
    );
    public static $postypeMap = array(
        '0' => '用户',
        '1' => '管理',
    );
    protected $casts = [
        // 'scid'    => 'array',
        // 'pid'     => 'array',
        'picdata' => 'array',
        'related' => 'array',
    ];
    protected $events = [
        'changed' => ['ContentEvent', 'changed'],
        'deleted' => ['ContentEvent', 'deleted'],
    ];
    public static function setEventData($data)
    {
        ContentEvent::$APP_DATA = $data;
    }
    // public static function setTable($app)
    // {
    //     $instance = self::getInstance();
    //     $instance->table = $app;
    //     return $instance;
    // }
}
