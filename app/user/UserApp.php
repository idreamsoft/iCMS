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

class UserApp
{
	public $openid = null;
	public $forward = null;
	public $me     = array();

	public function __construct()
	{
		User::init();
		UserCP::forward();
		$this->subApp();
	}
	/**
	 * 子应用
	 *
	 * @return void
	 */
	public function subApp()
	{
		$do = iAPP::$DO;
		$subApp = sprintf("User%sApp", ucfirst($do));
		$path = iAPP::path('user', $subApp);
		if (@is_file($path)) {
			$this->instance = new $subApp;
		} elseif (in_array($do, UserPassportApp::$apiList)) {
			//['login', 'logout', 'register', 'findpwd']
			$this->instance = new UserPassportApp();
		}
	}
	/**
	 * iCMS::run 执行时回调
	 *
	 * @return void
	 */
	public static function callback_begin()
	{
		// User::$data = User::getCookie();
		User::$data = User::status();
		if (User::$data) {
			User::$data['data'] = UserData::gets(User::$data['uid']);
			View::assign('ME', User::$data);
		}
	}
	public function home($name = 'index')
	{
		if (empty(User::$data) && empty($_GET['id'])) {
			$url = User::getLoginUrl();
			Helper::redirect($url);
		}
		UserCP::assign();
		$node = [];
		if ($cid = (int)Request::get('cid')) {
			$node = UserNodeModel::where(compact('cid', 'appid'))->get();
			View::append('user', compact('node'), true);
		}
		View::display('iCMS://user/home.' . $name . '.htm');
	}
	public function API_iCMS()
	{
		$this->home();
	}
	public function API_home()
	{
		$this->home();
	}

	public function API_fans()
	{
		$this->home('fans');
	}
	public function API_follower()
	{
		$this->home('follower');
	}
	public function API_favorite()
	{
		$this->home('favorite');
	}

	public function ACTION_findpwd()
	{
		Captcha::check() or Script::code(0, 'iCMS:captcha:error', 'captcha', 'json');

		$uid = (int) $_POST['uid'];
		$auth = Request::post('auth');
		if ($auth && $uid) {
			//print_r($_POST);
			$authcode = rawurldecode($auth);
			$authcode = base64_decode($authcode);
			$authcode = auth_decode($authcode);

			if (empty($authcode)) {
				Script::code(0, 'user:findpwd:error', 'uname', 'json');
			}
			list($uid, $account, $password, $timeline) = explode(USER_AUTHASH, $authcode);
			$uid = (int)$uid;
			$now = time();
			if ($now - $timeline > 86400) {
				Script::code(0, 'user:findpwd:error', 'time', 'json');
			}
			$user = User::get($uid, false);
			if ($account != $user['account'] || $password != $user['password']) {
				Script::code(0, 'user:findpwd:error', 'user', 'json');
			}
			$rstpassword = md5(trim($_POST['rstpassword']));
			if ($rstpassword == $user['password']) {
				Script::code(0, 'user:findpwd:same', 'password', 'json');
			}
			UserModel::update(array('password' => $rstpassword), array('uid' => $uid));
			Script::code(1, 'user:findpwd:success', 0, 'json');
		} else {
			$uname = Request::post('uname');
			$uname or Script::code(0, 'user:findpwd:account:empty', 'uname', 'json');
			$uid = User::check($uname, 'account');
			$uid or Script::code(0, 'user:findpwd:account:noexist', 'uname', 'json');
			$user = User::get($uid, false);
			$user or Script::code(0, 'user:findpwd:account:noexist', 'uname', 'json');

			$authcode = auth_encode($uid .
				USER_AUTHASH . $user['account'] .
				USER_AUTHASH . $user['password'] .
				USER_AUTHASH . time());
			$authcode = base64_encode($authcode);
			$authcode = rawurlencode($authcode);
			$find_url = Route::routing('user/findpwd', 'auth=' . $authcode);
			$config = Config::get('mail');
			$config['title'] = Config::get('site.name');
			$config['subject'] = Lang::get('user:findpwd:subject', $config['title']);
			$config['body'] = Lang::get(
				'user:findpwd:body',
				array($user['nickname'], $config['title'], $find_url, $find_url, $config['replyto'])
			);
			$config['address'] = array(
				array($user['account'], $user['nickname']),
			);
			//var_dump(iCMS::$config);
			$result = Vendor::run('SendMail', array($config));

			if ($result === true) {
				Script::code(1, 'user:findpwd:send:success', 'mail', 'json');
			} else {
				Script::code(0, 'user:findpwd:send:failure', 'mail', 'json');
			}
		}
	}


