<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
function iCMS_route($vars){
	if(empty($vars['url'])){
		echo 'javascript:;';
		return;
	}

	$print = isset($vars['print'])?$vars['print']:true;

	$as    = $vars['as'];
	$url   = $vars['url'];
	$query = $vars['query'];
	$host = $vars['host'];
    if ($url==='self') {
		$url = $_SERVER["REQUEST_URL"];
    }
	if(isset($vars['set'])){
		$GLOBALS['iCMS:route'] = $vars;
		return;
	}
	// View::assign('Route',null);
	if($url==$GLOBALS['iCMS:route']['url']){
		View::assign('Route',$GLOBALS['iCMS:route']);
	}
	unset($vars['url'],$vars['as'],$vars['print'],$vars['get'],$vars['host'],$vars['query']);

    if (!Request::isUrl($url)) {
        $url = Route::routing($url);
	}
	$query && $url = Route::make($query,$url);
	$vars && $url = Route::make($vars,$url);
	
	if($url && !Request::isUrl($url) && $host){
		$url = rtrim(iCMS_URL,'/').'/'.ltrim($url, '/');;
	}
	empty($url) && $url = 'javascript:;';

	if($as) return $url;

	$print && print($url);
	return $url;
}
