<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class Role
{
    const USER      = "0";
    const MEMBER    = "1";
    const SUPER_RID = "1";
    const APP = 'role';
    const APPID = iCMS_APP_ROLE;

    public static $typeMap = array(
        Role::USER   => '用户组',
        Role::MEMBER => '管理组',
    );
    public static $ID      = 0;
    public static $DATA    = array();
    public static $ROLE   = array();
    public static $default = array(
        // '0' => array('id' => '0', 'type' => '0', 'name' => '路人甲'),
        // '65535' => array('id' => '65535', 'type' => '0', 'name' => '管理员克隆')
    );
    public static function access($id)
    {
    }
    public static function data($type = null, $id = null)
    {
        $type !== null && $where['type'] = $type;
        $result = RoleModel::where($where)->orderBy('sortnum,id', 'ASC')->select();
        ($type === null || $type == Role::USER) && $result = array_merge($result, self::$default);
        foreach ($result as $key => $value) {
            self::$DATA[$value['id']] = $value;
            self::$ROLE[$value['type']][$value['id']] = $value;
        }
        return self::$DATA;
    }
    public static function get($id = 0)
    {
        return RoleModel::get($id);
    }

    public static function select($type = null, $sid = NULL)
    {
        $array = self::data($type);
        $option = '';
        if ($array) foreach ($array as $value) {
            $selected = ($sid == $value['id']) ? "selected" : '';
            $option .= sprintf(
                '<option value="%s" %s>%s[id:="%s"]</option>',
                $value['id'],
                $selected,
                $value['name'],
                $value['id']
            );
        }
        return $option;
    }
    public static function userSelect($id = null)
    {
        return self::select(Role::USER, $id);
    }
    public static function memberSelect($id = null)
    {
        return self::select(Role::MEMBER, $id);
    }
    public static function isSuper($id)
    {
        if ($id && (string)$id == Role::SUPER_RID) {
            return  true;
        }
        return false;
    }
}
