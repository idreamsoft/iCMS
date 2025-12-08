<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class LinksFunc extends AppsFuncCommon
{
	public static function lists($vars)
	{
		$whereNot  = array();
		$resource  = array();
		$model     = LinksModel::field('*');
		$status    = isset($vars['status']) ? (int) $vars['status'] : 1;
		$where     = [['status', $status]];

		$vars['type'] == 'text' && $where[] = ['logo', ''];
		$vars['type'] == 'logo' && $where[] = ['logo', '<>', ''];
		isset($vars['cid'])   && $where[] = ['cid', $vars['cid']];

		self::inited($vars, $model, $where, $whereNot);
		self::where();
		$model->orderBy('sortnum ASC,id ASC');
		return self::getResource(__METHOD__, true);
	}
}
