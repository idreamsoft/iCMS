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

class Archive
{
    const APP = 'archive';
    const APPID = iCMS_APP_ARCHIVE;

    public static function data(&$result, $appId, $id, $data)
    {
        $result['appid'] = $where['appid'] = (int)$appId;
        $result['index_id'] = $where['index_id'] = (int)$id;

        if (empty($result['node_id']) && isset($data['cid'])) {
            $result['node_id'] = (int)$data['cid'];
        }
        if (empty($result['second_node_id']) && isset($data['scid'])) {
            $result['second_node_id'] = $data['scid'];
        }
        if (empty($result['user_node_id']) && isset($data['ucid'])) {
            $result['user_node_id'] = $data['ucid'];
        }
        if (empty($result['porp_id']) && isset($data['pid'])) {
            $result['porp_id'] = $data['pid'];
        }

        $fullFields = ArchiveModel::fullFields();
        $keys = array_keys($fullFields);
        foreach ($keys as $idx => $key) {
            isset($data[$key]) && $result[$key] = $data[$key];
        }
        unset($result['id']);
        return $where;
    }
    // public static function updated($appId, $id, $data)
    // {
    //     $where = self::data($result, $appId, $id, $data);
    //     ArchiveModel::updateOrCreate($result,$where, true);
    // }
    // public static function created($appId, $id, $data)
    // {
    //     $where = self::data($result, $appId, $id, $data);
    //     ArchiveModel::create($result, $where, true);
    // }
    public static function update($appId, $id, $data){
        $where = self::data($result, $appId, $id, $data);
        foreach ($result as $key => $value) {
            if(is_array($value) && $value[0]=='-'){
                $where[$key] = ['>',0];
            }
        }
        ArchiveModel::update($result, $where);
    }
    public static function save($appId, $id, $data, $apps = null)
    {   
        $where = self::data($result, $appId, $id, $data);
        $model =  new ArchiveModel;
        $pk = $model->getPrimaryKey();
        $check = $model->field($pk)->where($where)->value();
        if ($check) {
            $flag = $model->update($result, $where);
        } else {
            if (is_array($where)) {
                $result = array_merge($result, $where);
            } elseif (is_bool($where) && $where === true) {
                $result = array_merge($result, $where);
            }
            $flag = $model->create($result, true);
        }
    }
    public static function delete($appId, $id)
    {
        $where['appid'] = (int)$appId;
        $where['index_id'] = (int)$id;
        ArchiveModel::delete($where);
    }
}
