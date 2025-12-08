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

define('USER_AUTHASH', '#=(iCMS@' . iPHP_KEY . '@iCMS)=#');

class User
{
    public const APP = 'user';
    public const APPID = iCMS_APP_USER;

    public static $id = 0;
    public static $openid = null;
    public static $account = '';
    public static $nickname = '';
    public static $cookieTime = 0;
    public static $format = false;
    public static $config = [];
    public static $callback = []; //回调
    public static $data = []; //在iCMS::run 已经初始化
    public const AUTH_KEY = 'USER_AUTH';
    public const AUTH_TOKEN = 'USER_TOKEN'; //只用来判断是否登录

    public static function init()
    {
        self::$config = Config::get('user');
        Files::init(['userid' => self::$id]);
    }

    /**
     * 获取登陆链接
     *
     * @param String $base 替换域名
     * @return void
     */
    public static function getLoginUrl($name = null, $base = null)
    {
        $route = 'user/login';
        $name && $route .= '/' . $name;
        $url = Route::routing($route);
        $base && $url = str_replace(rtrim(iCMS_URL, '/'), $base, $url);
        return $url;
    }

    public static function getPicDir($uid, $dir = 'avatar')
    {
        $nuid = abs(intval($uid));
        $nuid = sprintf('%08d', $nuid);
        $dir1 = substr($nuid, 0, 3);
        $dir2 = substr($nuid, 3, 2);
        $path = $dir . '/' . $dir1 . '/' . $dir2;
        return $path;
    }

    public static function getPic($uid, $size = 0, $dir = 'avatar')
    {
        $path = self::getPicDir($uid, $dir) . '/' . $uid . '.jpg';
        if ($size) {
            $path .= '_' . $size . 'x' . $size . '.jpg';
        }
        return $path;
    }

    public static function route($type, $id = null, $size = 0)
    {
        empty($id) && $id = self::$id;
        switch ($type) {
            case 'avatar':
                $path = self::getPic($id);
                $rootpath = FilesClient::getRoot($path);
                if (!@is_file($rootpath)) {
                    return iCMS_PUBLIC_URL . '/assets/img/avatar.gif';
                }
                return iCMS_FILE_URL . self::getPic($id, $size);
                break;
            case 'url':
                return Route::routing('{userid}/home', [$id]);
                break;
            case 'coverpic':
                $dir = self::getPicDir($id, 'coverpic');
                return [
                    'pc' => FilesClient::getUrl($dir . '/' . $id . '.jpg'),
                    'mo' => FilesClient::getUrl($dir . '/m_' . $id . '.jpg')
                ];
                break;
            case 'urls':
                return [
                    'inbox'    => Route::routing('message/userid/{userid}', [$id]),
                    'home'     => Route::routing('{userid}/home', [$id]),
                    'comment'  => Route::routing('{userid}/comment', [$id]),
                    'favorite' => Route::routing('{userid}/favorite', [$id]),
                    'fans'     => Route::routing('{userid}/fans', [$id]),
                    'follower' => Route::routing('{userid}/follower', [$id]),
                ];
                break;
        }
    }

    public static function uinfo($user, $name = '')
    {
        $id = $user['id'] ?: 0;
        $nickname = ($user['nickname'] ?: Lang('anonymous'));
        empty($name) && $name = $nickname;
        $url = $user['urls']['home'] ?: 'javascript:;';
        $slogan = $user['data']['slogan'] ?: Lang('no slogan');
        $format = '<a href="%s" class="cms-UserLink" target="_blank" i="cms@h:user:ucard" data-userid="%d">%s</a>';
        $link = sprintf($format, $url, $id, $name);
        $at = sprintf($format, $url, $id, '@' . $name);
        $avatar = self::route('avatar', $id);
        return compact(['id', 'nickname', 'name', 'url', 'avatar', 'link', 'at', 'slogan']);
    }

    public static function column($resource, $key, $value = null)
    {
        is_null($value) && $value = $key;
        return User::info(array_column($resource, $value, $key));
    }

    public static function info($user, $name = '')
    {
        if (is_array($user)) {
            $userArr = self::get(array_keys($user));
            $info = [];
            foreach ($user as $id => $name) {
                $name === $id && $name = null;
                $info[$id] = self::uinfo($userArr[$id], $name);
            }
            return $info;
        } elseif (is_numeric($user)) {
            $user = self::get($user);
            $info = self::uinfo($user, $name);
        } else {
            $info = self::uinfo($user, $name);
        }
        self::$callback['info'] && iPHP::callback(self::$callback['info'], [&$info]);
        return $info;
    }

    public static function value($val, $field = 'account', $where = 'id')
    {
        return UserModel::field($field)->where($where, $val)->value();
    }

    public static function check($val, $field = 'account')
    {
        $id = UserModel::field('id')->where($field, $val)->value();
        return empty($id) ? false : $id;
    }

    public static function data($user = [])
    {
        if(is_numeric($user)){
            $user = self::get($user);
        }
        empty($user) && $user = self::$data;
        $user['role'] = array_filter_keys($user['role'], 'name', true);
        $user = array_filter_keys($user, 'id,nickname,name,url,avatar,article,gender,comment,fans,favorite,follow,role,money,credit,scores,free', true);
        return $user;
    }

    public static function updateInc($field, $id, $step = 1)
    {
        return self::updateCount($field, $id, $step, 'inc');
    }

    public static function updateDec($field, $id, $step = 1)
    {
        return self::updateCount($field, $id, $step, 'dec');
    }

    public static function updateCount($field, $id, $step = 1, $func = 'inc')
    {
        if ($func == 'dec') {
            return UserModel::where($id)->where($field, '>', 0)->$func($field, $step);
        }
        return UserModel::where($id)->$func($field, $step);
    }

