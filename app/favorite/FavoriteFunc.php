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

class FavoriteFunc extends AppsFuncCommon
{
	public static function lists($vars = null)
	{
		$whereNot  = array();
		$resource  = array();
		$model     = FavoriteModel::field('id');

		isset($vars['mode']) && $where['mode'] = $vars['mode'];
		isset($vars['userid']) && $where['uid'] = $vars['userid'];

		self::init($vars, $model, $where, $whereNot);
		self::setApp(Favorite::APPID, Favorite::APP);
		self::orderby(['hot' => 'count'], 'id');
		self::where();
		return self::getResource(__METHOD__, [__CLASS__, 'resource']);
	}
	public static function resource($vars, $idsArray = null)
	{
		$vars['ids'] && $idsArray = $vars['ids'];
		$resource = FavoriteModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
		$resource = FavoriteApp::items($vars, $resource);
		return $resource;
	}
	public static function data($vars = null)
	{
		$whereNot  = array();
		$resource  = array();
		$model     = FavoriteDataModel::field('id');

		$vars['fid'] && $where['fid'] = $vars['fid'];
		isset($vars['iid']) && $where['iid'] = $vars['iid'];
		isset($vars['appid']) && $where['appid'] = $vars['appid'];

		if (isset($vars['userid'])) {
			$uid = $vars['userid'];
			$uid == '@me' && $uid = User::$id ?: 0;
			$where['uid'] = $uid;
		}

		self::orderby([], 'id');
		self::where();

		$whereNot && $model->where($whereNot);
		$where && $model->where($where);
		$hash = md5($model->getSql());

		$paging = self::paging($hash, __CLASS__);
		list($total, $offset, $pageSize) = $paging;

		$cacheName = sprintf('%s/%s/%s/%d_%d', iPHP_DEVICE, Favorite::APP, $hash, $offset, $pageSize);
		$resource  = self::getCache($cacheName);
		if (empty($resource)) {
			$idsArray = self::getIds($paging);
			if ($idsArray) {
				$resource = FavoriteDataModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
				if (!isset($vars['loop']) && isset($vars['column'])) {
					$column  = array_column($resource, $vars['column'], 'id');
					return $column;
				}

				if ($vars['user']) {
					$uidArray = array_column($resource, 'uid');
					$uidArray && $userArray = (array) User::get($uidArray);
				}
				if ($resource) foreach ($resource as $key => $value) {
					if ($vars['user'] && $userArray) {
						$value['user']  = (array) $userArray[$value['uid']];
					}
					$value['param'] = array(
						"id"    => $value['id'],
						"fid"   => $value['fid'],
						"appid" => $value['appid'],
						"iid"   => $value['iid'],
						"uid"   => $value['uid'],
						"title" => $value['title'],
						"url"   => $value['url'],
					);
					$resource[$key] = $value;
				}
			}
			$cacheTime = isset($vars['time']) ? (int) $vars['time'] : -1;
			$vars['cache'] && Cache::set($cacheName, $resource, $cacheTime);
		}
		return $resource;
	}
}
