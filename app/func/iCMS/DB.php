<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class iCMS_DB_Func extends AppsFuncCommon
{
	public static function select($vars = null)
	{
		$table = $vars['table'];
		if (empty($table)) {
			$vars['iid']   or Script::warning('iCMS&#x3a;DB 标签出错! 缺少参数"table"或"table"值为空.');
			return;
		}
		$where  = array();
		$whereNot  = array();
		$resource  = array();
		if ($vars['connection']) {
			$model = DB::connection($vars['connection'])->table($table);
		} else {
			$model = DB::table($table);
		}
		$model->field('id');

		self::init($vars, $model, $where, $whereNot);
		// self::node('cid');
		self::orderby([], 'id');
		self::where();

		$whereNot && $model->where($whereNot);
		$where && $model->where($where);
		$hash = md5($model->getSql());

		$paging = self::paging($hash, __CLASS__);
		list($total, $offset, $pageSize) = $paging;

		$cacheName = sprintf('%s/%s/%s/%d_%d', iPHP_DEVICE, 'DB', $hash, $offset, $pageSize);
		$resource  = self::getCache($cacheName);
		if (empty($resource)) {
			$idsArray = self::getIds($paging);
			if ($idsArray) {
				$model = $model->field(true,'*')->where($idsArray);
				if (isset($vars['loop'])) {
					$resource = $model->orderBy('id', $idsArray)->select();
				} else {
					$resource = $model->get();
				}
			}
			$cacheTime = isset($vars['time']) ? (int) $vars['time'] : -1;
			$vars['cache'] && Cache::set($cacheName, $resource, $cacheTime);
		}
		return $resource;
	}
}

function iCMS_DB($vars)
{
	return iCMS_DB_Func::select($vars);
}
