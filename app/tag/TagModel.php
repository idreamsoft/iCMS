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

class TagModel extends Model
{
    public static $statusMap = array(
        '0' => '禁用',
        '1' => '正常',
        '2' => '回收站',
    );
    protected $casts = [
        // 'pid'     => 'array',
    ];
    protected $events = [
        'changed' => ['TagEvent', 'changed'],
        'delete' => ['TagEvent', 'delete'],
    ];
}