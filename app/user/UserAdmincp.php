<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class UserAdmincp extends AdmincpCommon
{
    use AdmincpCommonTrait;

    public static $_status = 1;
    public static $orderBy = [
        'id'         => 'ID',
        'hits'       => '点击',
        'hits_week'  => '周点击',
        'hits_month' => '月点击',
        'fans'       => '粉丝数',
        'follow'     => '关注数',
        'article'    => '文章数',
        'favorite'   => '收藏数',
        'comment'    => '评论数',
    ];

    /**
     * 上传头像
     *
     * @return void
     */
    public static function done_avatar($id = null)
    {
        empty($id) && $id = (int)Request::get('id');
        return UserUtils::avatar($id);
    }

    public function done_typeMap()
    {
        $arr = [
            UserOpenid::TYPE_WEB   => 'WEB',
            UserOpenid::TYPE_WXA   => '小程序',
            UserOpenid::TYPE_QRCODE=> '扫码'
        ];
        if (is_array(self::$CONFIG['social'])) {
            foreach (self::$CONFIG['social'] as $type) {
                !isset($arr[$type['id']]) && $arr[$type['id']] = $type['name'];
            }
        }

        $arr += (array)Prop::get('type');
        $list = [];
        if ($arr) {
            foreach ($arr as $id => $name) {
                $list[] = iJson::listItem(['type' => $id], $name);
            }
        }

        return iJson::listData($list);
    }

    public function do_add()
    {
        if ($this->id) {
            $rs = UserModel::get($this->id);
            $rs && $userdata = UserData::get($this->id);
        }
        if ($rs) {
            $rs['avatar'] = iCMS_FILE_URL . User::getPic($this->id);
        } else {
            $rs = [
                'role_id'=> 3,
                'status' => 1,
            ];
        }
        include self::view();
    }

    /**
     * 登录用户
     * @return void
     */
    public function do_login()
    {
        $userid = (int)Request::sget('userid');
        if ($userid && Admin::isSuperRole()) {
            $user = UserModel::get($userid);
            User::setCookie($user);
            $url = Route::routing('{userid}/home', [$userid]);
            Utils::jumpto($url);
        }
    }

    public function do_index()
    {
        self::setContext([
            'tablist' => 'params',
            'params'  => [
                'wxappid' => Request::get('wxappid'),
                'status'  => Request::get('status'),
            ]
        ]);

        if (Request::isAjax()) {
            return $this->index();
        }

        include self::view();
    }

    public function do_order()
    {
        self::$MODEL = 'UserOrderModel';
        self::setContext([
            'tablist' => 'params',
            'params'  => [
                'status'  => Request::get('status'),
            ]
        ]);

        if (Request::isAjax()) {
            return $this->order();
        }

        include self::view();
    }

    public function order()
    {
        $where = [];

        $status = Request::get('status');
        is_numeric($status) && $where['status'] = (int) $status;

        return self::tableData('UserOrderModel', [
            'where' => $where
        ], function ($rows) {
            $rows = array_map(function ($row){
                // $row['role_name'] = $roleArr[$row['role_id']]['name'];
                return $row;
            }, $rows);
            return $rows;
        });
    }

    public function index()
    {
        $where = [];

        $status = Request::get('status');
        is_numeric($status) && $where['status'] = (int) $status;

        $wxappid = Request::get('wxappid');
        $wxappid && $where['email'] = ['like', "%@{$wxappid}.wxa"];

        $keywords = Request::get('keywords');
        $keywords && $where['CONCAT(account,nickname)'] = ['like', "%{$keywords}%"];

        $role_id = Request::get('role_id');
        $role_id && $where['role_id'] = $role_id;

        $regip = Request::get('regip');
        $regip && $where['regip'] = $regip;

        $loginip = Request::get('loginip');
        $loginip && $where['loginip'] = $loginip;

        return self::tableData([
            'where' => $where
        ], function ($rows) {
            $roleArr = Role::user();
            $rows = array_map(function ($row) use ($roleArr) {
                $row['role_name'] = $roleArr[$row['role_id']]['name'];
                return $row;
            }, $rows);
            return $rows;
        });
    }

    public function save(&$data = [])
    {
        $user = UserModel::postData();
        $userData = UserData::postData('userdata');
        $postud = Request::post('userdata');
        $roleId = Request::post('role_id');

        $id = (int)$user['id'];
        $account = $user['account'];
        $phone = $user['phone'];
        $email = $user['email'];
        $nickname = $user['nickname'];
        $password = $user['password'];
        unset($user['password']);

        $nickname or self::alert('昵称不能为空');
        $account or self::alert('账号不能为空');

        preg_match("/^[\w_\-@\.]+$/i", $account) or self::alert('账号格式错误,只能由英文字母、数字或_-组成,不支持中文');

        if ($phone) {
            Utils::isPhone($phone) or self::alert('手机号格式错误');
        }
        if ($email) {
            preg_match("/^[\w\-\.]+@[\w\-]+(\.\w+)+$/i", $email) or self::alert('邮箱格式错误');
        }

        $user['regdate'] = str2time($user['regdate']);
        $user['lastlogintime'] = str2time($user['lastlogintime']);

        UserModel::check(compact('account'), $id) && self::alert('该账号已经存在');
        UserModel::check(compact('nickname'), $id) && self::alert('该昵称已经存在');

        if ($userData['city']) {
            $citys = explode('/', $userData['city']);
            $userData['province'] = $citys[0];
        }
        if ($postud['birthday']) {
            list($userData['year'], $userData['month'], $userData['day']) = explode('-', $postud['birthday']);
        }
        if($roleId!=$user['role_id']){
            $user['role_id'] = $roleId;
        }
        if (empty($id)) {
            $password or self::alert('密码不能为空');
            $user['password'] = User::password($password);
            $user['id'] = UserModel::create($user, true);
            if ($user['id']) {
                $userData['userid'] = $user['id'];
                UserData::create($userData, true);
            }
        } else {
            $password && $user['password'] = User::password($password);
            UserModel::update($user, compact('id'));
            $userData['userid'] = $id;
            UserData::updateOrCreate($userData, ['userid' => $id]);
        }
        return Admincp::url('/user/index');
    }

    public function deleting($ids = [])
    {
        if ($ids) {
            foreach ($ids as $key => $id) {
                UserModel::delete($id);
                UserData::delete($id);

                $where = ['userid' => $id];
                UserNodeModel::delete($where);
                UserOpenidModel::delete($where);
                UserFollowModel::delete($where);
                UserReportModel::delete($where);
                DB::hasTable('user_cdata') && DB::table('user_cdata')->delete($where);
            }
        }
        return true;
    }

    public static function shadow($data)
    {
        $id = $data['id'];
        $user_id = $data['user_id'];
        $account = $data['account'];
        $nickname = $data['nickname'];

        $has = UserModel::field('id')->where($user_id)->value();
        if ($has) {
            UserModel::update(['type' => '255'], ['id' => $user_id]);
        } else {
            $account = substr(md5('admins:' . $id), 8, 16);
            $user_id = UserModel::field('id')->where('account', $account)->value();
            if (empty($user_id)) {
                //创建管理员影子用户
                $user_id = UserModel::create([
                    'role_id'   => Config::get('user.register.role')?:3,
                    'account'   => $account,
                    'nickname'  => $nickname,
                    'password'  => User::password(random(32)),
                    'type'      => '255',
                    'status'    => '1',
                ]);
            }
        }
        return $user_id;
    }

    /**
     * 更新会员中心菜单缓存
     *
     * @return void
     */
    public static function done_ucpCache()
    {
        self::autoCacheUsercpMenu();
    }

    /**
     * autoCacheUsercpMenu 在更新所有缓存时，将会自动执行
     */
    public static function autoCacheUsercpMenu()
    {
        UserCenter::usercpMenuCache('default', true);
        UserCenter::usercpMenuCache('manage', true);
        UserCenter::usercpMenuCache('content', true);
    }
}
