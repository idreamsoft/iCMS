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
    public function __construct()
    {
        parent::__construct();
        self::$primaryKey = UserModel::getPrimaryKey();
        $this->uid = (int)$_GET['id'];
    }

    public function save_config()
    {
        Config::$data = Request::post('config');
        foreach ((array)Config::$data['open'] as $key => $value) {
            if ($value['appid'] && $value['appkey']) {
                Config::$data['open'][$key]['enable'] = true;
            }
        }
        Config::save(self::$appId);
        // return self::success('保存成功');
    }

    /**
     * 上传头像
     *
     * @return void
     */
    public static function do_uploadAvatar($uid=0)
    {
        empty($uid) && $uid = (int)Request::get('uid');
        FilesMark::$enable = false;
        Files::$check_data  = false;
        FilesCloud::$enable = false;
        FilesClient::$config['allow_ext'] = 'jpg,png,jpeg';
        $path = get_user_pic($uid);
        $udir = dirname($path);
        try {
            return FilesClient::upload('upfile', $udir, $uid);
        } catch (\Exception $ex) {
            iJson::error($ex->getMessage());
        }
    }
    public static function do_deck()
    {
        include self::view("user.deck");
    }
    public function do_add()
    {
        if ($this->uid) {
            $user = UserModel::get($this->uid);
            $user && $userdata = UserData::get($this->uid);
        }
        self::added($this, __METHOD__, $user);
        include self::view("user.add");
    }
    /**
     * [登录用户]
     * @return [type] [description]
     */
    public function do_login()
    {
        if ($this->uid && Member::isSuperRole()) {
            $user = UserModel::get($this->uid);
            User::setCookie($user);
            $url = Route::routing('{uid}/home', [$this->uid]);
            Helper::redirect($url);
        }
    }
    public function do_manage()
    {

        $wxappid = Request::get('wxappid');
        $wxappid && $where['account'] = array('like', "%@{$wxappid}%");

        $keywords = Request::get('keywords');
        $keywords && $where['CONCAT(account,nickname)'] = array('like', "%{$keywords}%");

        $role_id = Request::get('role_id');
        $role_id && $where['role_id'] = $role_id;

        $status = Request::get('status');
        is_numeric($status) && $where['status'] = $status;

        $regip = Request::get('regip');
        $regip && $where['regip'] = $regip;

        $loginip = Request::get('loginip');
        $loginip && $where['loginip'] = $loginip;

        // $pid = $_GET['pid'];
        // if (is_numeric($_GET['pid'])) {
        //     $uri_array['pid'] = $pid;
        //     if ($_GET['pid'] == 0) {
        //         $sql .= " AND `pid`=''";
        //     } else {
        //         iMap::init('prop', self::$appId, 'pid');
        //         $map_where = iMap::where($pid);
        //     }
        // }

        // if ($map_where) {
        //     $map_sql = iSQL::select_map($map_where);
        //     $sql     = ",({$map_sql}) map {$sql} AND `uid` = map.`iid`";
        // }

        $orderby = self::setOrderBy(array(
            'uid'        => "UID",
            'hits'       => "点击",
            'hits_week'  => "周点击",
            'hits_month' => "月点击",
            'fans'       => "粉丝数",
            'follow'     => "关注数",
            'article'    => "文章数",
            'favorite'   => "收藏数",
            'comment'   => "评论数",
        ));

        $result = UserModel::where($where)
            ->orderBy($orderby)
            ->paging();

        include self::view("user.manage");
    }
    public function save()
    {
        $user = UserModel::postData();
        $userData = UserData::postData();

        $uid      = (int)$user['uid'];
        $account  = $user['account'];
        $phone    = $user['phone'];
        $email    = $user['email'];
        $nickname = $user['nickname'];
        $password = $user['password'];
        unset($user['password']);

        $nickname or self::alert('昵称不能为空');
        $account or self::alert('账号不能为空');

        preg_match("/^[\w_\-@\.]+$/i", $account) or self::alert('账号格式错误，只能由英文字母、数字或_-组成,不支持中文');

        if ($phone) {
            preg_match("/^1[34578]\d{9}$/i", $phone) or self::alert('手机号格式错误');
        }
        if ($email) {
            preg_match("/^[\w\-\.]+@[\w\-]+(\.\w+)+$/i", $email) or self::alert('邮箱格式错误');
        }

        $user['regdate']       = str2time($user['regdate']);
        $user['lastlogintime'] = str2time($user['lastlogintime']);

        UserModel::check(compact('account'), $uid) && self::alert('该账号已经存在');
        UserModel::check(compact('nickname'), $uid) && self::alert('该昵称已经存在');

        if (empty($uid)) {
            $password or self::alert('密码不能为空');
            $user['password'] = User::password($password);
            $user['uid'] = UserModel::create($user, true);
            if ($user['uid']) {
                $userData['uid'] = $user['uid'];
                UserData::create($userData, true);
            }
        } else {
            $password && $user['password'] = User::password($password);
            UserModel::update($user, compact('uid'));
            $userData['uid'] = $uid;
            UserData::updateOrCreate($userData, compact('uid'));
        }
        $user['id'] = $user['uid'];
        self::saved($this, __METHOD__, $user);
        // return self::success('保存成功');
    }
    public function do_batch()
    {
        $actions = array(
            'prop' => function ($idArray, $ids, $batch) {
                $pid = Request::post('pid');
                UserModel::update(compact('pid'), array('uid' => $idArray));
            },
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    $this->do_delete((int)$id);
                }
            },
        );

        return AdmincpBatch::run($actions, "用户");
    }
    public function do_delete($uid = null)
    {
        $uid === null && $uid = $this->uid;
        $uid or self::alert('请选择要删除的用户');
        UserModel::delete($uid);
        UserData::delete($uid);

        $where = ['user_id' => $uid];
        UserNodeModel::delete($where);
        UserOpenidModel::delete($where);
        UserFollowModel::delete($where);
        UserReportModel::delete($where);
        DB::hasTable('user_cdata') && DB::table('user_cdata')->delete($where);

        // return self::success('用户删除完成');
    }

    public static function shadow($data)
    {
        $id  = $data['id'];
        $user_id  = $data['user_id'];
        $account = $data['account'];
        $nickname = $data['nickname'];

        $has = UserModel::field('uid')->where($user_id)->value();
        if ($has) {
            UserModel::update(['type' => '255'], array('uid' => $user_id));
        } else {
            $account = substr(md5('members:' . $id), 8, 16);
            $user_id = UserModel::field('uid')->where('account', $account)->value();
            if (empty($user_id)) {
                //创建管理员影子用户
                $user_id = UserModel::create(array(
                    'role_id'   => Config::get('user.register.role'),
                    'account' => $account,
                    'nickname' => $nickname,
                    'password' => User::password(random(32)),
                    'type'     => '255',
                    'status'   => '1',
                ));
            }
        }
        return $user_id;
    }

    /**
     * [autoCacheUsercpMenu 在更新所有缓存时，将会自动执行]
     */
    public static function autoCacheUsercpMenu()
    {
        UserCP::usercpMenuCache('manage');
        UserCP::usercpMenuCache('content');
    }

    public static function widget_count()
    {
        $total = UserModel::count();
        $widget[] = array($total, '全部');
        foreach (User::$statusMap as $status => $text) {
            $count = UserModel::where('status', $status)->count();
            $widget[] = array($count, $text);
        }
        return $widget;
    }
}
