<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
function iCMS_cache($vars = null)
{
	if (empty($vars['key'])) {
		return false;
	}
	if (isset($vars['value'])) {
		$time = isset($vars['time']) ? $vars['time'] : 0;
		Cache::set($vars['key'], $vars['value'], $time);
	}
	$skey = isset($vars['skey']) ? $vars['skey'] : null;
	return Cache::get($vars['key'], $skey);
}
