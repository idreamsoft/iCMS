<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class NodeAccess
{
    public static $callback     = null;
    public static $nodeTypeMap  = array('m' => '管理', 'a' => '添加子节点', 'e' => '编辑', 'd' => '删除');
    public static $appTypeMap   = array('cm' => '内容管理', 'ca' => '内容添加', 'ce' => '内容编辑', 'cd' => '内容删除');

    public static function check($id, $type = '', $flag = null)
    {
        if (!is_callable(self::$callback['check'])) {
            return false;
        }
        return call_user_func_array(self::$callback['check'], array($id, $type, $flag));
    }
    public static function where($cid, &$where, &$whereAccess, $field = 'cid')
    {
        $nodeAccess = self::check('IDS', 'cm'); //取得所有有权限的栏目ID
        if ($nodeAccess !== true) { //被设置了相关栏目权限- 1 没有任何权限
            if (is_array($nodeAccess) && $cid) {
                $cflag = self::check($cid, 'cm');
                if ($cflag) { //有权限
                    $nodeAccess = (array)$cid;
                    if ($_GET['sub']) {
                        $subidsArray = NodeCache::getIds($cid);
                        if ($subidsArray) foreach ($subidsArray as $_id) {
                            if (self::check($_id, 'cm')) {
                                $nodeAccess[] = $_id;
                            }
                        }
                    }
                } else {
                    $nodeAccess = '-2';
                }
            }
            is_array($nodeAccess) && $nodeAccess = array_unique($nodeAccess);
            $whereAccess[$field] = $nodeAccess;
        } else {
            if ($cid) { // -1 没有任何权限
                Node::makeWhere($where, $cid);
            }
        }
        if ($_GET['hidden']) {
            $hidden = NodeCache::get('hidden');
            $where[] = array($field, 'NOT IN', $hidden);
        }
    }
}
