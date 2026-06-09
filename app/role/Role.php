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
    const ADMIN     = "1";
    
    const USER_DEFAULT = "3";
    const SUPER_RID = "1";
    
    const APP = 'role';
    const APPID = iCMS_APP_ROLE;

    public static $ID = 0;
    public static $DATA = [];
    public static $ROLE = [];
    public static $guest = '游客';
    public static $default = [
        // '0' => array('id' => '0', 'type' => '0', 'name' => '路人甲'),
        // '65535' => array('id' => '65535', 'type' => '0', 'name' => '管理员克隆')
    ];

    public static function access($id)
    {
    }
    public static function adminKeys($keys)
    {
        $rows = RoleModel::where([
            'status' => 1,
            'type' => Role::ADMIN,
            'key' => $keys
        ])->select();
        
        return array_column($rows, 'key', 'id');
    }
    public static function data($type = null, $id = null)
    {
        $type !== null && $where['type'] = $type;
        $result = RoleModel::where($where)->orderBy('level,id', 'ASC')->select();
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

    public static function user()
    {
        return self::data(Role::USER);
    }

    public static function admin()
    {
        return self::data(Role::ADMIN);
    }

    public static function isSuper($id)
    {
        if ($id && (string)$id == Role::SUPER_RID) {
            return  true;
        }
        return false;
    }

    public static function checkLevel($id, $ids)
    {
        $role = self::get($id);
        if (!$role) {
            return false;
        }
        if(!is_array($ids)){
            $ids = explode(',', $ids);
        }
        
        $levels = RoleModel::where(['id' => $ids])->pluck('level');
        if (empty($levels)) {
            return false;
        }

        $minLevel = min($levels);
        $maxLevel = max($levels);
        
        if($minLevel==$maxLevel){
            return $role['level'] >= $minLevel;
        }
        // 检查当前等级是否在范围内
        return $role['level'] >= $minLevel && $role['level'] <= $maxLevel;
    }
}
