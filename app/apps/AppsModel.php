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

class AppsModel extends Model
{
    public static $appTypeMap = array(
        '4' => '第三方应用',
        '3' => '自定义应用',
        '2' => '官方应用',
        '1' => '系统应用',
        '0' => '基础组件',
    );
    public static $typeMap = array(
        '4' => '第三方应用',
        '3' => '自定义应用',
        '2' => '官方应用',
        '1' => '系统应用',
        '0' => '基础组件',
    );
    protected $casts = [
        'table'  => 'array',
        'config' => 'array',
        'fields' => 'array',
        'route' => 'array',
    ];
}