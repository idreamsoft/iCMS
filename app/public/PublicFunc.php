<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PublicFunc
{
	public static function ui($vars = null)
	{
		View::assign("public_ui", $vars);
		View::display("iCMS://public.ui.htm");
	}
	public static function captcha($vars = null)
	{
		echo PublicApp::captcha($vars);
	}
	public static function dialog($vars = null)
	{
		View::assign("public_dialog", $vars);
		View::display("iCMS://public.dialog.htm");
	}
	public static function crontab($vars = null)
	{
		$url = Route::make('app=public&do=crontab', 'route::api');
		$html = '<img src="' . $url . '" style="display: none;" />';
		if ($vars === true) {
			return $html;
		}
		echo $html;
	}
	public static function qrcode($vars = null)
	{
		if ($vars['base64']) {
			echo PublicApp::qrcode_base64($vars['data']);
		} else {
			echo PublicApp::qrcode_url($vars['data']);
		}
	}
}
