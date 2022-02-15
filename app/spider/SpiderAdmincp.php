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
class SpiderAdmincp extends AdmincpBase
{

	public function __construct()
	{
		parent::__construct();
		self::init($this);
	}
	public static function init(&$a)
	{
		Spider::$cid = $a->cid = (int) $_GET['cid'];
		Spider::$rid = $a->rid = (int) $_GET['rid'];
		Spider::$pid = $a->pid = (int) $_GET['pid'];
		Spider::$urlId = $a->urlId = (int) $_GET['urlId'];
		Spider::$title = $a->title = $_GET['title'];
		Spider::$url = $a->url = $_GET['url'];
		$a->poid = (int) $_GET['poid'];
	}

	/**
	 * [手动采集页]
	 * @return [type] [description]
	 */
	public function do_manual()
	{
		$responses = SpiderList::crawl('WEB@MANUAL');
		extract($responses);
		include self::view("spider.manual");
	}
	/**
	 * [自动采集页]
	 * @return [type] [description]
	 */
	public function do_start()
	{
		$a = SpiderList::crawl('WEB@AUTO');
		$this->do_mpublish($a);
	}
	public function do_crawl($a = null)
	{
		try {
			$_POST && $a = $_POST;

			Spider::$cid = $a['cid'];
			Spider::$pid = $a['pid'];
			Spider::$rid = $a['rid'];
			Spider::$url = $a['url'];
			Spider::$title = $a['title'];

			$publish = Spider::publish('WEB@AUTO');
			$label = sprintf(
				'<span class="badge badge-success">发布成功!</span> => 内容ID[%s]',
				$publish['indexid']
			);
		} catch (\FalseEx $ex) {
			$msg = $ex->getMessage();
			$state = $ex->getState();
			if ($state == "published") {
				$label = sprintf('%s[published]', $msg);
			} else {
				$label = sprintf('发布出错[%s]', $msg);
			}
		} catch (\sException $ex) {
			throw $ex;
		}
		printf(
			'标题:%s<br />网址:%s<br />结果:%s',
			Spider::$title,
			Spider::$url,
			$label
		);
	}
	/**
	 * [批量发布]
	 * @return [type] [description]
	 */
	public function do_mpublish($listArray = array())
	{
		if ($_POST['pub']) {
			$listArray = array();
			foreach ((array) $_POST['pub'] as $i => $uri) {
				parse_str($uri, $output);
				if ($output) {
					$output['index'] = $i;
					$listArray[$i] = $output;
				}
			}
		}
		empty($listArray) && self::alert('暂无最新内容');
		Script::$break = false;
		Script::flush_start();
		Script::dialog('开始采集', '', false, 0, false);
		include self::view("spider.mpublish");
	}

	/**
	 * [发布]
	 * @return [type] [description]
	 */
	public function do_publish($work = null)
	{
		try {
			$result = Spider::publish($work);
			Admincp::$APP_METHOD = __FUNCTION__;
			return $result;
		} catch (\FalseEx $ex) {
			$msg = $ex->getMessage();
			self::alert($msg);
		}
	}

	/**
	 * [测试代理 #NO:ACCESS#]
	 * @return [type] [description]
	 */
	public function do_proxy_test()
	{
		$a = SpiderHttp::proxy_test();
		var_dump($a);
	}
}
