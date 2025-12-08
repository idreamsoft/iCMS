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

class CommentModel extends Model
{
    const  EXAMINE = 3;
    const  USER_DELETE = 5;
    public static $statusMap = array(
        '0' => '草稿',
        '1' => '正常',
        '2' => '回收站',
        self::EXAMINE => '待审核',
        '4' => '未通过',
        self::USER_DELETE => '用户删除'
    );
}