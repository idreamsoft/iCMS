<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
function iCMS_pages($vars)
{
	$conf = Pages::$config;
	if (empty($conf)) {
		return;
	}
	View::parseVars($vars);
	View::unfuncVars($vars);

	// $as = $vars['as'];
	// $array = $vars['array'];
	// $print = $vars['print'];
	// unset($vars['as'],$vars['array'],$vars['print']);

	if ($vars['lang']) {
		$conf['lang'] = array_merge($conf['lang'], $vars['lang']);
		unset($vars['lang']);
	}

	$conf = array_merge($conf, $vars);

	if (isset($vars['url'])) {
		$conf['url'] = $vars['url'];
		if (strtolower($vars['url']) === 'self') {
			$conf['url'] = $_SERVER['REQUEST_URI'];
		}
		$query = array('page' => '{P}');
		$vars['query'] && $query = array_merge($query, $vars['query']);
		Pages::$setting['index'] = Route::make(array('page' => null), $conf['url']);
		$conf['url'] = Route::make($query, $conf['url']);
	}
	$obj = Paging::make($conf);
	if (isset($vars['show'])) {
		echo $obj->show($vars['show']);
	}
	return $obj;
}
