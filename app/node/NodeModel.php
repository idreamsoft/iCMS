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

class NodeModel extends Model
{
    public static $statusMap = array(
        '0' => '隐藏',
        '1' => '正常',
        '2' => '虚拟',
    );
    protected $casts = [
        // 'pid'      => 'array',
        'rule'     => 'array',
        'template' => 'array',
        'config'   => 'array',
    ];
    protected $events = [
        'changed'     => ['NodeEvent', 'changed'],
        'deleted'     => ['NodeEvent', 'deleted'],
    ];
    public static function setTable($app)
    {
        $table = 'node';
        $app && $table = $app . '_' . $table;
        $instance = self::getInstance();
        $instance->table = $table;
        return $instance;
    }
}
