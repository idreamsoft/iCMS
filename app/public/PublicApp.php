<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PublicApp
{
	public $methods = array(
		'sitemapindex', 'sitemap', 'captcha', 'crontab', 'time', 'qrcode',
		'privacy', 'terms',
		'smscode','password'
	);

	public function ACTION_smscode()
	{
		$phone = Request::post('phone');
		$captcha = Request::post('captcha');

		empty($captcha) && iJson::error('iCMS:captcha:empty');
		Captcha::check($captcha) or iJson::error('iCMS:captcha:error');

		try {
			// PluginSmsCode::$debug = iPHP_DEBUG;
			$result = PluginSmsCode::send($phone);
			iJson::success($result, 'plugin:SMS:success');
		} catch (\sException $ex) {
			$msg = $ex->getMessage();
			iJson::error($msg);
		}
	}
	public function ACTION_emailcode()
	{
		$email = Request::post('email');
		$captcha = Request::post('captcha');

		empty($captcha) && iJson::error('iCMS:captcha:empty');
		Captcha::check($captcha) or iJson::error('iCMS:captcha:error');

		try {
			// PluginEmail::$debug = iPHP_DEBUG;
			$code = random(6, true);
			$active_url = Route::routing('plugin/email/verify', 'code=' . $code);
			$result = PluginEmail::send([
				'subject' => '{title}更改帐号信息的验证码',
				'body' => Lang::get(
					'plugin:email:verify:body',
					['name' => '用户', 'url' => $active_url]
				),
				'address' => [$email, '']
			]);
			iJson::success('plugin:email:success');
		} catch (\sException $ex) {
			$msg = $ex->getMessage();
			iJson::error($msg);
		}
	}
	public function API_privacy()
	{
		View::display('iCMS://user/privacy.htm');
	}
	public function API_terms()
	{
		View::display('iCMS://user/terms.htm');
	}
	public function API_sitemapindex()
	{
		header("Content-type:text/xml");
		View::display('/tools/sitemap.index.htm');
	}
	public function API_sitemap()
	{
		header("Content-type:text/xml");
		View::assign('cid', (int) $_GET['cid']);
		View::display('/tools/sitemap.baidu.htm');
	}
	public function API_resetAdmin()
	{
		View::display('/tools/reset.admin.htm');
		// echo Member::password('123456');
	}
	public function API_crontab()
	{
		exit();
	}

	public function API_captcha()
	{
		$prefix = Request::get('prefix');
		Captcha::get($prefix);
	}

	public function API_qrcode($url = null)
	{
		$url === null && $url = Request::get('url');
		echo PluginQRcode::make($url);
	}
	public static function qrcode_base64($text)
	{
		$image = PluginQRcode::make($text,true);
		return 'data:image/png;base64,' . base64_encode($image);
	}
	public static function qrcode_url($url)
	{
		$query = array(
			'app' => 'public',
			'do'  => 'qrcode',
			'url' => $url
		);
		isset($vars['cache']) && $query['cache'] = true;
		return Route::make($query, 'route::api');
	}
	public static function url($app = null, $query = null)
	{
		if (empty($app)) {
			return iCMS_PUBLIC_URL;
		}

		$url = iCMS_API_URL . ($app ? $app : 'public');
		$query && $url = Route::make($query, $url);
		return $url;
	}
	public static function captcha($vars = [])
	{
		View::assign('public_captcha', $vars);
		return View::fetch('iCMS://public.captcha.htm');
	}
}
