<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class iCMS_dbFunc extends AppsFuncCommon
{
    public static function select($vars = null)
    {
        $table = $vars['table'];
        if (empty($table)) {
            $vars['iid'] or self::msg(['func_param', ['iCMS:db:select', 'table']]);
            return;
        }
        $where = [];
        $whereNot = [];
        $resource = [];
        if ($vars['connection']) {
            $model = DB::connection($vars['connection'])->table($table);
        } else {
            $model = DB::table($table);
        }
        $model->field('id');

        self::inited($vars, $model, $where, $whereNot);
        // self::node('cid');
        self::orderby([], 'id');
        self::where();

        $whereNot && $model->where($whereNot);
        $where && $model->where($where);
        $hash = md5($model->getSql());

        self::paging($hash, __CLASS__);

        $resource = self::getCache(self::$cacheName);
        if (empty($resource)) {
            $idsArray = self::getIds();
            if ($idsArray) {
                $model = $model->field(true, '*')->where($idsArray);
                if (isset($vars['loop'])) {
                    $resource = $model->orderBy('id', $idsArray)->select();
                } else {
                    $resource = $model->get();
                }
            }
            $cacheTime = isset($vars['time']) ? (int) $vars['time'] : -1;
            $vars['cache'] && Cache::set(self::$cacheName, $resource, $cacheTime);
        }
        return $resource;
    }

    public static function getLastSql($vars = null)
    {
        echo DB::getLastSql();
    }
}

function iCMS_db($vars)
{
    return iCMS_dbFunc::select($vars);
}
