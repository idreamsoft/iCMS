<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class AdminAdmincp extends AdmincpCommon
{
    use AdmincpCommonTrait;
    // public static $NOLOGIN = ['login', 'captcha', 'lang', 'assets'];

    public static $orderBy = [
        'id'         => 'ID',
        'regtime'    => '注册时间',
        'logintimes' => '登录次数',
        'post'       => '发表数'
    ];

    /**
     * 加载静态资源
     *
     * @auth no  无需鉴权的方法,但需要登录
     * @login no 无需登录的方法,同时也就不需要鉴权了
     * @return void
     */
    public function do_assets()
    {
        return Files::getAssets();
    }

    /**
     * 验证码
     *
     * @login no
     * @return void
     */
    public function do_captcha()
    {
        Captcha::get();
        exit;
    }

    /**
     * 退出登录
     * @auth no
     */
    public function done_logout()
    {
        Admin::logout();
        return ADMINCP_URL;
    }

    /**
     * 登陆
     *
     * @login no
     * @return void
     */
    public function done_login()
    {
        try {
            $captcha = Request::post('captcha');
            if ($captcha !== iPHP_KEY) {
                Captcha::check($captcha);
            }
            Security::csrf_check();
            $account = Request::post('iAccount');
            $password = Request::post('iPassWord');
            list($token, $expire) = Admin::login($account, $password);
            return ['token' => $token, 'expire' => $expire, 'info' => Admin::data()];
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            iJson::fail($msg);
        }
    }

    /**
     * 工作统计
     * @return void
     */
    public function do_job()
    {
        // $job = new AdminJob();
        // $this->id or $this->id = Admin::$user_id;
        // $job->count_post($this->id);
        // $month = $job->month();
        // $pmonth = $job->month($job->pmonth['start']);
        // $rs = AdminModel::get($this->id);
        include self::view('job');
    }

    /**
     * 个人信息
     * @return void
     */
    public function do_profile()
    {
        $this->id = Admin::$id;
        Menu::setData('breadcrumb', [
            'name'  => '个人信息',
            'url'   => $_SERVER['REQUEST_URI']
        ]);
        $rs = AdminModel::get($this->id);
        $this->do_add($rs);
    }

    public function done_profile()
    {
        $this->done_edit();
    }

    public function do_index()
    {
        $status = Request::get('status');
        is_numeric($status) && $where['status'] = $status;
        $role_id = Request::get('role_id');
        is_numeric($role_id) && $where['role_id'] = $role_id;

        self::setContext([
            'tablist' => 'params',
            'params'  => [
                'status' => $status,
            ]
        ]);
        if (Request::isAjax()) {
            return self::tableData([
                'where' => $where
            ], function ($rows) {
                $roleArr = Role::admin();
                $rows = array_map(function ($row) use ($roleArr) {
                    $row['isSuperAdmin'] = Admin::isSuperAdmin($row['id']);
                    $row['isSuperRole'] = Admin::isSuperRole($row['role_id']);
                    $row['role_name'] = $roleArr[$row['role_id']]['name'];
                    if ($row['isSuperAdmin']) {
                        $row['operate']['trash'] = false;
                    }
                    return $row;
                }, $rows);
                return $rows;
            });
        }

        include self::view();
    }

    public function save(&$data = [])
    {
        empty($data) && $data = AdminModel::postdata();
        $data['account'] or self::alert('账号不能为空');

        $id = $data['id'];
        $password = $data['password'] ? Admin::password($data['password']) : '';

        if (!Admin::isSuperRole()) {
            unset($data['role_id']);
        }
        unset($data['id'], $data['password']);

        $where = ['account' => $data['account']];
        $id && $where['id'] = ['<>', $id];
        AdminModel::field('id')->where($where)->value() && self::alert('该账号已存在');

        if (empty($id)) {
            $data['regtime'] = time();
            $data['lastloginip'] = Request::ip();
            $data['lastlogintime'] = time();
            $data['status'] = '1';
            $id = AdminModel::create($data, true);
        } else {
            AdminModel::update($data, $id);
        }
        $data['id'] = $id;
        $password && AdminModel::update(compact('password'), $id);
        $user_id = UserAdmincp::shadow($data);
        AdminModel::update(compact('user_id'), $id);
    }

    public function doneAccess()
    {
        $id = (int)Request::post('id');
        $key = Request::post('key');
        if (Request::isAjax() && $key) {
            empty($id) && self::alert('请求出错');
            $result = AdminModel::get($id);
            $access = [];
            if ($key) {
                $access = $result['access'][$key];
            }
            return ['access' => $access ?: []];
        }
        $data = (array) Request::post('access');
        $akey = Request::post('akey');
        if (Request::isPost() && $akey) {
            $role = AdminModel::get($id);
            $access = $role['access'];
            if ($akey == 'node') {
                $access['node'] = $data['node'] ?: [];
            } else {
                $access['app'] = $data['app'] ? array_unique($data['app']) : [];
            }

            AdminModel::update(['access' => $access], $id);
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
        // list($accessAll, $appArray) = AppsAccess::all();
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

    public function deleting($ids = [])
    {
        if ($ids) {
            foreach ($ids as $key => $id) {
                Admin::isSuperAdmin($id) && self::alert('禁止删除超级管理员');
            }
        }
    }

    public function selectpage()
    {
        return [
            'list' => function (&$list, $param) {
                if (!Admin::isSuperRole()) {
                    foreach ($list as $key => $value) {
                        if (Admin::$id != $value['id']) {
                            unset($list[$key]);
                        }
                    }
                }
            },
        ];
    }
}
