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

class UserProfileApp extends UserCP
{
	public function __construct()
	{
		parent::__construct();
	}
/**
 * 用户中心修改密码
 *
 * @return void
 */
	public function ACTION_modifypwd()
	{
		Captcha::check() or iJson::error('iCMS:captcha:error');
		
		$post = Request::post();
		trim($post['password']) or iJson::error('user:password:empty:oorigld');
		trim($post['passwd']) or iJson::error('user:password:empty:new');
		trim($post['passwd-confirm']) or iJson::error('user:password:empty:confirm');
		$post['passwd'] == $post['passwd-confirm'] or iJson::error('user:password:unequal');

		$userid    = User::$id;
		if (User::value($userid, 'password') != User::password($post['password'])) {
			iJson::error('user:password:error:orig');
		}

		$password = User::password($post['passwd']);

		DB::beginTransaction();
		try {
			UserModel::update(compact('password'), $userid);
			User::logout();
			DB::commit();
		} catch (\sException $ex) {
			DB::rollBack();
			$msg = $ex->getMessage();
			iJson::error($msg);
		}
		iJson::success();
	}
	/**
	 * 用户中心修改手机号
	 *
	 * @return void
	 */
	public function ACTION_modifyPhone()
	{
		$post = Request::post();
		if ($post['phone'] == $post['phone2']) {
			iJson::success();
		}
		//判断手机号 验证码 是否正确验证过
		if (
			PluginSmsCode::verify($post['phone'], $post['smscode']) &&
			PluginSmsCode::verify($post['phone2'], $post['smscode2'])
		) {
			$phone = $post['phone2'];
			DB::beginTransaction();
			try {
				UserModel::update(compact('phone'), User::$id);
				DB::commit();
			} catch (\sException $ex) {
				DB::rollBack();
				$msg = $ex->getMessage();
				iJson::error($msg);
			}
			iJson::success();
		} else {
			iJson::error('验证出错');
		}
	}
	/**
	 * 用户中心修改手机号 验证手机验证码
	 *
	 * @return void
	 */
	public function ACTION_modifyPhoneStep()
	{
		$post = Request::post();
		try {
			// PluginSmsCode::$debug = iPHP_DEBUG;
			PluginSmsCode::check($post['phone'], $post['smscode']);
			iJson::success();
		} catch (\sException $ex) {
			$msg = $ex->getMessage();
			iJson::error($msg);
		}
	}
	/**
	 * 用户中心修改邮箱 验证邮箱验证码
	 *
	 * @return void
	 */
	public function ACTION_modifyEmailStep()
	{
		$post = Request::post();
		try {
			PluginEmail::$debug = iPHP_DEBUG;
			PluginEmail::check($post['email'], $post['emailcode']);
			iJson::success();
		} catch (\sException $ex) {
			$msg = $ex->getMessage();
			iJson::error($msg);
		}
	}
	public function ACTION_save()
	{
		$post = Request::post();
		$user = array_filter_keys(
			$post,
			['email', 'nickname', 'gender', 'setting']
		);
		$user['setting'] = array_filter_keys(
			$user['setting'],
			['message']
		);
		$data = array_filter_keys(
			$post['data'],
			['slogan', 'weixin', 'weibo', 'province', 'city', 'year', 'month', 'day', 'constellation', 'profession', 'personstyle']
		);
		DB::beginTransaction();
		try {
			UserModel::update($user, User::$id);
			UserData::update($data, User::$id);
			DB::commit();
		} catch (\sException $ex) {
			DB::rollBack();
			$msg = $ex->getMessage();
			iJson::error($msg);
		}
		iJson::success();
	}
}
