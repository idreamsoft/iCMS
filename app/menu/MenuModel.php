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

class MenuModel extends Model
{
    protected $table = 'menu';
    
    protected $casts = [
        'request_config' => 'array',
        'popup_config' => 'array',
        'module_config' => 'array',
    ];

    public static $nodeTypeMap = [
        'dir' => '目录',
        'menu' => '菜单',
        'button' => '按钮',
    ];

    public static $actionTypeMap = [
        'page' => '页面',
        'request' => '请求',
        'popup' => '弹窗',
        'module' => '模块',
    ];
}