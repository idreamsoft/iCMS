<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class RoleAdmincp extends AdmincpCommon
{
    use AdmincpCommonTrait;

    public $type = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function forbid($id)
    {
        Role::isSuper($id) && self::alert('超级管理员组禁止修改');
    }

    public function do_add()
    {
        $this->id && $rs = Role::get($this->id);
        if (empty($rs)) {
            $rs = [
                'type'    => Request::sget('type',0),
                'status'  => '1',
                'credit'  => '0',
                'scores'  => '0',
                'free'    => '0',
                'sortnum' => '0',
            ];
        }
        include self::view();
    }

    public function do_copy()
    {
        $role = Role::get($this->id);
        unset($role['id']);
        $role['name'] .= '副本';
        $id = RoleModel::create($role);
        $url = APP_URL . '&do=edit&id=' . $id;
        Utils::jumpto($url);
    }

    public function doneAccess()
    {
        $id = (int)Request::post('id');
        $key = Request::post('key');
        if (Request::isAjax() && $key) {
            empty($id) && self::alert('请求出错');
            $result = Role::get($id);
            $access = [];
            if ($key) {
                $access = $result['access'][$key];
            }
            return ['access' => $access];
        }
        $data = (array) Request::post('access');
        $akey = Request::post('akey');
        if (Request::isPost() && $akey) {
            $this->forbid($id);
            $role = Role::get($id);
            $access = $role['access'];
            if ($akey == 'node') {
                $access['node'] = $data['node'] ?: [];
            } else {
                $access['app'] = $data['app'] ? array_unique($data['app']) : [];
            }

            RoleModel::update(['access' => $access], $id);
            return true;
        }
    }

    /**
     * 设置后台权限
     *
     * @return void
     */
    public function do_access_app()
    {
        list($accessAll, $appArray) = AppsAccess::all();
        self::setJsPath('apps/assets/js/access');
        include self::view('widget/access', 'apps');
    }

    public function done_access_app()
    {
        return $this->doneAccess();
    }

    /**
     * 设置节点权限
     *
     * @return void
     */
    public function do_access_node()
    {
        self::setJsPath('node/assets/js/access');
        include self::view('widget/access', 'node');
    }

    public function done_access_node()
    {
        return $this->doneAccess();
    }

    public function do_index()
    {
        $where = [];
        $type = Request::sget('type');
        isset($type) && $where['type'] = (int)$type;
        self::setContext([
            'ROLE_ADMIN' => Role::ADMIN,
            'tablist'    => 'params',
            'params'     => [
                'type' => $type,
            ]
        ]);
        if (Request::isAjax()) {
            return self::tableData([
                'where' => $where
            ], function ($rows) {
                // $roleArr = Role::admin();
                $rows = array_map(function ($row) {
                    $row['type_text'] = RoleModel::$typeMap[$row['type']];
                    $row['isSuperRole'] = Role::isSuper($row['id']);
                    $row['isAdminRole'] = $row['type'] == Role::ADMIN;
                    return $row;
                }, $rows);
                return $rows;
            });
        }
        include self::view();
    }

    public function save(&$data = [])
    {
        $this->forbid($data['id']);
        $data['name'] or self::alert('角色名不能为空');
    }

    public function deleting($ids = [])
    {
        if ($ids) {
            foreach ($ids as $key => $id) {
                $this->forbid($id);
                RoleModel::where('id', $id)->delete();
            }
        }
        return true;
    }

    public function selectpage()
    {
        return [
            'list' => function (&$list, $param) {
                if ('user' == Request::sget('sdo')) {
                    $head = ['id' => '0', 'name' => '游客', 'text' => '游客'];
                    array_unshift($list, $head);
                }
            },
            'result' => function ($param, $where, $whereOr, $orderby, &$response) {
                $type = Role::USER;
                if ('admin' == Request::sget('sdo')) {
                    $type = Role::ADMIN;
                }
                return Role::data($type);
            },
        ];
    }
}
