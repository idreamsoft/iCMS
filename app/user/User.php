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

define("USER_AUTHASH", '#=(iCMS@' . iPHP_KEY . '@iCMS)=#');

class User
{
	const APP = 'user';
	const APPID = iCMS_APP_USER;

	public static $statusMap = array(
		'0' => '禁用',
		'1' => '正常',
		'2' => '黑名单',
		'3' => '登录封禁',
	);
	public static $id         = 0;
	public static $openid     = null;
	public static $account    = '';
	public static $nickname   = '';
	public static $cookieTime = 0;
	public static $format     = false;
	public static $config     = array();
	public static $callback   = array(); //回调
	public static $data       = array(); //在iCMS::run 已经初始化
	const AUTH_KEY            = 'USER_AUTH';
	const AUTH_TOKEN          = 'USER_TOKEN'; //只用来判断是否登录

	public static function init()
	{
		self::$config = Config::get('user');
		Files::init(['userid' => self::$id]);
	}

	public static function getLoginUrl($base = null)
	{
		$url = Route::routing('user/login');
		$base && $url = str_replace(rtrim(iCMS_URL, '/'), $base, $url);
		return $url;
	}
	public static function route($uid, $type, $size = 0)
	{
		switch ($type) {
			case 'avatar':
				return iCMS_FS_URL . get_user_pic($uid, $size);
				break;
			case 'url':
				return Route::routing('{uid}/home', [$uid]);
				break;
			case 'coverpic':
				$dir = get_user_dir($uid, 'coverpic');
				return array(
					'pc' => FilesClient::getUrl($dir . '/' . $uid . ".jpg"),
					'mo' => FilesClient::getUrl($dir . '/m_' . $uid . ".jpg")
				);
				break;
			case 'urls':
				return array(
					'inbox'    => Route::routing('user/message/{uid}', [$uid]),
					'home'     => Route::routing('{uid}/home', [$uid]),
					'comment'  => Route::routing('{uid}/comment', [$uid]),
					'favorite' => Route::routing('{uid}/favorit', [$uid]),
					'fans'     => Route::routing('{uid}/fans', [$uid]),
					'follower' => Route::routing('{uid}/follower', [$uid]),
				);
				break;
		}
	}
	public static function uinfo($uid, $name, $url = 'javascript:;', $avatar = "about:blank")
	{
		$format = '<a href="%s" class="iCMS_user_link" target="_blank" i="event:user:ucard" data-uid="%d">%s</a>';
		$link = sprintf($format, $url, $uid, $name);
		$at = sprintf($format, $url, $uid, '@' . $name);
		$nickname = $name;
		return compact(['uid', 'nickname', 'name', 'url', 'avatar', 'link', 'at']);
	}
	public static function info($uid, $name = null, $size = 0)
	{
		if (empty($uid)) {
			$info = self::uinfo($uid, '###');
		} else {
			$url = self::route($uid, "url");
			if ($name === null) {
				$name = self::value($uid, 'nickname');
			}
			$avatar = self::route($uid, "avatar", $size ? $size : 0);
			$info = self::uinfo($uid, $name, $url, $avatar);
		}
		self::$callback['info'] && iPHP::callback(self::$callback['info'], array(&$info));
		return $info;
	}
	public static function value($val, $field = 'account', $where = 'uid')
	{
		return UserModel::field($field)->where($where, $val)->value();
	}
	public static function check($val, $field = 'account')
	{
		$uid = UserModel::field('uid')->where($field, $val)->value();
		return empty($uid) ? false : $uid;
	}

	public static function updateInc($field, $id, $step = 1)
	{
		return self::updateCount($field, $id, $step, 'inc');
	}
	public static function updateDec($field, $id, $step = 1)
	{
		return self::updateCount($field, $id, $step, 'dec');
	}
	public static function updateCount($field, $id, $step = 1, $func = 'inc')
	{
		return UserModel::where($id)->$func($field, $step);
	}

	public static function get_cache($uid)
	{
		return Cache::get(iPHP_APP . ':user:' . $uid);
	}
	public static function set_cache($uid)
	{
		$user = UserModel::get($uid);
		unset($user['password']);
		Cache::set('user/' . $user['uid'], $user, 0);
	}

