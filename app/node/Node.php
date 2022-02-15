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

    public static $APPID     = null;
    public static $ACCESS    = null;
    public static $callback  = null;
    public static $statusMap = array(
        '0' => '隐藏',
        '1' => '显示',
        '2' => '不调用',
    );

    public static function appid($appid = null, &$where = array())
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
    public static function hasChild($rootid = "0")
    {
        $id = NodeModel::field('id')
            ->where('rootid', $rootid)
            ->value('id');
        return $id ? true : false;
    }
    public static function rootid($array, $appid = null)
    {
        if (empty($array)) return array();

        $where['rootid'] = $array;
        self::appid($appid, $where);
        $result = NodeModel::field('id,rootid')
            ->where($where)
            ->select();

        foreach ($result as $value) {
            $data[$value['rootid']][$value['id']] = $value['id'];
        }

        return $data;
    }
    //从数据中的字段获多条值，以该值去获取多条节点数据
    public static function many($rs, $field, $appid = null)
    {
        if (empty($rs)) return array();

        $nids = array_column($rs, $field);
        // var_dump($nids);
        $nids = array_unique($nids);
        $nids = array_filter($nids);
        $data = array();
        !empty($nids) && $data = (array) self::get($nids, $appid);
        return $data;
    }
    //使用节点ID 获取节点详情 可传数组获取多条
    public static function get($ids, $appid = null)
    {
        if (empty($ids)) return array();
        is_array($ids) && $ids = array_unique($ids);
        $where['id'] = $ids;
        self::appid($appid, $where);
        $model = NodeModel::where($where);
        if (is_numeric($ids)) {
            $result = $model->find();
            self::item($result);
        } else {
            $result  = $model->select();
            $result = array_column($result, null, 'id');
            foreach ($result as $key => &$value) {
                self::item($value);
            }
            // $result = @array_map([__CLASS__, 'item'], $result);
        }
        return $result;
    }
    public static function item(&$node)
    {
        $node['iurl']     = Route::get('node', (array) $node);
        $node['href']     = $node['iurl']->href;
        $node['CP_ADD']   = NodeAccess::check($node['id'], 'a');
        $node['CP_EDIT']  = NodeAccess::check($node['id'], 'e');
        $node['CP_DEL']   = NodeAccess::check($node['id'], 'd');
        return $node;
    }
    public static function child($rootid = 0, $where = null, $appid = null)
    {
        $where['rootid'] = $rootid;
        self::appid($appid, $where);

        $result = NodeModel::field('id')
            ->where($where)
            ->orderBy('sortnum', 'ASC')
            ->pluck('id');
        // foreach ((array) $result as $key => &$id) {
        //     if (self::$ACCESS && !NodeAccess::check($id, self::$ACCESS)) {
        //         unset($result[$key]);
        //     }
        // }
        return $result;
    }

    public static function makeWhere(&$where, $id, $field = 'cid')
    {
        if ($id) {
            $ids = $id;
            $sub = Request::get('sub');
            if (isset($sub)) {
                $ids  = (array) $id;
                $_ids = NodeCache::getIds($id);
                $ids  = array_merge($ids, $_ids);
            }
            $where[] = array($field, $ids);
            return $ids;
        }
    }
    /**
     * @return Array
     */
    public static function callfunc($id = "0", $where = null, $level = 1)
    {
        $idArray   = (array) Node::child($id, $where); //获取$nid下所有子栏目ID
        $nodeArray = (array) Node::get($idArray);      //获取子栏目数据
        $rootArray = (array) Node::rootid($idArray);   //获取子栏目父栏目数据
        //self::$callback['result'] 设置成空字符或者空数组
        $data      = self::$callback['result'];
        foreach ($idArray as $root => $_id) {
            $node  = (array) $nodeArray[$_id];
            $child = $rootArray[$_id];
            if (self::$callback['func'] && is_callable(self::$callback['func'])) {
                $param = array($node, $level, $child);
                self::$callback['param'] && $param = array_merge($param, self::$callback['param']);
                $result = call_user_func_array(self::$callback['func'], $param);
                if (!empty($result)) {
                    if (is_array(self::$callback['result'])) {
                        $data[] = $result;
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
                        if (is_array(self::$callback['result'])) {
                            $data[] = $result;
                        } else {
                            $data .= $result;
                        }
                    }
                }
            }
        }
        return $data;
    }
    public static function select_option($node, $level, $child, $selId, $url)
    {
        $id = $node['id'];
        if (NodeAccess::check($id, self::$ACCESS)) {
            if ($node['status']) {
                $tag      = ($level == '1' ? "" : "├ ");
                $selected = ($selId == $id) ? "selected" : "";
                $text     = str_repeat("│　", $level - 1) . $tag . $node['name'] . "[id:{$id}]" . ($node['url'] ? "[∞]" : "");
                ($node['url'] && !$url) && $selected = 'disabled';
                $option = sprintf(
                    '<option name="%s" value="%s" %s>%s</option>',
                    $node['name'],
                    $id,
                    $selected,
                    $text
                );
            }
        }
        return $option;
    }
    public static function select($param = null)
    {
        $default = array(
            'selId' => "0",
            'id' => "0",
            'url' => false,
            'where' => null
        );
        if (is_numeric($param)) {
            $default['selId'] = $param;
        } else if (is_array($param)) {
            $default = array_merge($default, $param);
        }
        extract($default);

        self::$callback['func']   = array(__CLASS__, 'select_option');
        self::$callback['param']  = array($selId, $url);
        self::$callback['result'] = '';
        $result = self::callfunc($id, $where);
        return $result;
    }
    public static function set($key = null, $value = null)
    {
        $node = new self();
        $node::${$key} = $value;
        return $node;
    }
    public static function setAccess($value)
    {
        $node = new self();
        $node::$ACCESS = $value;
        return $node;
    }

    public static function instance()
    {
        $node = new self();
        $args = func_get_args();
        //第一个值传appid
        if (is_numeric($args[0])) {
            $node::$APPID = $args[0];
            $args[1] && $node::$ACCESS = $args[1];
        } elseif (is_string($args[0])) {
            //第一个值传 权限类型
            $node::$ACCESS = $args[0];
            $args[1] && $node::$APPID = $args[1];
        }
        return $node;
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

    public static function change($field, $node, $event, $iid, $appid)
    {
        // $appid = iCMS_APP_ARTICLE;
        NodeModel::where(array('id' => $node))->inc('count');
        if ($event == 'created') {
            // $iid = $builder->getResponse('id');
            NodeMapModel::create(compact('node', 'iid', 'field', 'appid'));
        } elseif ($event == 'updated') {
            // $row = $builder->field('id')->get();
            // $iid = $builder->getResponse('id');

            $mWhere = compact('iid', 'appid', 'field');
            $_node = NodeMapModel::field('node')->where($mWhere)->value();
            if ($_node) {
                NodeModel::where(array('id' => $_node))->where('count', '>', '0')->dec('count');
                NodeMapModel::update(compact('node'), $mWhere);
            } else {
                NodeMapModel::create(compact('node', 'iid', 'field', 'appid'));
            }
        }
    }
}
