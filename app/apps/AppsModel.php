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
    protected $casts = [
        'table'  => 'array',
        'config' => 'array',
        'fields' => 'array',
        'route' => 'array',
    ];
}