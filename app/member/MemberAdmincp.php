<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class MemberAdmincp extends AdmincpBase
{

    public function __construct()
    {
        parent::__construct();
        $this->id = (int) $_GET['id'];
    }
    /**
     * [工作统计]
     * @return [type] [description]
     */
    public function do_job()
    {
        $job    = new members_job();
        $this->id or $this->id = Member::$user_id;
        $job->count_post($this->id);
        $month  = $job->month();
        $pmonth = $job->month($job->pmonth['start']);
        $rs = MemberModel::get($this->id);
        include self::view("job");
    }
    public function do_add()
    {
        $this->id && $rs = MemberModel::get($this->id);
        include self::view("add");
    }
    /**
     * [个人信息]
     * @return [type] [description]
     */
    public function do_profile()
    {
        $this->id = Member::$id;
        Menu::setData('breadcrumb', array(
            'name'  => '个人信息',
            'url'   => $_SERVER["REQUEST_URI"]
        ));
        Menu::active('add');
        $this->do_add();
    }
    public function do_manage()
    {

        //isset($this->type)	&& $sql.=" AND `type`='$this->type'";
        $_GET['role_id'] && $where['role_id'] = $_GET['role_id'];
        $orderby = self::setOrderBy(array(
            'id'        => "ID",
            'regtime'    => "注册时间",
            'logintimes' => "登录次数",
            'post'       => "发表数",
        ));
        $result = MemberModel::where($where)
            ->orderBy($orderby)
            ->paging();

        include self::view("manage");
    }
    public function save()
    {
        $data = Request::post();
        $data['account'] or self::alert('账号不能为空');

        $id = $data['id'];
        $password = $data['password'] ? Member::password($data['password']) : '';

        if (!Member::isSuperRole()) {
            unset($data['role_id']);
        }
        unset($data['id'], $data['password']);

        $where = array('account' => $data['account']);
        $id && $where['id'] = array('<>', $id);
        MemberModel::field('id')->where($where)->value() && self::alert('该账号已存在');

        if (empty($id)) {
            $data['regtime']       = time();
            $data['lastloginip']   = Request::ip();
            $data['lastlogintime'] = time();
            $data['status']        = '1';
            $id = MemberModel::create($data,true);
        } else {
            MemberModel::update($data, $id);
        }
        $data['id'] = $id;
        $password && MemberModel::update(compact('password'), $id);
        
        $user_id = UserAdmincp::shadow($data);
        MemberModel::update(compact('user_id'), $id);
        // self::success('保存成功');
    }
    public function do_access_app()
    {
        $this->id && $result = MemberModel::get($this->id);

        Menu::setData('breadcrumb', array(
            'name'  => '角色权限设置',
            'url'   => $_SERVER["REQUEST_URI"]
        ));
        Menu::active('add');
        $appAccess = json_encode($result['access']['app']);
        include self::view("access.app");
    }
    public function do_access_node()
    {
        $this->id && $result = MemberModel::get($this->id);

        Menu::setData('breadcrumb', array(
            'name'  => '栏目权限设置',
            'url'   => $_SERVER["REQUEST_URI"]
        ));
        Menu::active('add');
        $nodeAccess   = json_encode($result['access']['node']);
        include self::view("access.node");
    }
    public function do_save_access()
    {
        // if (!Member::isSuperAdmin()) {
        //     return false;
        // }
        $id = (int)Request::post('id');
        Member::isSuperAdmin($id) && self::alert('超级管理员不需要设置权限');

        $data = (array) $_POST['access'];
        $akey = Request::post('akey');

        $members = MemberModel::get($id);
        $access  = $members['access'];

        if ($akey == 'node') {
            $access['node'] = $data['node'] ?: array();
        } else {
            $menu = $data['menu'] ?: array();
            $app = $data['app'] ?: array();
            $access['app'] = array_merge($menu, $app);
            $access['app'] = array_unique($access['app']);
        }
        MemberModel::update(compact('access'), $id);
        // self::success('权限更新完成');
    }
    public function do_batch()
    {
        $stype = AdmincpBatch::$config['stype'];
        $actions = array(
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    $this->do_delete($id, false);
                }
                // self::success('管理员删除完成');
            }
        );

        return AdmincpBatch::run($actions, "管理员");
    }
    public function do_delete($id = null)
    {
        is_null($id) && $id = $this->id;
        $id or self::alert('请选择要删除的管理员');
        Member::isSuperAdmin($id) && self::alert('禁止删除超级管理员');
        MemberModel::delete($id);
        // $dialog && self::success('管理员删除完成');
    }
}
