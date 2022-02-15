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

class ArticleDataModel extends Model
{
    protected $casts = [
        // 'body'    => 'html',
    ];
    protected $callback = array(
        // '42000' => 'aa',//无创建表权限
        'SQLSTATE:42S02' => array(__CLASS__, 'createTable'), //表不存在自动创建
    );
    public static function bodyChanged($body, $event, $model, $attr)
    {
        $event == 'updated' && $model->field('article_id')->get();
        $iid = $model->getResponse('article_id');
        $appid = iCMS_APP_ARTICLE;

        is_array($body) && $body = implode('', $body);
        $body = stripslashes($body);
        $fileData = FilesPic::findImg($body, $match);
        $fileData && Files::change('body', $appid, $fileData, $event, $iid);
    }
    public static function deleted($response, $model)
    {
        $row = $model->field('article_id')->get();
        $iid = $row['article_id'];
        $appid = iCMS_APP_ARTICLE;
        if ($iid) {
            // AppsMap::delete($appid, $iid, new NodeModel, new NodeMapModel);
        }
    }
    public static function sharding($article_id)
    {
        $model = self::getInstance();
        $model->sharding = (int)$article_id % 10;
        return $model;
    }

    public static function createTable()
    {
        try {
            $target  = self::getTableName();
            $source = self::table(__CLASS__);
            return DB::copy($source, $target);
        } catch (sException $ex) {
            $state = $ex->getState();
            if ($state === '42000') { //无创建表权限
                throw $ex;
            }
            return false;
        }
    }
    public static function getFields($update = false)
    {
        $fields  = array('subtitle', 'body');
        $update or $fields  = array_merge($fields, array('article_id'));
        return $fields;
    }
    public static function getData($article_id = 0, $field = '*')
    {
        self::sharding($article_id);
        $result = array();
        try {
            $pluck = $field == '*' ? 'body' : $field;
            $result = self::field($field)->where('article_id', $article_id)->pluck($pluck);
        } catch (\sException $ex) {
            $state = $ex->getState();
            if ($state == '42S02') { //表不存在
            } elseif ($state === '42S021') { //表不存在,但自动创建成功
            } else {
                throw $ex;
            }
        }
        return $result;
    }
    public static function getBody($article_id = 0)
    {
        self::sharding($article_id);
        $pieces = array();
        try {
            $pieces = self::field('body')->where('article_id', $article_id)->pluck('body');
        } catch (\sException $ex) {
            $state = $ex->getState();
            if ($state == '42S02') { //表不存在
            } elseif ($state === '42S021') { //表不存在,但自动创建成功
            } else {
                throw $ex;
            }
        }
        return implode(iPHP_PAGEBREAK, $pieces);
    }
    public static function create($data)
    {
        if (!array_key_exists('article_id', $data)) {
            throw new sException("need a param of article_id In Data", 1);
        }
        $article_id = $data['article_id'];
        self::sharding($article_id);
        try {
            return parent::create($data);
        } catch (\sException $ex) {
            $state = $ex->getState();
            // var_dump($state);
            if ($state == '42S02') { //表不存在
                $flag = self::createTable();
                if ($flag) return self::create($data);
                return false;
            } elseif ($state == '42S021') { //表不存在，但自动创建成功
                return self::create($data);
            } else {
                throw $ex;
            }
        }
    }
    public static function update($data, array $where)
    {
        if (array_key_exists('article_id', $where)) {
            $article_id = $where['article_id'];
        } elseif (array_key_exists('article_id', $data)) {
            $article_id = $data['article_id'];
        } else {
            throw new sException("need a param of article_id In Data OR Where", 1);
        }
        self::sharding($article_id);
        try {
            return parent::update($data, $where);
        } catch (\sException $ex) {
            $state = $ex->getState();
            if ($state == '42S02') { //表不存在
                $flag = self::createTable();
                if ($flag) return self::update($data, $where);
            } elseif ($state == '42S021') { //表不存在，但自动创建成功
                return self::update($data, $where);
            } else {
                throw $ex;
            }
        }
    }
    public static function delete($article_id, $id = 0, $field = 'article_id')
    {
        self::sharding($article_id);
        try {
            $field == 'article_id' && $id = $article_id;
            return parent::delete($id, $field);
        } catch (\sException $ex) {
            $state = $ex->getState();
            if ($state == '42S02') { //表不存在
                return false;
            } elseif ($state == '42S021') { //表不存在，但自动创建成功
                return false;
            } else {
                throw $ex;
            }
        }
    }
    public static function get_ids($article_id = 0)
    {
        $model = ArticleDataModel::sharding($article_id);
        try {
            return $model->field('id')->where('article_id', $article_id)->pluck();
        } catch (\sException $ex) {
            $state = $ex->getState();
            if ($state == '42S02') { //表不存在
                return false;
            } elseif ($state == '42S021') { //表不存在，但自动创建成功
                return false;
            } else {
                throw $ex;
            }
        }
    }
    public static function get_sql($article_id = 0)
    {
        $model = ArticleDataModel::sharding($article_id);
        try {
            return $model->field('*')->where('article_id', $article_id)->getSql();
        } catch (\sException $ex) {
            $state = $ex->getState();
            if ($state == '42S02') { //表不存在
                return false;
            } elseif ($state == '42S021') { //表不存在，但自动创建成功
                return false;
            } else {
                throw $ex;
            }
        }
    }
}
