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
    public static $statusMap = array(
        '0' => '草稿',
        '1' => '正常',
        '2' => '回收站',
        '3' => '待审核',
        '4' => '未通过'
    );
    public static $stypeMap = array(
        'inbox'   => '0', //草稿
        'normal'  => '1', //正常
        'trash'   => '2', //回收站
        'examine' => '3', //待审核
        'off'     => '4', //未通过
    );
    public static $postypeMap = array(
        '0' => '用户',
        '1' => '管理',
    );

    public static $primaryKey   = 'id';
    /**
     * 设置 ContentModel 模型表
     * 设置 ContentDataModel 模型表
     * 设置 ContentDataModel::$unionKey 关联ID
     */
    public static function model($apps, $table = null)
    {
        self::$primaryKey = AppsTable::getPrimaryKey();
        is_null($table) && $table = $apps['app'];
        $model = ContentModel::setTable($table);
        // self::$primaryKey = $model->getPrimaryKey();
        if (AppsTable::getDataTable($apps, $table)) {
            ContentDataModel::setUnionKey($apps['app']);
            ContentDataModel::setTable($table);
        }
        return $model;
    }
    /**
     * @return Array 新增返回空数组|更新返回旧数据
     */
    public static function save(&$id, &$content, $contentData)
    {
        $pk = AppsTable::getPrimaryKey();
        is_null($id) && $id = $content[$pk];
        unset($content[$pk]);
        $unionKey = ContentDataModel::$unionKey;
        $orig = array(); //旧数据
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
                if($check){
                    ContentDataModel::update($contentData, $where);
                }else{
                    $contentData[$unionKey] = $id;
                    ContentDataModel::create($contentData, true);
                }
            }
            // echo ContentDataModel::getQueryLog();
        }
        $content[$pk] = $id;
        return $orig;
    }
    public static function check($value, $id = 0, $field = 'title')
    {
        $where = array($field => $value);
        $id && $where['id'] = array('<>', $id);
        return ContentModel::field('id')->where($where)->value();
    }

    public static function value($field = 'id', $id = 0)
    {
        if (empty($id)) {
            return '';
        }
        return ContentModel::field($field)->where($id)->value();
    }
    public static function get($id = 0, $field = '*', $where = array())
    {
        $where['id'] = $id;
        return ContentModel::field($field)->where($where)->get();
    }
    public static function data($id = 0, $dataId = 0, $userid = 0)
    {
        $data = array();
        if (empty($id)) return $data;

        $userid && $where['userid'] = $userid;
        $content = ContentModel::where($where)->get($id);
        if ($content) {
            $where[ContentDataModel::$unionKey] = $content['id'];
            $dataId && $where['id'] = $dataId;
            $d = ContentDataModel::getData($where);
            is_array($d) && $content = array_merge($content, $d);
        }
        return $content;
        // return array_merge($content, $data);
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
