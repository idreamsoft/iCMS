<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class Tag
{
    const APP = 'tag';
    const APPID = iCMS_APP_TAG;

    public static $APPID      = '1';
    public static $field      = 'tags';
    public static $remove     = true;
    public static $add_status = '1';
    public static $statusMap = array(
        '0' => '草稿',
        '1' => '正常',
    );

    public static function check($value, $id = 0, $field = 'name')
    {
        $where = array($field => $value);
        $id && $where['id'] = array('<>', $id);
        return TagModel::field('id')->where($where)->value();
    }

    public static function value($field = 'id', $id = 0)
    {
        if (empty($id)) {
            return '';
        }
        return TagModel::field($field)->where($id)->value();
    }

    public static function get($ids = 0, $field = 'id')
    {
        if (empty($ids)) return array();

        $where['status'] = '1';
        $where[$field] = $ids;
        $model = TagModel::where($where);
        if (is_numeric($ids)) {
            $result = $model->find();
            self::item($result);
        } else {
            $result  = $model->select();
            $result = array_column($result, null, 'id');
            $result = array_map([__CLASS__, 'item'], $result);
        }
        return $result;
    }
    public static function item(&$tag)
    {
        return $tag;
    }

    public static function cache($value = 0, $field = 'id')
    {
    }

    public static function getTkey($name)
    {
        return strtolower(Pinyin::get(trim($name), Config::get('tag.tkey')));
    }
    public static function name($name)
    {
        if (is_array($name)) {
            $name = array_filter($name);
            $name = array_unique($name);
            $name = implode(',', $name);
        }
        $name = trim($name);
        $name = htmlspecialchars_decode($name);
        $name = html2text($name);
        $name = str_replace('，', ',', $name);
        $name = Security::escapeStr($name);
        return $name;
    }
    // public static function field($field)
    // {
    //     $self = new self();
    //     $self::$field = $field;
    //     return $self;
    // }

    public static function where($node, &$where = array())
    {
        $where = array(
            'node' => $node,
            'appid' => self::$APPID,
            'field' => self::$field,
        );
    }
    public static function create($tags, $userid = "0", $cid = '0', $tcid = '0')
    {
        if (empty($tags)) return;

        $tids = array();
        if (is_array($tags)) foreach ($tags as $key => $name) {
            $tkey = Tag::getTkey($name);
            $data = array(
                'cid'     => $cid,
                'tcid'    => $tcid,
                'userid'  => $userid,
                'tkey'    => $tkey,
                'name'    => $name,
                'title'   => $name,
                'count'   => '1',
                'pubdate' => time(),
                'postime' => time(),
                'status'  => self::$add_status,
                'field'   => self::$field,
            );
            $tids[] = TagModel::create($data, true);
        }
        return $tids;
    }

    public static function add($tags, $userid = "0", $iid = "0", $cid = '0', $tcid = '0')
    {
        isset($_POST['tag_status']) && Tag::$add_status = (int)$_POST['tag_status'];

        $tags = is_array($tags) ? $tags : explode(',', $tags);
        if (empty($tags)) return;

        $tags = array_map(array(__CLASS__, 'name'), $tags);
        $_tags = TagModel::field('id,name')->where('name', $tags)->pluck('id', 'name');
        $diff = array_diff_values($tags, $_tags);
        //已有 count+1
        $tids = array_keys($_tags);
        $tids && TagModel::update(array(
            'count' => array('+', 1),
            'pubdate' => time()
        ), $tids);
        //新增
        $diff['+'] && $tids += self::create($diff['+'], $userid, $cid, $tcid);
        return $tids;
    }
    public static function diff($nTags, $oTags, $userid = "0", $iid = "0", $cid = '0', $tcid = '0')
    {
        $nTags = is_array($nTags) ? $nTags : explode(',', $nTags);
        $nTags = array_map(array(__CLASS__, 'name'), $nTags);

        $oTags = is_array($oTags) ? $oTags : explode(',', $oTags);
        $oTags = array_map(array(__CLASS__, 'name'), $oTags);

        $diff = array_diff_values($nTags, $oTags);
        //新增
        $diff['+'] && $tids = self::create($diff['+'], $userid, $cid, $tcid);
        //减少 count-1
        if ($diff['-']) {
            $where = array('name' => $diff['-']);
            TagModel::where($where)->where('count', '>', '0')
                ->update(array(
                    'count' => array('-', 1),
                    'pubdate' => time()
                ));
            // TagModel::where($where)->where('count', '<', '1')->delete();
        }
        return $tids;
    }
    public static function delete($tags, $field = 'name', $iid = 0)
    {
        $value   = explode(",", $tags);
        $result = TagModel::field('id,name')->where($field, $value)->select();
        $names = array_column($result, 'name', 'id');
        $ids = array_column($result, 'id');

        self::where($ids, $mWhere);
        $iid && $mWhere['iid'] = $iid;

        $iids = TagMapModel::field('iid')->where($mWhere)->pluck();
        $model = Apps::model(self::$APPID);
        $raw = sprintf('REPLACE(%s,?,?)', self::$field);
        foreach ($names as $k => $name) {
            $model->update(array(
                self::$field => array(
                    'raw' => $raw, ["{$name},", '']
                ),
            ), $iids);
            $model->update(array(
                self::$field => array(
                    'raw' => $raw, [",{$name}", '']
                ),
            ), $iids);
        }

        self::$remove && TagModel::delete($ids);
        TagMapModel::where($mWhere)->delete();
    }

    public static function move($cid, $tocid)
    {
        TagModel::where('cid', $cid)
            ->update(array(
                'cid' => $$tocid,
            ));
    }

    public static function change($field, $tags, $event, $model)
    {
        $tags   = is_array($tags) ? $tags : explode(',', $tags);
        $tags   = array_filter($tags);
        $tags   = array_unique($tags);

        if (empty($tags)) return;

        $appid  = Tag::$APPID;
        $iid    = $model->getResponse('id');
        $cid    = $model->getResponse('cid');
        $userid = $model->getResponse('userid');


        if ($event == 'created') {
            Tag::add($tags, $userid, $iid, $cid);
        } elseif ($event == 'updated') {
            $where = compact('iid', 'appid', 'field');
            $tids = TagMapModel::field('node')->where($where)->pluck();
            $_tags = TagModel::field('name')->where($tids)->pluck();
            Tag::diff($tags, $_tags, $userid, $iid, $cid);
        }
        //         var_dump($tags, $_tags, $userid, $iid, $cid);
        // throw new Exception("Error Processing Request", 1);
        $tags = array_map(array(__CLASS__, 'name'), $tags);
        $nodes = TagModel::field('id')->where('name', $tags)->pluck();
        AppsMap::change($field, $appid, $nodes, $event, $iid, 'Tag', false);
    }
}
