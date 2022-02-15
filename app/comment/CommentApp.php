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

class CommentApp
{
	public $methods = array('html','vote', 'like', 'widget', 'json', 'add', 'reply', 'form', 'list', 'redirect');
	public $config  = null;
	public function __construct()
	{
		$this->config = Config::get('comment');
		$this->id     = (int) Request::get("id");
	}
	public function API_redirect()
	{
		$appid = (int) Request::get("appid");
		$iid   = (int) Request::get("iid");
		$url = Apps::get_url($appid, $iid);
		Helper::redirect($url);
	}
	public function API_widget()
	{
		$name = Request::sget('name');
		empty($name)&& iJson::error('iCMS:empty:widget');
		$tpl = sprintf('iCMS://comment/widget.%s.htm',$name);
		View::display($tpl);
	}
	public function API_html(){
		$name = Request::spost('name');
		empty($name)&& iJson::error('iCMS:empty:param');
		$tpl = sprintf('iCMS://comment/%s.htm',$name);
		$html = View::fetch($tpl);
		iJson::success(compact('html'));
	}
	public function API_reply()
	{
		$name = Request::post('name', 'api.reply');
		$tpl = sprintf('iCMS://comment/%s.htm',$name);
		$html = View::fetch($tpl);
		iJson::success(compact('html'));
	}
	//userid
	//iid
	//appid
	public function API_list()
	{
		// $param = Request::param('param');
		// $param && View::append('_REQUEST', $param,true);
		$name = Request::spost('name', 'api.list');
		$tpl = sprintf('iCMS://comment/%s.htm',$name);
		$html = View::fetch($tpl);
		iJson::success(compact('html'));
	}
	public function API_form()
	{
		View::display('iCMS://comment/api.form.htm');
	}

	public function API_json()
	{
		$vars = array(
			'appid' => iCMS_APP_ARTICLE,
			'id' => (int) $_GET['id'],
			'iid' => (int) $_GET['iid'],
			'date_format' => 'Y-m-d H:i',
		);
		$_GET['by'] && $vars['by'] = Request::get('by');
		$_GET['date_format'] && $vars['date_format'] = Request::get('date_format');
		$vars['page'] = true;
		// $array = comment_list($vars);
		// Script::json($array);
		View::assign('vars', $vars);
		View::display('iCMS://comment/api.json.htm');
	}

	public function ACTION_add()
	{
		$data = Request::post();

		if (!$this->config['enable']) {
			iJson::error('comment:close');
		}

		if ($this->config['captcha']) {
			Captcha::check() or iJson::error('iCMS:captcha:error');
		}
		UserCP::status();

		$data = Request::post();

		$data['param'] or iJson::error('iCMS:empty:param');
		$data['content'] or iJson::error('comment:empty:content');

		$flag = iPHP::callback('Filter::run', [&$data['content']], false);
		$flag && iJson::error('comment:filter');

		$param = is_array($data['param']) ? $data['param'] : json_decode($data['param'], true);
		is_array($param) && $data = array_merge($data, $param);
		$data['iid'] or iJson::error('comment:empty:iid');
		$data['target_title']   = $param['title'];
		$data['target_userid']   = $param['userid'];
		$data['target_username'] = $param['username'];

		$data['create_time'] = $_SERVER['REQUEST_TIME'];
		$data['update_time'] = $_SERVER['REQUEST_TIME'];
		$data['ip'] = Request::ip();
		$data['userid'] = User::$id;
		$data['username'] = User::$nickname;
		$data['status'] = $this->config['examine'] ? '0' : '1';
		$data['status'] = '1';
		$data['id'] = CommentModel::create($data, true);

		Apps::updateInc('comment', $data['iid'], $data['appid']);
		User::updateInc('comment', $data['userid']);

		iJson::success(
			$data,
			$this->config['examine'] ?
				'comment:examine' :
				'comment:success'
		);
	}
	public static function value($value, $vars)
	{
		self::getUrls($value);

		$value['content'] = nl2br($value['content']);
		$value['user']    = User::info($value['userid'], $value['username'], $vars['facesize']);
		AppsCommon::init($value, $vars)->param();
		$value['param']['iid'] = (int) $value['iid'];
		$value['param']['appid'] = iCMS_APP_COMMENT;
		$value['param']['app'] = 'comment';
		// var_dump($value['param']);
		return $value;
	}
	public static function getUrls(&$value)
	{
		$value['url'] = self::route($value, 'id');
		$value['content_url'] = self::route($value, 'appid,iid,cid', 'redirect');
		return [
			'url' => $value['url'],
			'content_url' => $value['content_url']
		];
	}
	public static function route($vars, $keys, $do = null)
	{
		$vars = array_filter_keys($vars, $keys);
		$vars['app'] = 'comment';
		$do && $vars['do'] = $do;
		$url = Route::make($vars, 'route::api');
		return $url;
	}
}
