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

class SpiderUrlModel extends Model
{
	public static $statusMap = [
		0 => '未采集', 1 => '正常采集', 2 => '采集错误'
	];
	public static $publishMap = [
		0 => '未发布', 1 => '已发布', 2 => '标记移除'
	];
}
