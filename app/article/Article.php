<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class Article
{
    const APP = 'article';
    const APPID = iCMS_APP_ARTICLE;

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

    public static function check($value, $id = 0, $field = 'title')
    {
        $where = array($field => $value);
        $id && $where['id'] = array('<>', $id);
        return ArticleModel::field('id')->where($where)->value();
    }

    public static function value($field = 'id', $id = 0)
    {
        if (empty($id)) {
            return '';
        }
        return ArticleModel::field($field)->where($id)->value();
    }
    public static function get($id = 0, $field = '*', $where = array())
    {
        $where['id'] = $id;
        return ArticleModel::field($field)->where($where)->get();
    }
    public static function data($id = 0, $dataId = 0, $userid = 0)
    {
        $userid && $where['userid'] = $userid;
        $article = ArticleModel::where($where)->get($id);
        $data = array();
        if ($article) {
            $DataModel = ArticleDataModel::sharding($article['id']);
            try {
                $DataModel = $DataModel->where('article_id', $article['id']);
                $dataId && $DataModel = $DataModel->where('id', $dataId);
                $data = $article['chapter'] ?
                    $DataModel->select() :
                    $DataModel->get();
            } catch (\sException $ex) {
                $state = $ex->getState();
                if ($state == '42S02') { //表不存在
                    // ArticleDataModel::createTable();
                } elseif ($state === '42S021') { //表不存在,但自动创建成功
                } else {
                    // throw $ex;
                }
            }
        }
        if ($data) {
            if ($article['chapter']) {
                $chapterIds = array_column($data, 'id');
                $chapterTitles = array_column($data, 'subtitle');
                $bodyArray = array_column($data, 'body');
            } else {
                $chapterIds = [$data['id']];
                $chapterTitles = [$data['subtitle']];
                $bodyArray = explode(iPHP_PAGEBREAK, $data['body']);
            }
            $bodyCount = count($bodyArray);    
        }
        return array($article, $data,compact('chapterIds','chapterTitles','bodyArray','bodyCount'));
    }

    public static function create($data)
    {
        return ArticleModel::create($data, true);
    }
    public static function update($data, $where)
    {
        return ArticleModel::update($data, $where);
    }
    public static function delete($id)
    {
        return ArticleModel::delete($id);
    }
}