	public function ACTION_report()
	{
		UserCP::status();

		$post = Request::post();
		$post['param'] or iJson::error('iCMS:empty:param');
		$param = is_array($post['param']) ? $post['param'] : json_decode($post['param'], true);

		$iid = (int) $param['id'];
		$uid = (int) $param['userid'];
		$appid = (int) $param['appid'];
		$app = $param['app'];
		$reason = (int) $post['reason'];
		$content = $post['content'];

		$iid or iJson::error('user:report:empty:iid');
		$uid or iJson::error('user:report:empty:uid');

		$create_time = time();
		$ip = Request::ip();
		$userid = User::$id;
		$status = 0;

		$data = compact(['appid', 'app', 'userid', 'iid', 'uid', 'reason', 'content', 'param', 'ip', 'create_time', 'status']);
		$id = UserReportModel::create($data, true);
		iJson::success('user:report:success');
	}

	public function ACTION_follow()
	{
		$this->auth or Script::code(0, 'iCMS:!login', 0, 'json');

		$uid = (int) User::$id;
		$name = User::$nickname;
		$fuid = (int) $_POST['uid'];
		$follow = (bool) $_POST['follow'];

		$uid or Script::code(0, 'iCMS:error', 0, 'json');
		$fuid or Script::code(0, 'iCMS:error', 0, 'json');

		if ($follow) {
			//1 关注
			$uid == $fuid && Script::code(0, 'user:follow:self', 0, 'json');
			$check = UserFollow::is($uid, $fuid);
			if ($check) {
				Script::code(1, 'user:follow:success', 0, 'json');
			} else {
				$fname  = User::value($fuid, 'nickname');
				$fields = array('uid', 'name', 'fuid', 'fname');
				$data   = compact($fields);
				UserFollowModel::create($data, true);
				User::updateInc('follow', $uid);
				User::updateInc('fans', $fuid);
				Script::code(1, 'user:follow:success', 0, 'json');
			}
		} else {
			UserFollowModel::delete(compact('uid', 'fuid'));
			User::updateDec('follow', $uid);
			User::updateDec('fans', $fuid);
			Script::code(1, 0, 0, 'json');
		}
	}

	public function API_hits($uid = null)
	{
		$uid === null && $uid = (int) $_GET['uid'];
		$uid && AppsApp::updateHits('user', $uid, 'uid');
	}

	public function ACTION_status($uid = 0)
	{
		if ($user = User::$data) {
			$status = array(
				'code'        => 1,
				'user'        => $user,
				'message_num' => MessageApp::_count($user['uid']),
			);
			View::assign('status', $status);
			View::display('iCMS://user/api.status.htm');
		} else {
			iJson::error('user:login:not', $this->forward);
		}
	}

	public function API_findpwd()
	{
		$auth = Request::get('auth');
		if ($auth) {
			$authcode = rawurldecode($auth);
			$authcode = base64_decode($authcode);
			$authcode = auth_decode($authcode);

			if (empty($authcode)) {
				exit;
			}
			list($uid, $account, $password, $timeline) = explode(USER_AUTHASH, $authcode);
			$now = time();
			if ($now - $timeline > 86400) {
				exit;
			}
			$user = User::get($uid, false);
			if ($account != $user['account'] || $password != $user['password']) {
				exit;
			}
			unset($user['password']);
			View::assign('auth', $auth);
			View::assign('user', (array) $user);
			View::display('iCMS://user/resetpwd.htm');
		} else {
			View::display('iCMS://user/findpwd.htm');
		}
	}

	public function API_collections()
	{
		//View::display('iCMS://user/card.htm');
	}
	public function API_ucard()
	{
		UserCP::assign();
		if ($this->auth) {
			$secondary = $this->ucard_info();
			View::assign('secondary', $secondary);
		}
		View::display('iCMS://user/user.card.htm');
	}

	public function ucard_info()
	{
		if ($this->uid == User::$id) {
			return;
		}

		$follow = UserFollow::gets(User::$id, 'all'); //你的所有关注者
		$fans = UserFollow::gets('all', $this->uid); //他的所有粉丝
		$links = array();
		foreach ((array) $fans as $uid => $name) {
			if ($follow[$uid]) {
				$url = User::route($uid, "url");
				$links[$uid] = '<a href="' . $url . '" class="user-link" title="' . $name . '">' . $name . '</a>';
			}
		}
		if (empty($links)) {
			return;
		}
		$_count = count($links);
		$text = Lang::get('user:follow:text1');
		if ($_count > 3) {
			$links = array_slice($links, 0, 3);
			$text = Lang::get(array('user:follow:text2', $_count));
		}
		return implode('、', $links) . $text;
	}
}
