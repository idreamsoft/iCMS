<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class Content
{
    const APP = 'content';
    const APPID = iCMS_APP_CONTENT;

    public static $MODEL = null;
    public static $APP_DATA = [];
    public static $APP = null;
    public static $APPID = 0;
    public static $stypeMap = array(
        'inbox'   => '0', //草稿
        'normal'  => '1', //正常
        'trash'   => '2', //回收站
        'examine' => '3', //待审核
        'off'     => '4', //未通过
        'delete'  => '5', //删除
        'lock'    => '6', //锁定
        'hidden'  => '7', //隐藏
    );



    /**
     * 设置 ContentModel 模型表
     * 设置 ContentDataModel 模型表
     * 设置 ContentDataModel::$unionKey 关联ID
     */
    public static function model($apps, $table = null)
    {
        is_null($table) && $table = $apps['app'];
        ContentModel::setEventData($apps);
        self::$MODEL = ContentModel::setTable($table);
        if (AppsTable::getDataTable($apps, $table)) {
            ContentDataModel::setUnionKey($apps['app']);
            ContentDataModel::setTable($table);
        }
        return  self::$MODEL;
    }
    /**
     * 已在 nodeApp contentApp 设置数据回调,
     * 在应用范围内可以不用设置 app="应用名/应用ID"
     **/
    public static function setAppData($data){
        // echo iDebug::backtraceHtml();
        self::$APP_DATA = $data;
    }
    /**
     * @return Array 新增返回空数组|更新返回旧数据
     */
    public static function save(&$id, &$content, $contentData)
    {
        is_null($id) && $id = $content['id'];
        unset($content['id']);
        $unionKey = ContentDataModel::$unionKey;
        $orig = []; //旧数据

        if (empty($id)) {
            $flag = 0; //插入
            $id = ContentModel::create($content, true);
            if ($contentData && $id && $unionKey) {
                $contentData[$unionKey] = $id;
                ContentDataModel::create($contentData, true);
                // echo ContentDataModel::getQueryLog();
            }
        } else {
            $flag = 1; //更新
            $orig = ContentModel::get($id);
            ContentModel::update($content, $id);
            if ($contentData && $unionKey) {
                $where = [$unionKey => $id];
                // ContentDataModel::updateOrCreate($contentData, $where, true);
                $check = ContentDataModel::check($where);
                //检测 附加表是否有数据，有就更新
                if ($check) {
                    ContentDataModel::update($contentData, $where);
                } else {
                    $contentData[$unionKey] = $id;
                    ContentDataModel::create($contentData, true);
                }
            }
        }
        $content['id'] = $id;
        return $orig;
    }

    public static function check($value, $id = 0, $field = 'title')
    {
        $where = [$field => $value];
        $id && $where['id'] = ['<>', $id];
        return ContentModel::field('id')->where($where)->value();
    }

    public static function value($field = 'id', $id = 0)
    {
        if (empty($id)) {
            return '';
        }
        return ContentModel::field($field)->where($id)->value();
    }

    public static function get($id = 0, $field = '*', $where = [])
    {
        $where['id'] = $id;
        return ContentModel::field($field)->where($where)->get();
    }

    public static function data($id = 0, $dataId = 0, $userid = 0)
    {
        $data = [];
        if (empty($id)) {
            return $data;
        }

        $where = [];
        $id && $where['id'] = $id;
        $userid && $where['userid'] = $userid;
        $content = ContentModel::where($where)->get();
        self::cdata($content, $dataId);
        return $content;
    }

    public static function cdata(&$data, $dataId = 0)
    {
        $ukey = ContentDataModel::$unionKey;
        if ($data && $ukey) {
            $where[$ukey] = $data['id'];
            $dataId && $where['id'] = $dataId;
            $cdata = ContentDataModel::data($where);
            is_array($cdata) && $data = array_merge($data, $cdata);
        }
        return $data;
    }

    public static function create($data)
    {
        return ContentModel::create($data, true);
    }

    public static function update($data, $where)
    {
        return ContentModel::update($data, $where);
    }

    public static function delete($id)
    {
        return ContentModel::delete($id);
    }
}
