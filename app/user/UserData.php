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

class UserData extends Model
{
    protected $primaryKey = 'uid';
    protected $casts = [
        'meta' => 'array',
    ];
	public static function gets($ids = null)
	{
		if (empty($ids)) return array();

		is_array($ids) && $ids = array_unique($ids);
		$model = self::where('uid', $ids);
		if (is_numeric($ids)) {
			$result = $model->find();
		} else {
			$result  = $model->select();
			$result = array_column($result, null, 'uid');
		}
		return $result;
	}
}