	public static function data($id = 0)
	{
		return UserData::gets($id);
	}
	public static function get($ids = 0, $unpass = true, $field = 'uid')
	{
		if (empty($ids)) return array();

		is_array($ids) && $ids = array_unique($ids);

		$where[$field] = $ids;
		$where['status'] = '1';
		$model = UserModel::where($where);
		$unpass && $model->withoutField('password');
		if (is_numeric($ids)) {
			$result = $model->get();
			self::item($result);
		} else {
			$result  = $model->select();
			$result = array_column($result, null, 'uid');
			$result = array_map([__CLASS__, 'item'], $result);
		}
		return $result;
	}

	private static function item(&$user)
	{
		if ($user) {
			$user['genderText']   = $user['gender'] > 1 ?: ($user['gender'] ? 'male' : 'female');
			$user['avatar']   = self::route($user['uid'], 'avatar');
			$user['urls']     = self::route($user['uid'], 'urls');
			$user['coverpic'] = self::route($user['uid'], 'coverpic');
			$user['url']      = $user['urls']['home'];
			$user['inbox']    = $user['urls']['inbox'];
			$user['name']     = $user['nickname'];
			$user['role']     = Role::get($user['role_id']);
			// $user['data']     = UserData::gets($user['uid']);
			//用户点数+角色点数  不增不减
			$user['scores']   = $user['scores'] + $user['role']['scores'];

			$url = sprintf(
				'%s?app=user&do=hits&uid=%d',
				Route::routing('api'),
				$user['uid']
			);
			$user['hits'] = array(
				'script' => $url,
				'count'  => $user['hits'],
				'today'  => $user['hits_today'],
				'yday'   => $user['hits_yday'],
				'week'   => $user['hits_week'],
				'month'  => $user['hits_month'],
			);
		}
		return $user;
	}

	public static function deAuth($auth)
	{
		$json = authcode($auth, 'DECODE', md5(iPHP_KEY));
		return json_decode($json, true);
	}
	public static function getCookie($flag = false)
	{
		if (self::$callback['cookie:get']) {
			return self::$callback['cookie:get'];
		}
		$token = Cookie::get(self::AUTH_TOKEN);
		$auth  = Cookie::get(self::AUTH_KEY);
		$userid  = Cookie::get('userid');
		$nickname  = Cookie::get('nickname');


		if ($token == Security::secureToken($auth)) {
			$data = self::deAuth($auth);

			if (empty($data)) return false;

			$userid   = auth_decode($userid);
			$nickname = auth_decode($nickname);

			if ((int)$userid === (int)$data['uid'] && $nickname === $data['nickname']) {
				self::$id       = (int)$data['uid'];
				self::$nickname = $data['nickname'];
				$data['userid'] = self::$id;
				if (!$flag) {
					unset($data['account'], $data['password']);
				}
				return $data;
			}
			//self::logout();
		}
		return false;
	}
	public static function setCookie($user)
	{
		if (self::$callback['cookie:set']) {
			iPHP::callback(self::$callback['cookie:set'], array(&$user));
		}

		$data = array_filter_keys($user, 'uid,account,password,nickname,status');
		$auth = authcode(json_encode($data), 'ENCODE', md5(iPHP_KEY));

		Cookie::set(self::AUTH_KEY,	$auth, self::$cookieTime);
		Cookie::set(self::AUTH_TOKEN,	Security::secureToken($auth), self::$cookieTime, false);
		Cookie::set('userid', auth_encode($user['uid']), self::$cookieTime, false);
		Cookie::set('nickname', auth_encode($user['nickname']), self::$cookieTime, false);

		return $auth;
	}

	public static function status()
	{
		$status = false;
		$auth   = self::getCookie(true);
		if ($auth) {
			$user = self::get($auth['userid'], false);
			// var_dump($user,$auth);
			$status = ($auth['account'] == $user['account'] &&
				$auth['password'] == $user['password']);
			unset($user['password']);
		}
		unset($auth);
		return $status ? $user : false;
	}
	public static function logout()
	{
		Cookie::set(self::AUTH_KEY,   '', -31536000);
		Cookie::set(self::AUTH_TOKEN, '', -31536000);
		Cookie::set('userid',         '', -31536000);
		Cookie::set('nickname',       '', -31536000);
		Cookie::set('captcha',        '', -31536000);
	}
	public static function password($value)
	{
		return md5($value);
		// return Security::secureToken($value);
	}
}
