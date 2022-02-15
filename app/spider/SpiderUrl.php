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

class SpiderUrl
{
	public static $statusMap = [
		0 => '未采集', 1 => '正常采集'
	];
	public static $publishMap = [
		0 => '未发布', 1 => '已发布', 2 => '标记移除'
	];
	public static function checkout(&$urlsData, $params)
	{
		extract($params);
		foreach ($urlsData as $idx => $value) {
			$url = SpiderUrl::url($value['url']);
			try {
				Spider::checker($pid, $url, $value['title']);
			} catch (\FalseEx $fex) {
				unset($urlsData[$idx]);
			}
		}
		return $urlsData;
	}
	/**
	 * 网址批量检测
	 *
	 * @param [type] $urlsData
	 * @return void
	 */
	public static function lotUrlCheck(&$urlsData, $params)
	{
		foreach ($urlsData as $idx => $value) {
			$urlsArray[] = SpiderUrl::url($value['url']);
		}
		if ($urlsArray) {
			$result = SpiderUrlModel::field('id,url')->where(['url' => $urlsArray])->pluck('url', 'id');
			foreach ($urlsData as $idx => $value) {
				$url = SpiderUrl::url($value['url']);
				if ($result[$url]) {
					unset($urlsData[$idx]);
				}
			}
		}
	}
	public static function create($data)
	{
		$data['url'] = SpiderUrl::url($data['url']);
		$data['id'] = SpiderUrlModel::field('id')->where(['url' => $data['url']])->value();
		$data['hash']  = SpiderUrl::hash($data['url'], $data['title']);
		if (empty($data['id'])) {
			$data['addtime'] = time();
			$data['id'] = SpiderUrlModel::create($data, true);
		}
		return $data['id'];
	}
	public static function lotcreate(&$urlsData, $params)
	{
		foreach ($urlsData as $idx => $value) {
			$urlsArray[] = SpiderUrl::url($value['url']);
		}
		$urlsArray && $result = SpiderUrlModel::field('id,url')->where(['url' => $urlsArray])->pluck('url', 'id');
		foreach ($urlsData as $idx => $value) {
			$url = SpiderUrl::url($value['url']);
			$hash  = SpiderUrl::hash($url, $value['title']);
			$id = $result[$url];
			if (!$id) {
				$id = SpiderUrlModel::create(array(
					'appid'   => 0,
					'cid'     => Spider::$cid ?: $params['cid'],
					'rid'     => (int)$params['rid'] ?: 0,
					'pid'     => (int)$params['pid'] ?: 0,
					'title'   => $value['title'],
					'url'     => $url,
					'hash'    => $hash,
					'status'  => '0',
					'addtime' => time(),
					'publish' => '0',
					'indexid' => '0',
					'pubdate' => '0'
				), true);
				SpiderUrl::data($url, $value, $id);
			}
			$urlsData[$idx]['id'] = $id;
		}
	}
	public static function getId($app, $post = null)
	{
		$post === null && $post = $_POST;
		$url = SpiderUrl::url($post['reurl']);
		if ($url) {
			// $urls   = array($url, 'http://' . $url, 'https://' . $url);
			$where  = ['url' => $url];
			$row = SpiderUrlModel::field('id,publish,indexid')->where($where)->get();
		}
		$appid  = $app['id'];
		if ($row) {
			$row['indexid'] && Spider::getAppDataIds($row['indexid'], $app);
			return $row['id'];
		}
		return SpiderUrl::create(array(
			'appid'   => $appid,
			'cid'     => $post['cid'],
			'rid'     => Spider::$rid,
			'pid'     => Spider::$pid,
			'title'   => $post['title'],
			'url'     => $post['reurl'],
			'status'  => '1',
			'publish' => '0',
			'indexid' => '0',
			'pubdate' => ''
		));
	}
    public static function createList($iid, $url, $source)
    {
        return SpiderUrlListModel::create(
            compact('iid', 'url', 'source'),
            true
        );
    }
	public static function url($url)
	{
		if (strpos($url, '://') !== false) {
			$url = strstr($url, '://');
			$url = substr($url, 3);
		}
		return $url;
	}
	public static function hash($url, $title = null)
	{
		$parse = parse_url($url);
		return md5($title . $parse['path']);
	}
	// public static function value($url)
	// {
	//     $scheme = parse_url($url, PHP_URL_SCHEME);
	//     $scheme && $url  = str_replace($scheme . '://', '', $url);
	//     return $url;
	// }
	/**
	 * 列表 除title,url外其它字段保存到 spider_url_data 表
	 *
	 * @param [type] $url
	 * @param [type] $data
	 * @param [type] $urlId
	 * @return void
	 */
	public static function data($url = null, $data = null, $id = 0)
	{

		if (Spider::$callback['SpiderUrl:data'] === false) {
			return false;
		}

		$url = self::url($url);
		if ($data) {
			unset($data['title'], $data['url']);
		}
		if (Spider::$callback['SpiderUrl:data'] && is_callable(Spider::$callback['SpiderUrl:data'])) {
			return call_user_func_array(Spider::$callback['SpiderUrl:data'], array($url, $data));
		}
		if (Spider::$isTest) {
			return;
		}
		$where = ['url' => $url];
		if ($data === null) {
			return SpiderUrlDataModel::field("data")->where($where)->value();
		} else {
			if (empty($data)) {
				return;
			}
			$id = SpiderUrlDataModel::field("id")->where($where)->value();
			if (!$id) {
				SpiderUrlDataModel::create(array(
					'id'  => $id,
					'url'  => $url,
					'data' => $data
				), true);
			}
		}
	}

	public static function update_indexid($urlId, $indexid)
	{
		SpiderUrlModel::update(array(
			//'publish' => '1',
			'indexid' => $indexid,
			//'pubdate' => time()
		), $urlId);
		self::update_ids($indexid);
	}

	public static function update_publish($urlId)
	{
		SpiderUrlModel::update(array(
			'publish' => '1',
			'pubdate' => time()
		),  $urlId);
		self::update_ids();
	}

	public static function update_ids($indexid = 0)
	{
		foreach ((array)Spider::$spider_url_ids as $key => $suid) {
			if ($indexid) {
				$data = array(
					'indexid' => $indexid,
				);
			} else {
				$data = array(
					'pid'     => Spider::$pid,
					'publish' => '1',
					'status'  => '1',
					'pubdate' => time()
				);
			}
			SpiderUrlModel::update($data, $suid);
		}
	}
}
