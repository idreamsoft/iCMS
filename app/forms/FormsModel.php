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

class FormsModel extends Model
{
    protected $casts = [
        'pubdate' => 'datetime',
        'table'   => 'array',
        'config'  => 'array',
        'fields'  => 'array'
    ];
    protected $events = [
        // 'geted' => ['ArticleEvent', 'geted'],
        // 'created' => ['ArticleEvent', 'created'],
        // 'updated' => ['ArticleEvent', 'updated'],
        'delete' => ['FormsEvent', 'delete'],//删除前
        // 'deleted' => ['ArticleEvent', 'deleted'],//删除后
        'changed' => ['FormsEvent', 'changed'],//created + updated
    ];

}
