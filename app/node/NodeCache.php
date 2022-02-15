<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class NodeCache
{
    const KEY = 'node/%s';
    const ID  = 'node/C%d';
    const TMP = 'node/%d';
    public static $DATA = [];
    public static function make($appid = null)
    {
        Node::appid($appid, $where);
        $result = NodeModel::where($where)
            ->orderBy('sortnum', 'ASC')
            ->select();
        //生成临时缓存
        self::tmp($result);
        //正式缓存
        self::formal($result);
        //清除临时缓存
        self::tmp($result, 'delete');
        unset($all);
        self::common();
        gc_collect_cycles();
    }
    //正式缓存
    protected static function formal($result = null)
    {
        foreach ((array) $result as $C) {
            self::setId($C);
        }
    }
    //临时缓存
    protected static function tmp($result = null, $flag = false)
    {
        foreach ((array) $result as $C) {
            if ($flag === 'delete') {
                self::delData($C['id']);
            } else {
                self::setData($C);
            }
        }
        gc_collect_cycles();
    }
    protected static function common()
    {
        self::common_hidden();
        self::common_array();
        self::common_domain();
        self::common_rule();
    }
    //缓存隐藏节点
    protected static function common_hidden()
    {
        $idArray = NodeModel::field('id')
            ->where('status', 0)
            ->pluck('id');
        self::set('hidden', $idArray);
        unset($idArray);
        gc_collect_cycles();
    }
    //缓存节点目录对应CID,CID对应的rootid
    protected static function common_array()
    {
        $result = NodeModel::field('id,dir,rootid,status')
            ->orderBy('sortnum', 'ASC')
            ->select();
        $arr1 = array_column($result, 'id', 'dir');
        $arr2 = array_column($result, 'rootid', 'id');
        foreach ((array) $result as $C) {
            $arr3[$C['rootid']][$C['id']] = $C['id'];
        }
        self::set('dir2nid', $arr1);
        self::set('parent', $arr2);
        self::set('rootid', $arr3);
        unset($result, $arr1, $arr2, $arr3);
        gc_collect_cycles();
    }

    /**
     * [cache_domain 要在 cache_rootid 之后执行]
     * [缓存节点绑定域名,用于iURL回调函数]
     */
    protected static function common_domain()
    {
        $result = NodeModel::field('id,domain')
            ->where('domain', '!=', '')
            ->select();
        $rootArray = self::get('rootid');
        $domain_rootid = array();
        foreach ((array) $result as $C) {
            $root = self::child($C['id'], true, $rootArray);
            $root && $domain_rootid += array_fill_keys($root, $C['id']);
        }
        self::set('domain_rootid', $domain_rootid);
        unset($result, $domain_rootid, $root);
        gc_collect_cycles();
    }
    //缓存节点URL规则,用于rewrite
    protected static function common_rule()
    {
        $result = NodeModel::field('id,dir,rule')
            ->where('rule', '!=', '')
            ->select();
        $rules = array();
        foreach ((array) $result as $C) {
            if ($C['rule']) foreach ($C['rule'] as $key => &$value) {
                if ($key != 'index' && $key != 'list') {
                    $value = str_replace('{CDIR}', $C['dir'], $value);
                }
            }
            $C['rule'] && $rules[$C['id']] = $C['rule'];
        }
        self::set('rules', $rules);
        unset($result, $domain_rootid, $root);
        gc_collect_cycles();
    }
    //分段生成缓存
    public static function burst($appid = null, $offset = 0, $num = 10, $flag = null)
    {
        if ($flag === 'common') {
            return self::common();
        }

        Node::appid($appid, $where);

        $idArray = NodeModel::field('id')
            ->where($where)
            ->orderBy('sortnum', 'ASC')
            ->limit($offset, $num)
            ->pluck('id');

        if ($idArray) {
            $result = NodeModel::where('id', $idArray)
                ->orderBy('id', $idArray)
                ->select();
            //生成临时缓存
            $flag === 'tmp' && self::tmp($result);
            //正式缓存
            $flag === 'gold' && self::formal($result);
            //清除临时缓存
            $flag === 'delete' && self::tmp($result, 'delete');
        }
    }

    public static function all($offset, $pageSize, $appid = null)
    {
        Node::appid($appid, $where);
        $idArray = NodeModel::field('id')
            ->where($where)
            ->orderBy('sortnum', 'ASC')
            ->limit($offset, $pageSize)
            ->pluck('id');
        $result = NodeModel::where('id', $idArray)->orderBy('id', $idArray)->select();
        foreach ((array) $result as $C) {
            self::setId($C);
        }
        unset($$rs, $C, $ids_array);
    }

    public static function get($key, $k2 = null)
    {
        $k1 = sprintf(self::KEY,$key);
        if ($k2) {
            return Cache::get($k1, $k2);
        }
        return Cache::get($k1);
    }
    public static function set($key,$data,$time=0)
    {
        $key = sprintf(self::KEY, $key);
        Cache::set($key, $data, $time);
    }
    //获取处理后的节点数据缓存
    public static function getId($id = "0")
    {
        $key = sprintf(self::ID, $id);
        return Cache::get($key);
    }
    //设置处理后的节点数据缓存
    public static function setId($C = null)
    {
        empty($C['link']) && $C = NodeItem::make($C);
        $key = sprintf(self::ID, $C['id']);
        Cache::set($key, $C, 0);
    }
    public static function deleteId($id = null)
    {
        $key = sprintf(self::ID, $id);
        Cache::delete($key);
        self::delData($id);
    }
    public static function getIds($id = "0", $all = true, $hidden = null, $root_array = null)
    {
        $root_array or $root_array = self::get("rootid");
        $nids = array();
        is_array($id) or $id = explode(',', $id);
        foreach ($id as $_id) {
            $nids += (array) $root_array[$_id];
        }
        if ($all) {
            foreach ((array) $nids as $_nid) {
                $root_array[$_nid] && $nids += self::getIds($_nid, $all, $hidden, $root_array);
            }
        }
        $nids = array_unique($nids);
        $nids = array_filter($nids);
        if ($hidden) {
            is_array($hidden) or $hidden = self::get('hidden');
            $nids = array_diff($nids, $hidden);
        }
        return $nids;
    }
    //源数据缓存
    public static function setData($C = null)
    {
        is_array($C) or $C = NodeModel::get($C);
        $key = sprintf(self::TMP, $C['id']);
        Cache::set($key, $C, 0);
    }
    public static function delData($id = null)
    {
        $key = sprintf(self::TMP, $id);
        Cache::delete($key);
    }

    //获取父节点ID
    public static function parent($id = "0", $parent = null)
    {
        if ($id) {
            empty($parent) && $parent = self::get('parent');
            $rootid = $parent[$id];
            if ($rootid) {
                return self::parent($rootid, $parent);
            }
        }
        return $id;
    }
    //获取子节点ID
    public static function child($id = "0", $all = true, $root = null)
    {
        empty($root) && $root = self::get('rootid');
        $ids = $root[$id];
        if (is_array($ids)) {
            $array = $ids;
            if ($all) foreach ($ids as $key => $_nid) {
                $array += self::child($_nid, $all, $root);
            }
        }
        return (array) $array;
    }
}
