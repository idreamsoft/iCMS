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

class ArticleEvent
{
    public static function changed($response, $event, $model)
    {
        $event == 'updated' && $model->field('id,cid,userid')->get();
        $id = $model->getResponse('id');
        $appid = iCMS_APP_ARTICLE;
        if (isset($response['cid'])) {
            /** 栏目变化 */
            Node::change('cid', $response['cid'], $event, $id, $appid);
        }
        if (isset($response['scid'])) {
            /** 副栏目变化 */
            AppsMap::change('scid', $appid, $response['scid'], $event, $id, 'Node');
        }
        if (isset($response['pid'])) {
            /** 属性变化 */
            AppsMap::change('pid', $appid, $response['pid'], $event, $id, 'Prop');
        }
        if (isset($response['tags'])) {
            /** 标签变化 */
            Tag::$APPID = $appid;
            if(!isset($response['tags']['raw'])){
                Tag::change('tags', $response['tags'], $event, $model);
            }
        }
        //缩略图变化
        if (isset($response['pic']) || isset($response['bpic']) || isset($response['mpic']) || isset($response['spic'])) {
            FilesPic::change($response, $appid, $event, $id);
        }
        if($event == 'updated'){
            Archive::update($appid, $id, $response);
        }else{
            //内容归档
            Archive::save($appid, $id, $response);
        }
    }
    public static function deleted($response, $model)
    {
        $row = $model->field('id,cid,userid,tags,pic,bpic,mpic,spic')->get();
        $iid = $row['id'];
        $appid = iCMS_APP_ARTICLE;
        if ($iid) {
            AppsMap::delete($appid, $iid, 'Node');
            AppsMap::delete($appid, $iid, 'Tag');
            AppsMap::delete($appid, $iid, 'Prop');
            Files::delete($appid, $iid);
            Comment::delete($appid, $iid);
            Archive::delete($appid, $iid);
        }
    }
}
