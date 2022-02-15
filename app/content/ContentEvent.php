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

class ContentEvent
{
    public static function changed($response, $event, $model)
    {
        $apps = Admincp::$APP_DATA;
        $fieldsArray = Former::fields($apps['fields']);
        $archive = $response;
        $event == 'updated' && $model->field('id,cid,userid')->get();
        $id = $model->getResponse('id');
        $appid = Admincp::$APPID;
        if (isset($response['cid'])) {
            /** 栏目变化 */
            Node::change('cid', $response['cid'], $event, $id, $appid);
            unset($response['cid']);
        }
        if (isset($response['pid'])) {
            /** 属性变化 */
            AppsMap::change('pid', $appid, $response['pid'], $event, $id, 'Prop');
            unset($response['pid']);
        }
        $fileData = array();
        // $nodeData = array();
        // $propData = array();
        // $tagData = array();

        foreach ($response as $key => $value) {
            $fields = $fieldsArray[$key];
            if (in_array($fields['type'], array('image', 'multi_image', 'file', 'multi_file'))) {
                $fileData[$key] = is_array($value) ? $value : [$value];
            }
            if (in_array($fields['type'], array('editor'))) {
                $body = $value;
                is_array($body) && $body = implode('', $body);
                $body = stripslashes($body);
                $fileData[$key] = FilesPic::findImg($body, $match);
                unset($body);
            }
            if (in_array($fields['type'], array('node', 'multi_node'))) {
                // var_dump($key,$value);
                // $nodeData[$key] = $value;
                AppsMap::change($key, $appid, $value, $event, $id, 'Node');
            }
            if (in_array($fields['type'], array('prop', 'multi_prop', 'radio_prop', 'checkbox_prop'))) {
                // $propData[$key] = $value;
                AppsMap::change($key, $appid, $value, $event, $id, 'Prop');
            }
            if (in_array($fields['type'], array('tag'))) {
                /** 标签变化 */
                Tag::$APPID = $appid;
                // var_dump($key, $value);
                // $tagData[$key] = $value;
                Tag::change($key, $value, $event, $model);
            }
        }
        if ($fileData) foreach ($fileData as $key => $paths) {
            Files::change($key, $appid, $paths, $event, $id);
        }
        //缩略图变化
        if (isset($response['pic']) || isset($response['bpic']) || isset($response['mpic']) || isset($response['spic'])) {
            FilesPic::change($response, $appid, $event, $id);
        }
        // //内容归档
        if ($event == 'updated') {
            Archive::update($appid, $id, $response);
        } else {
            //内容归档
            Archive::save($appid, $id, $response);
        }
    }
    public static function deleted($response, $model)
    {
        // $row = $model->field('id,cid,userid,tags,pic,bpic,mpic,spic')->get();
        // $iid = $row['id'];
        // $appid = iCMS_APP_ARTICLE;
        // if ($iid) {
        //     AppsMap::delete($appid, $iid, new NodeModel, new NodeMapModel);
        //     AppsMap::delete($appid, $iid, new TagModel, new TagMapModel);
        //     AppsMap::delete($appid, $iid, new PropModel, new PropMapModel);
        //     Files::delete($appid, $iid);
        //     Comment::delete($appid, $iid);
        //     Archive::delete($appid, $iid);
        // }
    }
}
