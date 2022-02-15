<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class RoleAdmincp extends AdmincpBase
{
    public $id   = NULL;
    public $type  = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->id = (int) $_GET['id'];
        $this->forbid($this->id);
    }
    public function forbid($id)
    {
        Role::isSuper($id) && self::alert('超级管理员组禁止修改');
    }
    public function do_add()
    {
        $this->id && $result = Role::get($this->id);
        if (empty($result)) {
            $result = array(
                'type'    => 0,
                'status'  => '1',
                'credit'  => '0',
                'scores'  => '0',
                'free'    => '0',
                'sortnum' => '0',
            );
        }
        include self::view("role.add");
    }
    public function do_copy()
    {
        $role = Role::get($this->id);
        unset($role['id']);
        $role['name'] .= '副本';
        $id = RoleModel::create($role);
        $url = APP_URL . '&do=edit&id=' . $id;
        Helper::redirect($url);
    }
    public function do_access_app()
    {
        $this->id && $result = Role::get($this->id);

        Menu::setData('breadcrumb', array(
            'name'  => '角色权限设置',
            'url'   => $_SERVER["REQUEST_URI"]
        ));
        Menu::active('add');
        $appAccess = json_encode($result['access']['app']);
        include self::view("role.access.app");
    }
    public function do_access_node()
    {
        $this->id && $result = Role::get($this->id);

        Menu::setData('breadcrumb', array(
            'name'  => '栏目权限设置',
            'url'   => $_SERVER["REQUEST_URI"]
        ));
        Menu::active('add');
        $nodeAccess   = json_encode($result['access']['node']);
        include self::view("role.access.node");
    }
    public function do_manage()
    {
        $where = [];
        $type = Request::sget('type');
        isset($type) && $where['type'] = (int)$type;
        $result = RoleModel::where($where)->orderBy('id')->select();
        include self::view("role.manage");
    }
    public function do_delete($id = null)
    {

        $id === null && $id = $this->id;
        $id or self::alert('请选择要删除的角色');
        $this->forbid($id);
        RoleModel::delete($id);
        // $dialog && self::success('角色删除完成');
    }
    public function do_batch()
    {
        $stype = AdmincpBatch::$config['stype'];
        $actions = array(
            'update' => function ($idArray, $ids, $batch) {
                $dataArray = $_POST['data'];
                foreach ($idArray as $id) {
                    $data = $dataArray[$id];
                    Role::isSuper($id) or RoleModel::update($data, compact('id'));
                }
            },
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    Role::isSuper($id) or $this->do_delete($id);
                }
                return true;
            }
        );
        return AdmincpBatch::run($actions, "角色");
    }
    public function save()
    {
        $data = RoleModel::postData();
        $this->forbid($data['id']);
        $data['name'] or self::alert('角色名不能为空');
        if ($data['id']) {
            RoleModel::update($data, $data['id']);
        } else {
            RoleModel::create($data);
        }
        // self::success('保存成功');
    }
    public function do_save_access()
    {
        $id = intval($_POST['id']);
        $this->forbid($id);

        $data = (array) $_POST['access'];
        $akey = $_POST['akey'];

        $role = Role::get($id);
        $access = $role['access'];
        if ($akey == 'node') {
            $access['node'] = $data['node'] ?: array();
        } else {
            $menu = $data['menu'] ?: array();
            $app = $data['app'] ?: array();
            $access['app'] = array_merge($menu, $app);
            $access['app'] = array_unique($access['app']);
        }

        RoleModel::update(array('access' => $access), $id);

        // self::success('权限更新完成');
    }
}
