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

class AppsStoreModel extends Model
{

    public static $typeMap = array(
        '0' => '应用',
        '1' => '模板',
        '2' => '插件',
        '3' => '表单'
    );
    protected $casts = [
        'files'    => 'array',
    ];
}
