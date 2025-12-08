<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesFunc extends AppsFuncCommon
{
    public static function lists($vars)
    {
        $whereNot = [];
        $resource = [];
        $model = FilesModel::field('id');
        $status = isset($vars['status']) ? (int) $vars['status'] : 1;
        $where = [['status', $status]];
        isset($vars['userid']) && $where[] = ['userid', $vars['userid']];
        isset($vars['ext']) && $where[] = ['ext', $vars['ext']];
        self::inited($vars, $model, $where, $whereNot);
        self::where();
        $model->orderBy('id DESC');
        return self::getResource(__METHOD__);
    }
    public static function resource($idsArray = null)
    {
        $resource = FilesModel::field('*')
            ->where($idsArray)
            ->orderBy('id', $idsArray)
            ->select();
        $resource && $resource = array_map(function ($row) {
            $row['path'] && $row['url'] = FilesClient::getUrl($row['path']);
            return $row;
        }, $resource);
        return $resource;
    }
}