    public static function getCache($id)
    {
        return Cache::get(iPHP_APP . ':user:' . $id);
    }

    public static function setCache($id)
    {
        $user = UserModel::get($id);
        unset($user['password']);
        Cache::set('user/' . $user['id'], $user, 0);
    }

    /**
     * Undocumented function
     *
     * @param integer $ids
     * @param boolean $unpass
     * @param string $field
     * @return array
     */
    public static function get($ids = 0, $unpass = true, $field = 'id')
    {
        if (empty($ids)) {
            return [];
        }

        is_array($ids) && $ids = array_unique($ids);

        $where[$field] = $ids;
        $where['status'] = '1';
        $model = UserModel::where($where);
        $unpass && $model->withoutField('password');
        if (is_numeric($ids)) {
            $result = $model->get();
            $userdata = UserData::gets([$result['id']]);
            self::item($result, $userdata);
        } else {
            $result = $model->select();
            $result = array_column($result, null, 'id');
            $uidArray = array_column($result, 'id');
            $userdata = UserData::gets($uidArray);
            foreach ($result as $key => &$value) {
                self::item($value, $userdata);
            }
        }
        return $result;
    }

    private static function item(&$user, $userdata)
    {
        if ($user) {
            $user['data'] = $userdata[$user['id']] ?: [];
            $user['genderText'] = $user['gender'] > 1 ?: ($user['gender'] ? 'male' : 'female');
            $user['avatar'] = self::route('avatar', $user['id']);
            $user['urls'] = self::route('urls', $user['id']);
            $user['coverpic'] = self::route('coverpic', $user['id']);
            $user['url'] = $user['urls']['home'];
            $user['inbox'] = $user['urls']['inbox'];
            $user['name'] = $user['nickname'];
            $user['role'] = Role::get($user['role_id']);
            // $user['data']     = UserData::gets($user['id']);
            //用户点数+角色点数  不增不减
            $user['scores'] = $user['scores'] + $user['role']['scores'];

            $url = sprintf(
                '%s?app=user&do=hits&id=%d',
                Route::routing('api'),
                $user['id']
            );
            $user['hits'] = [
                'script' => $url,
                'count'  => $user['hits'],
                'today'  => $user['hits_today'],
                'yday'   => $user['hits_yday'],
                'week'   => $user['hits_week'],
                'month'  => $user['hits_month'],
            ];
        }
        return $user;
    }

    public static function deAuth($auth)
    {
        return Crypt::decrypt($auth);
    }

    /**
     * Undocumented function
     *
     * @param boolean $flag 是否清除账号密码 默认true
     * @return void
     */
    public static function getCookie($flag = true)
    {
        if (self::$callback['cookie:get']) {
            return self::$callback['cookie:get'];
        }
        $token = Cookie::get(self::AUTH_TOKEN);
        $auth = Cookie::get(self::AUTH_KEY);
        $userid = Cookie::get('userid');
        $nickname = Cookie::get('nickname');

        if ($token == Security::secureToken($auth)) {
            $data = Crypt::decrypt($auth);

            if (empty($data)) {
                return false;
            }

            $userid = Crypt::decrypt($userid);
            $nickname = Crypt::decrypt($nickname);

            if ((int)$userid === (int)$data['id'] && $nickname === $data['nickname']) {
                self::$id = (int)$data['id'];
                self::$nickname = $data['nickname'];
                // $data['userid'] = self::$id;
                if ($flag) {
                    unset($data['account'], $data['password']);
                }
                return $data;
            }
            //self::logout();
        }
        return false;
    }

    public static function setCookie($user)
    {
        if (self::$callback['cookie:set']) {
            iPHP::callback(self::$callback['cookie:set'], [&$user]);
        }

        $data = array_filter_keys($user, 'id,account,password,nickname,status');
        $auth = Crypt::encrypt($data);

        Cookie::set(self::AUTH_KEY, $auth, self::$cookieTime);
        Cookie::set(self::AUTH_TOKEN, Security::secureToken($auth), self::$cookieTime, false);
        Cookie::set('userid', Crypt::encrypt($user['id']), self::$cookieTime, false);
        Cookie::set('nickname', Crypt::encrypt($user['nickname']), self::$cookieTime, false);

        return $auth;
    }

    public static function isLogin()
    {
        if (!self::$id) {
            $message = Lang::get('user:login:not') ?: 'Please login';
            iPHP::alert($message, -9999, [self::getLoginUrl(), ['app@user', 'login']]);
        }
    }

    public static function status()
    {
        $status = false;
        $user = [];
        $auth = self::getCookie(false);
        if ($auth) {
            $user = self::get($auth['id'], false);
            $status = (
                $auth['account'] == $user['account'] &&
                $auth['password'] == $user['password']
            );
            unset($user['password']);
            $status && User::$data = $user;
        }
        return $status;
    }

    public static function logout()
    {
        Cookie::del(self::AUTH_KEY);
        Cookie::del(self::AUTH_TOKEN);
        Cookie::del('userid');
        Cookie::del('nickname');
        Cookie::del('captcha');
    }

    public static function password($value)
    {
        return md5($value);
        // return Security::secureToken($value);
    }

    public static function defaultRole()
    {
        return isset(User::$config['register']['role']) ? User::$config['register']['role'] : Role::USER_DEFAULT;
    }

    public static function checkStatus($status)
    {
        if ($status !== 1) {
            $text = UserModel::$statusMap[$status];
            $msg = 'user:status:' . $status;
            throw new sException($msg);
        }
    }
}
