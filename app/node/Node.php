<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class Node
{
    const APP = 'node';
    const APPID = iCMS_APP_NODE;

    public static $ACCESS = null;
    public static $APPID = null;
    public static $callback = [
        'mode'=>0,
        'param'=>[],
        'func'=>[],
        'recursive'=>null
    ];

    public static function appid($appid = null, &$where = [])
    {
        self::$APPID && $appid = self::$APPID;
        if ($appid && !is_numeric($appid)) {
            $appid = Apps::id($appid);
        }
        $appid && $where['appid'] = $appid;
        return $where;
    }

    public static function total($appid = null)
    {
        self::appid($appid, $where);
        return NodeModel::where($where)
            ->count();
    }

    public static function hasChild($rootid = '0')
    {
        $id = NodeModel::field('id')
            ->where('rootid', $rootid)
            ->value('id');
        return $id ? true : false;
    }

    public static function rootid($array, $appid = null)
    {
        if (empty($array)) {
            return [];
        }

        $where['rootid'] = $array;
        self::appid($appid, $where);
        $result = NodeModel::field('id,rootid')
            ->where($where)
            ->select();
        $data = [];
        foreach ($result as $value) {
            $data[$value['rootid']][$value['id']] = $value['id'];
        }
        return $data;
    }

    //从数据中的字段获多条值，以该值去获取多条节点数据
    public static function many($rs, $field, $appid = null)
    {
        if (empty($rs)) {
            return [];
        }

        $nids = array_column($rs, $field);
        // var_dump($nids);
        $nids = array_unique($nids);
        $nids = array_filter($nids);
        $data = [];
        !empty($nids) && $data = (array) self::get($nids, $appid);
        return $data;
    }
    public static function all($ids, $appid = null)
    {
        if (empty($ids)) {
            return [];
        }
        is_array($ids) && $ids = array_unique($ids);
        $where['id'] = $ids;
        self::appid($appid, $where);
        $model = NodeModel::where($where);
        $result = $model->select();
        $result = array_column($result, null, 'id');
        return $result;
    }
    //使用节点ID 获取节点详情 可传数组获取多条
    public static function get($ids, $appid = null)
    {
        if (empty($ids)) {
            return [];
        }
        is_array($ids) && $ids = array_unique($ids);
        $where['id'] = $ids;
        self::appid($appid, $where);
        $model = NodeModel::where($where);
        if (is_numeric($ids)) {
            $result = $model->find();
            NodeItem::data($result);
        } else {
            $result = $model->select();
            $result = array_column($result, null, 'id');
            foreach ($result as $key => &$value) {
                NodeItem::data($value);
            }
            // $result = @array_map([__CLASS__, 'item'], $result);
        }
        return $result;
    }



    public static function child($rootid = 0, $where = null, $appid = null)
    {
        $where['rootid'] = $rootid;
        self::appid($appid, $where);

        $result = NodeModel::field('id')
            ->where($where)
            ->orderBy('sortnum', 'ASC')
            ->pluck('id');
        return $result;
    }

    public static function makeWhere(&$where, $id, $field = 'cid')
    {
        if ($id) {
            $ids = $id;
            $sub = Request::get('sub');
            if (isset($sub)) {
                $ids = (array) $id;
                $_ids = NodeCache::getIds($id);
                $ids = array_merge($ids, $_ids);
            }
            $where[] = [$field, $ids];
            return $ids;
        }
    }

    /**
     * @return Array
     */
    public static $loop = 0;
    public static function callfunc($id = '0', $where = null, $level = 1)
    {
        $idArray = (array) Node::child($id, $where); //获取$id下所有子栏目ID
        $nodeArray = (array) Node::get($idArray);      //获取子栏目数据
        $rootArray = (array) Node::rootid($idArray);   //获取子栏目父栏目数据
        //self::$callback['mode'] 0字符1数组2二维数组
        $data = self::$callback['mode'] > 0 ? [] : '';

        foreach ($idArray as $root => $_id) {
            $node = (array) $nodeArray[$_id];
            $node['app'] = Apps::id($node['appid'],true);
            $child = [];
            if(isset($rootArray[$_id]) && $rootArray[$_id]){
                $child = $rootArray[$_id];
            }
            if (self::$callback['func'] && is_callable(self::$callback['func'])) {
                $param = [$node, $level, $child, $where];
                self::$callback['param'] && $param = array_merge($param, self::$callback['param']);
                // var_dump($level);
                $result = call_user_func_array(self::$callback['func'], $param);
                if (!empty($result)) {
                    if (self::$callback['mode']) {
                        //生成一维数组
                        if (self::$callback['mode'] == 1) {
                            $data = array_merge($data, $result);
                        } else {
                            $data[] = $result;
                        }
                    } else {
                        $data .= $result;
                    }
                }
            }
            //self::$callback['recursive'] = false不递归,
            //回调方法 self::$callback['func'] 里自行处理
            if (self::$callback['recursive'] !== false) {
                if ($child) {
                    $result = self::callfunc($_id, $where, $level + 1);
                    if (!empty($result)) {
                        if (self::$callback['mode']) {
                            //生成一维数组
                            if (self::$callback['mode'] == 1) {
                                $data = array_merge($data, $result);
                            } else {
                                $data[] = $result;
                            }
                        } else {
                            $data .= $result;
                        }
                    }
                }
            }
        }
        return $data;
    }

    public static function getAppMeta($id)
    {
        $node = Node::get($id);
        AppsMeta::$target = $node['config']['meta'];
        return $node['config']['meta'];
    }

    public static function deleteAppData($appid = null)
    {
        NodeModel::where('appid', $appid)->delete();
    }

    /**
     * 节点变更
     *
     * @param [type] $field
     * @param [type] $node
     * @param [type] $event
     * @param [type] $iid
     * @param [type] $appid
     * @return void
     */
    public static function change($field, $node, $event, $iid, $appid)
    {
        // $appid = iCMS_APP_ARTICLE;
        NodeModel::where(['id' => $node])->inc('count');
        if ($event == 'created') {
            // $iid = $builder->getResponse('id');
            NodeMapModel::create(compact('node', 'iid', 'field', 'appid'));
        } elseif ($event == 'updated') {
            // $row = $builder->field('id')->get();
            // $iid = $builder->getResponse('id');

            $mWhere = compact('iid', 'appid', 'field');
            $_node = NodeMapModel::field('node')->where($mWhere)->value();
            if ($_node) {
                NodeModel::where(['id' => $_node])->where('count', '>', '0')->dec('count');
                NodeMapModel::update(compact('node'), $mWhere);
            } else {
                NodeMapModel::create(compact('node', 'iid', 'field', 'appid'));
            }
        }
    }

    public static function config($domain = null)
    {
        if (empty($domain)) {
            $result = NodeModel::field('id,domain')
                ->where([
                    'domain' => ['<>', ''],
                    'status' => '1'
                ])->select();
            foreach ((array) $result as $C) {
                $domain[$C['domain']] = $C['id'];
            }
        }

        Config::save(Node::APPID, 'node', compact('domain'));
    }
}
