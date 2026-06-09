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
    public const APP = 'tag';
    public const APPID = iCMS_APP_TAG;

    public static $APPID = '1';
    public static $field = 'tags';
    public static $remove = true;
    public static $add_status = '1';

    public static function check($value, $id = 0, $field = 'name')
    {
        $where = [$field => $value];
        $id && $where['id'] = ['<>', $id];
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
        if (empty($ids)) {
            return [];
        }

        $where['status'] = '1';
        $where[$field] = $ids;
        $model = TagModel::where($where);

        if (is_numeric($ids)) {
            $result = $model->find();
            self::item($result);
        } else {
            $result = $model->select();
            $result = array_column($result, null, 'id');
            foreach ($result as $key => &$value) {
                self::item($value);
            }
            // $result = array_map([__CLASS__, 'item'], $result);
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
        if (empty($name)) {
            return '';
        }
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

    public static function where($node, &$where = [])
    {
        $where = [
            'node'  => $node,
            'appid' => self::$APPID,
            'field' => self::$field,
        ];
    }

    public static function create($tags, $opt = [])
    {
        if (empty($tags)) {
            return;
        }

        $userid = isset($opt['userid']) ? $opt['userid'] : '0';
        $cid = isset($opt['cid']) ? $opt['cid'] : '0';
        $tcid = isset($opt['tcid']) ? $opt['tcid'] : '0';
        $appid = isset($opt['appid']) ? $opt['appid'] : self::$APPID;

        $tids = [];
        if (is_array($tags)) {
            $alphabet = range('A', 'Z');
            foreach ($tags as $key => $name) {
                $tkey = Tag::getTkey($name);
                $initial = strtoupper($tkey[0]);
                if (!in_array($initial, $alphabet)) {
                    $initial = 'other';
                }
                if (is_numeric($initial)) {
                    $initial = 'number';
                }
                $data = [
                    'cid'     => $cid,
                    'tcid'    => $tcid,
                    'userid'  => $userid,
                    'initial' => $initial,
                    'tkey'    => $tkey,
                    'appid'   => $appid,
                    'name'    => $name,
                    'title'   => $name,
                    'count'   => '1',
                    'pubdate' => time(),
                    'postime' => time(),
                    'status'  => self::$add_status,
                    'field'   => self::$field,
                ];
                $tids[] = TagModel::create($data, true);
            }
        }
        return $tids;
    }

    public static function add($tags, $opt = [])
    {
        isset($_POST['tag_status']) && Tag::$add_status = (int)$_POST['tag_status'];

        $tags = is_array($tags) ? $tags : explode(',', $tags);
        if (empty($tags)) {
            return;
        }

        $tags = array_map([__CLASS__, 'name'], $tags);
        $_tags = TagModel::field('id,name')->where('name', $tags)->pluck('id', 'name');
        $diff = array_diff_values($tags, $_tags);
        //已有 count+1
        $tids = array_keys($_tags);
        $rows = TagModel::where('id',$tids)->select();
        if ($rows) {
            foreach ($rows as $row) {
                $appid = $row['appid'] ? $row['appid'] . ',' . $opt['appid'] : $opt['appid'];
                if($appid){
                    $appid = explode(',', $appid);
                    $appid = array_map('trim', $appid);
                    $appid = array_unique($appid);
                    $appid = array_filter($appid);
                    $appid = implode(',', $appid);
                }
                $data = [];
                $data['appid'] = $appid;
                $data['count'] = $row['count'] ? $row['count'] + 1 : 1;
                $data['pubdate'] = time();
                TagModel::update($data, $row['id']);
            }
        }
        //新增
        $diff['+'] && $tids += self::create($diff['+'], $opt);
        return $tids;
    }

    public static function diff($nTags, $oTags, $opt = [])
    {
        $nTags = is_array($nTags) ? $nTags : explode(',', $nTags);
        $nTags = array_map([__CLASS__, 'name'], $nTags);

        $oTags = is_array($oTags) ? $oTags : explode(',', $oTags);
        $oTags = array_map([__CLASS__, 'name'], $oTags);

        $diff = array_diff_values($nTags, $oTags);
        //新增
        $diff['+'] && $tids = self::create($diff['+'], $opt);

        //减少 count-1
        if ($diff['-']) {
            $where = ['name' => $diff['-']];
            $row = TagModel::where($where)->row();
            if ($row) {
                $row->appid = $row->appid ? $row->appid . ',' . $opt['appid'] : $opt['appid'];
                $row->count = $row->count ? $row->count - 1 : 0;
                $row->pubdate = time();
                $row->save();
            }
            // TagModel::where($where)->where('count', '<', '1')->delete();
        }
        return $tids;
    }

    public static function delete($tags, $field = 'name', $iid = 0)
    {
        $value = explode(',', $tags);
        $result = TagModel::field('id,name')->where($field, $value)->select();
        $names = array_column($result, 'name', 'id');
        $ids = array_column($result, 'id');

        self::where($ids, $mWhere);
        $iid && $mWhere['iid'] = $iid;

        $iids = TagMapModel::field('iid')->where($mWhere)->pluck();
        $model = Apps::model(self::$APPID);
        $raw = sprintf('REPLACE(%s,?,?)', self::$field);
        foreach ($names as $k => $name) {
            $model->update([
                self::$field => [
                    'raw' => $raw, ["{$name},", '']
                ],
            ], $iids);
            $model->update([
                self::$field => [
                    'raw' => $raw, [",{$name}", '']
                ],
            ], $iids);
        }

        self::$remove && TagModel::delete($ids);
        TagMapModel::where($mWhere)->delete();
    }

    public static function move($cid, $tocid)
    {
        TagModel::where('cid', $cid)
            ->update([
                'cid' => $$tocid,
            ]);
    }

    // public static function change($field, $tags, $event, $model)
    public static function change($field, $tags, $event, $iid, $appid, $Builder)
    {
        $tags = is_array($tags) ? $tags : explode(',', $tags);
        $tags = array_filter($tags);
        $tags = array_unique($tags);
        if (empty($tags)) {
            return;
        }

        $response = $Builder->field('cid,userid')->get($iid);
        $cid = $response['cid'];
        $userid = $response['userid'];

        // self::add($tags, $userid, $iid, $cid);

        // TagModel::where(['name' => $tags])->inc('count');

        //         $appid  = Tag::$APPID;
        //         $iid    = $model->getResponse('id');
        //         $cid    = $model->getResponse('cid');
        //         $userid = $model->getResponse('userid');
        // var_dump($iid);
        // exit;
        self::$field = $field;
        $opt = [
            'userid' => $userid,
            'appid'  => $appid,
            'iid'    => $iid,
            'cid'    => $cid,
        ];
        if ($event == 'created') {
            Tag::add($tags, $opt);
        } elseif ($event == 'updated') {
            $where = compact('iid', 'appid', 'field');
            $tids = TagMapModel::field('node')->where($where)->pluck();
            if ($tids) {
                $_tags = TagModel::field('name')->where($tids)->pluck();
                Tag::diff($tags, $_tags, $opt);
            } else {
                Tag::add($tags, $opt);
            }
        }
        $tags = array_map([__CLASS__, 'name'], $tags);
        $nodes = TagModel::field('id')->where('name', $tags)->pluck();
        AppsMap::change($field, $appid, $nodes, $event, $iid, 'Tag', false);
    }
}
