<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
function iCMS_config($vars = null)
{
	$config = Config::get();
	$vars['name'] && $config = $config[$vars['name']];
	if (isset($vars['key']) && is_array($config)) {
		$config = $config[$vars['key']];
	}
	return $config;
}
