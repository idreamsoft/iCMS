<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');

class UserApp extends AppsBase
{
    public $openid = null;
    public $instance = null;
    public $me = [];

    public static $LOGIN = ['report', 'follow'];

    public function __construct()
    {
        User::init();
        UserUtils::forward();
        $this->subApp();
    }

    /**
     * 子应用
     *
     * @return void
     */
    public function subApp()
    {
        $do = Application::$DO;
        $subApp = sprintf('User%sApp', ucfirst($do));
        $path = Application::path('user', $subApp);
        //app=user&do=manage => UserManageApp
        //app=user&do=content => UserContentApp
        //app=user&do=home => UserHomeApp
        if (@is_file($path)) {
            $this->instance = new $subApp();
        } elseif (in_array($do, UserPassportApp::$apiList)) {
            //['login', 'logout', 'register', 'findpwd']
            $this->instance = new UserPassportApp();
        }
    }

    public function api_report()
    {
        $params = Request::post('params');
        $content = Request::post('content');
        $reason = (int)Request::post('reason');
        $params or self::alert('iCMS:empty:params');

        $iid = (int) $params['id'];
        $uid = (int) $params['userid'];
        $appid = (int) $params['appid'];
        $app = $params['app'];

        $iid or self::alert('user:report:empty:iid');
        $uid or self::alert('user:report:empty:uid');

        $create_time = time();
        $ip = Request::ip();
        $userid = User::$id;
        $status = 0;

        $data = compact(['appid', 'app', 'userid', 'iid', 'uid', 'reason', 'content', 'param', 'ip', 'create_time', 'status']);
        $id = UserReportModel::create($data, true);
        return [true, 'user:report:success'];
    }

    public function api_follow()
    {
        $params = Request::post('params');

        $userid = (int) User::$id; //关注者ID

        $name = User::$nickname;

        $fuid = (int) $params['userid']; //被关注者ID
        $follow = (bool) $params['follow'];

        $fuid or self::alert('iCMS:error');

        if ($follow) {//已关注的 取消关注
            UserFollowModel::delete(compact('userid', 'fuid'));
            User::updateDec('follow', $userid);
            User::updateDec('fans', $fuid);
            return [['follow'=>0], 'user:follow:cancel'];
        } else {//未关注的 关注
            $userid == $fuid && self::alert('user:follow:self');
            $check = UserFollow::is($userid, $fuid);
            if (!$check) {
                $fname = User::value($fuid, 'nickname');
                $fields = ['userid', 'name', 'fuid', 'fname'];
                $data = compact($fields);
                UserFollowModel::create($data, true);
                User::updateInc('follow', $userid);
                User::updateInc('fans', $fuid);
            }
            return [['follow'=>1], 'user:follow:success'];
        }
    }

    public function api_hits($uid = null)
    {
        $uid === null && $uid = (int) $_GET['uid'];
        $uid && AppsBase::updateHits('user', $uid, 'uid');
    }

    /**
     * 获取用户状态
     *
     * @return void
     */
    public function api_status()
    {
        if (User::$id) {
            return [
                'user'        => User::data(),
                'message_num' => MessageApp::_count(User::$id),
            ];
        } else {
            return [false, 'user:login:not'];
        }
    }

    /**
     * 用户时间线，获取收藏、点赞等数据
     *
     * @return Array
     */
    public function api_timeline()
    {
        $params = Request::post('params');
        $result = [];
        if ($userid = User::$id) {
            $events = UserTimeline::events($params['appid'], $params['id'], $userid);
            $favorited = FavoriteDataModel::get([
                'appid' => $params['appid'],
                'iid'   => $params['id'],
                'userid'=> $userid
            ]);
            if ($favorited) {
                $events['favorite'] = $favorited;
            }
            foreach (UserTimeline::$eventNames as $event => $name) {
                if ($events[$event]) {
                    $result[$name] = 1;
                }
            }
        }
        return $result;
    }

    public function api_reminder()
    {
        $auth = Request::get('auth');
        if ($auth) {
            $authcode = rawurldecode($auth);
            $authcode = base64_decode($authcode);
            $authcode = auth_decode($authcode);

            if (empty($authcode)) {
                exit;
            }
            list($uid, $account, $password, $timeline) = explode(USER_AUTHASH, $authcode);
            $now = time();
            if ($now - $timeline > 86400) {
                exit;
            }
            $user = User::get($uid, false);
            if ($account != $user['account'] || $password != $user['password']) {
                exit;
            }
            unset($user['password']);
            View::assign('auth', $auth);
            View::assign('user', (array) $user);
            View::display('iCMS://user/resetpwd.htm');
        } else {
            View::display('iCMS://user/reminder.htm');
        }
    }

    public function API_collections()
    {
        //View::display('iCMS://user/card.htm');
    }

    /**
     * 获取用户信息
     *
     * @return void
     */
    public function api_info()
    {
        $userid = (int)Request::post('userid');
        if ($userid) {
            $userArr = User::get($userid);
            if ($userArr) {
                $data = $userArr['data'];
                $user = array_filter_keys($userArr, 'id,nickname,url,urls,avatar,article,gender,comment,fans,favorite,follow', true);
                $user['country'] = $data['country'];
                $user['province'] = $data['province'];
                $user['slogan'] = $data['slogan'];
                $user['personstyle'] = $data['personstyle'];
                $user['is_following'] = (int)UserFollow::is(User::$id, $user['id']); //关注
                $user['is_followed'] = (int)UserFollow::is($user['id'], User::$id); //被关注
                $user['is_blocking'] = false; //拉黑

                $user['allow_message'] = true; //默认都可以发私信
                if (isset($userArr['setting']['message'])) {
                    $receive = $userArr['setting']['message']['receive'];
                    if ($receive == 'all') {
                        //允许所有人给我发私信
                        $user['allow_message'] = true;
                    } elseif ($receive == 'follow') {
                        //只允许我关注的人给我发私信
                        $user['allow_message'] = $user['is_followed'] ? true : false;
                    } elseif (!$receive) {
                        //关闭私信
                        $user['allow_message'] = false;
                    }
                }
                //拉黑 不允许发私信
                $user['is_blocking'] && $user['allow_message'] = false;

                View::assign('user', $user);
                View::assign('userdata', $data);
                $html = View::fetch('iCMS://user/card.htm');
                return compact('html', 'user');
            }
        }
        return false;
    }
}
