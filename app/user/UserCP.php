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

/**
 * User's Control Panel
 */
class UserCP
{
	public $methods = array();
	public $openid = null;

	public function __construct()
	{
		User::init();
		self::forward();
		self::status(true); //
		self::assign(); //模板变量赋值
	}
	public function __destruct()
	{
		iAPP::destruct();
	}
	/**
	 * Undocumented function
	 *
	 * @param boolean $flag=true 未登陆将跳转到登陆页 
	 * @return void
	 */
	public static function status($flag = false)
	{
		if (User::$data) {
			return true;
		} else {
			$url = User::getLoginUrl();
			$message = Lang::get('user:login:not');
			$message.= Lang::get('user:login:link',[$url]);
			Request::isAjax() && iJson::error($message, $url, -9999);
			if (Request::param('frame')) {
				Script::alert($message, 'url:' . $url, 10);
			}
			return $flag ? Helper::redirect($url, true) : false;
		}
	}
	public static function checkRole($array)
	{
		$roleId = User::$data['role_id'];
		if ($array && in_array($roleId, $array)) {
			return true;
		}
		return false;
	}
	public static function assign($ud = true)
	{
		$user = [];
		$status = array('logined' => false, 'followed' => false, 'isme' => false);
		if ($uid = (int) Request::get('uid')) {
			$user = User::get($uid);
			empty($user) && AppsApp::throwError(['user:not_found', [$uid]], 'U10001');
		}
		$me = User::$data; //判断是否登录
		if ($me) {
			$status['logined'] = true;
			$user['uid'] && $status['followed'] = (int) UserFollow::is($me['uid'], $user['uid']);
			empty($user) && $user = $me;
			if ($user['uid'] == $me['uid']) {
				$status['isme'] = true;
				$user = $me;
			}
		}
		if ($user && $user['uid'] != $me['uid'] && $ud) {
			$user['data'] = UserData::gets($user['uid']);
		}
		$token = Security::csrf_token($user['uid'], date("Ymd"));
		View::assign('USER_TOKEN', $token);
		View::assign('USER_CONFIG', User::$config);
		View::assign('USER_STATUS', $status);
		View::assign('user', $user);
		return $user;
	}
	public static $forward = null;
	public static function forward($flag = null)
	{
		self::$forward = Request::param('forward');
		if (empty(self::$forward)) {
			self::$forward = Cookie::get('forward');
			if (empty(self::$forward)) {
				self::$forward = $_SERVER['HTTP_REFERER'];
				self::$forward or self::$forward = iCMS_URL;
			}
			if (strpos(self::$forward, 'forward=') !== false) {
				$query = parse_url(self::$forward, PHP_URL_QUERY);
				parse_str($query, $qs);
				$qs['forward'] && self::$forward = $qs['forward'];
			}
			$flag === 'c' && Cookie::set('forward', self::$forward);
			if ($flag === 'r' && User::$config['forward']) {
				$url = Route::make('forward=' . self::$forward);
				Helper::redirect($url);
			}
		}
		View::assign('forward', self::$forward);
	}
	public static function view($key, $s = null)
	{
		$menu = [];
		$breadcrumb = [];
		$breadcrumb[] = [
			'url' => 'user:' . $key . ':home',
			'caption' => Lang::get('user:' . $key . ':home')
		];
		$menuList = self::usercpMenuCache($key, true);

		if ($menuList) {
			$s = Request::sget('s', 'home');
			$menuIds = array_column($menuList, 'id');
			in_array($s, $menuIds) or AppsApp::throwError('UserCP Illegal access');
			$menu = $menuList[$s] ?: $menuList['home'];
			$menu['id'] != 'home' && $breadcrumb[] = array_filter_keys($menu, 'children', false);
			View::assign('navopen', $s);
		}
		if ($menu['children']) {
			$childrenIds = array_column($menu['children'], 'id');
			$app = Request::sget('app');
			$do = Request::sget('do');
			$ss = $app . ucfirst($do);
			$idx = array_search($ss, $childrenIds);
			$idx === false && AppsApp::throwError('children Illegal access');
			$children = $menu['children'][$idx];
			$breadcrumb[] = $children;
			// View::assign('children', $children);
			// View::assign('template', $children['template']);
			$menu = end($breadcrumb);
		}
		$menu['app'] && View::$app = $menu['app']; //增加 template/paymentApp
		View::assign('menuList', $menuList);
		View::assign('breadcrumb', $breadcrumb);
		View::assign('menu', $menu);
		return View::display("iCMS://user/usercp.htm");
	}
    /**
     * usercp.menu.manage.json
     * usercp.menu.content.json
     *
     * @param [type] $key
     * @return void
     */
    public static function usercpMenuCache($key, $cache = false)
    {
        $idx = 'menu/usercp.' . $key;
        $data = Cache::get($idx);
        if ($cache && $data) {
            return $data;
        }

        $data = Etc::many('*', $idx . '*', true);
        $data = array_column($data, null, 'id');
        // if ($data) foreach ($data as $key => &$value) {
        //     $value['url'] = $value['url'] ?
        //         Route::routing($value['url']) : 'javascript:;';
        // }
        Cache::set($idx, $data, 0);

        return $data;
    }
}
