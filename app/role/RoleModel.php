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

class RoleModel extends Model
{
    public static $typeMap = array(
        Role::USER   => '会员等级',
        Role::ADMIN => '管理组',
    );
    protected $casts = [
        'config' => 'array',
        'access' => 'array',
    ];
    
}
