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

class MessageModel extends Model
{
	public static $typeMap = array(
		'0' => '系统信息',
		'1' => '私信',
		'2' => '提醒',
		'3' => '留言',
	);
}
