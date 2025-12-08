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

class ArticleEvent extends DBEvent
{
    // public static function geted($response, $Builder, $event, $isMulti){
    //     // throw new Exception("Error Processing Request", 1);

    //     var_dump($response);
    // }
    // public static function updated($response, $Builder, $event, $isMulti)
    // {
    // }
    public static $appid = iCMS_APP_ARTICLE;

    public static function changed($response, $Builder, $event, $isMulti)
    {
        if ($event == 'updated') {
            $idArr = $Builder->field('id')->pluck();
        } else {
            $idArr = [$response['id']];
        }
        array_map(function ($id) use ($response, $Builder, $event, $isMulti) {
            if (!$id) {
                return;
            }
            if (isset($response['cid'])) {
                /** 栏目变化 */
                Node::change('cid', $response['cid'], $event, $id, self::$appid);
            }
            if (isset($response['scid'])) {
                /** 副栏目变化 */
                AppsMap::change('scid', self::$appid, $response['scid'], $event, $id, 'Node');
            }
            if (isset($response['pid'])) {
                /** 属性变化 */
                AppsMap::change('pid', self::$appid, $response['pid'], $event, $id, 'Prop');
            }
            if (isset($response['tags'])) {
                /** 标签变化 */
                Tag::$APPID = self::$appid;
                if (!isset($response['tags']['raw'])) {
                    Tag::change('tags', $response['tags'], $event, $id, self::$appid, $Builder);
                }
            }
            //缩略图变化
            if (isset($response['pic']) || isset($response['bpic']) || isset($response['mpic']) || isset($response['spic'])) {
                FilesPic::change($response, self::$appid, $event, $id);
            }
            if ($event == 'updated') {
                Archive::update(self::$appid, $id, $response);
            } else {
                //内容归档
                Archive::save(self::$appid, $id, $response);
            }
        }, $idArr);
    }

    /**
     * 删除前
     *
     * @param [type] $response
     * @param [type] $Builder
     * @param [type] $event
     * @param [type] $isMulti
     * @return void
     */
    public static function delete($response, $Builder, $event, $isMulti)
    {
        $idArr = $Builder->field('id')->pluck();
        array_map(function ($id) {
            if (!$id) {
                return;
            }
            AppsMap::delete(self::$appid, $id, 'Node');
            AppsMap::delete(self::$appid, $id, 'Tag');
            AppsMap::delete(self::$appid, $id, 'Prop');
            Files::delete(self::$appid, $id);
            Comment::delete(self::$appid, $id);
            Archive::delete(self::$appid, $id);
        }, $idArr);
    }
}
