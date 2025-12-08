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

class ContentEvent extends DBEvent
{
    public static $APP_DATA = [];
    public static $APP_ID = [];

    public static function changed($response, $Builder, $event, $isMulti)
    {
        if ($event == 'updated') {
            $idArr = $Builder->field('id')->pluck();
        } else {
            $idArr = [$response['id']];
        }
        $apps = self::$APP_DATA;
        array_map(function ($id) use ($apps, $response, $Builder, $event, $isMulti) {
            if (!$id) {
                return;
            }
            $fieldsArray = Former::fields($apps['fields']);
            $appid = $apps['id'];
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
            $fileData = [];
            // $nodeData = array();
            // $propData = array();
            // $tagData = array();

            foreach ($response as $key => $value) {
                $fields = $fieldsArray[$key];
                if (in_array($fields['type'], ['image', 'multi_image', 'file', 'multi_file'])) {
                    $fileData[$key] = is_array($value) ? $value : [$value];
                }
                if (in_array($fields['type'], ['editor'])) {
                    $body = $value;
                    is_array($body) && $body = implode('', $body);
                    $body = stripslashes($body);
                    $fileData[$key] = FilesPic::findImg($body, $match);
                    unset($body);
                }
                if (in_array($fields['type'], ['node', 'multi_node'])) {
                    // var_dump($key,$value);
                    // $nodeData[$key] = $value;
                    AppsMap::change($key, $appid, $value, $event, $id, 'Node');
                }
                if (in_array($fields['type'], ['prop', 'multi_prop', 'radio_prop', 'checkbox_prop'])) {
                    // $propData[$key] = $value;
                    AppsMap::change($key, $appid, $value, $event, $id, 'Prop');
                }
                if (in_array($fields['type'], ['tag'])) {
                    /** 标签变化 */
                    Tag::$APPID = $appid;
                    // var_dump($key, $value);
                    // $tagData[$key] = $value;
                    Tag::change($key, $value, $event, $id, $appid, $Builder);
                }
            }
            if ($fileData) {
                foreach ($fileData as $key => $paths) {
                    Files::change($key, $appid, $paths, $event, $id);
                }
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
        }, $idArr);
    }
    
    public static function delete($response, $Builder, $event, $isMulti)
    {
        $apps = self::$APP_DATA;
        $appid = $apps['id'];
        $idArr = $Builder->field('id')->pluck();
        array_map(function ($id) use ($appid) {
            if (!$id) {
                return;
            }
            AppsMap::delete($appid, $id, 'Node');
            AppsMap::delete($appid, $id, 'Tag');
            AppsMap::delete($appid, $id, 'Prop');
            Files::delete($appid, $id);
            Comment::delete($appid, $id);
            Archive::delete($appid, $id);
        }, $idArr);
    }
}
