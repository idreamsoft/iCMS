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

/**
 * User's Control Panel
 */
class UserCenter
{
    public $methods = [];
    public $openid = null;

    public function __construct()
    {
        User::init();
        UserUtils::forward();
        User::isLogin();
        self::assign(); //模板变量赋值
    }

    public function __destruct()
    {
        Application::destruct();
    }

    public static function checkRole($array)
    {
        is_array($array) or $array = explode(',', $array);
        $roleId = User::$data['role_id'];
        if ($array && in_array($roleId, $array)) {
            return true;
        }
        return false;
    }

    public static function assign($ud = true)
    {
        $user = [];
        $status = ['logined' => false, 'followed' => false, 'isme' => false];
        if ($userid = (int) Request::get('userid')) {
            $user = User::get($userid);
            empty($user) && AppsBase::alert(['user:not_found', [$userid]], 'U10001');
        }
        $me = User::$data; //判断是否登录
        if ($me) {
            $status['logined'] = true;
            $user['uid'] && $status['followed'] = (int) UserFollow::is($me['uid'], $user['uid']);
            empty($user) && $user = $me;
            if ($user['uid'] == $me['uid']) {
                $status['isme'] = true;
                $user = $me;
            }
        }

        $token = Security::csrf_token($user['uid'], date('Ymd'));

        View::assign([
            'USER'       => $user,
            'USER_TOKEN' => $token,
            'USER_STATUS'=> $status
        ]);
    }

    public static function extractUrls($menuList,$key = 'url')
    {
        $urls = [];

        foreach ($menuList as $item) {
            // 如果当前项有 'url' 键，则将其添加到结果数组中
            if (isset($item[$key])) {
                $urls[] = $item[$key];
            }

            // 如果当前项有 'children' 键，则递归调用函数处理子数组
            if (isset($item['children']) && is_array($item['children'])) {
                $urls = array_merge($urls, self::extractUrls($item['children'],$key));
            }
        }

        return $urls;
    }

    public static function view($key, $s = null)
    {
        $menu = [];
        $breadcrumb = [];
        $breadcrumb[] = [
            'url'     => 'user/' . $key,
            'caption' => Lang::get('usercp:' . $key . ':home')
        ];

        $menuList1 = self::usercpMenuCache('default');
        $menuList2 = self::usercpMenuCache($key, 0);
        $menuList = array_merge($menuList1, $menuList2);

        if ($menuList) {
            is_null($s) && $s = Request::sget('s');
            is_null($s) && $s = Request::sget('sdo');
            is_null($s) && $s = 'home';
            // $uriArr = [Request::sget('app'), Request::sget('do'), Request::sget('sdo')];
            // $uriArr = array_filter($uriArr);
            // $uri = implode('/', $uriArr);
            // $menuUrls = self::extractUrls($menuList, 'url');
            // var_dump($uri, $menuUrls);
            // in_array($uri, $menuUrls) or AppsBase::alert('UserCenter Illegal access');
            $menuIds = array_column($menuList, 'id');
            in_array($s, $menuIds) or AppsBase::alert('UserCenter Illegal access');
            $menu = $menuList[$s] ?: $menuList['home'];
            $menu['id'] != 'home' && $breadcrumb[] = array_filter_keys($menu, 'children', false);
            View::assign('navopen', $s);
        }

        if ($menu['children']) {
            $childrenIds = array_column($menu['children'], 'id');
            $app = Request::sget('app');
            $do = Request::sget('do');
            $ss = $app . ucfirst($do);
            $idx = array_search($ss, $childrenIds);
            $idx === false && AppsBase::alert('children Illegal access');
            $children = $menu['children'][$idx];
            $breadcrumb[] = $children;
            // View::assign('children', $children);
            // View::assign('template', $children['template']);
            $menu = end($breadcrumb);
        }
        $menu['app'] && View::$app = $menu['app']; //增加 template/paymentApp
        View::assign('menuList', $menuList);
        View::assign('breadcrumb', $breadcrumb);
        View::assign('menu', $menu);
        return View::display('iCMS://user/usercp.htm');
    }

    /**
     * usercp.menu.manage.json
     * usercp.menu.content.json
     *
     * @param [type] $key
     * @return array
     */
    public static function usercpMenuCache($key, $cache = '-1')
    {
        $idx = 'menu/usercp.' . $key;
        return Cache::KV($idx, function () use ($idx) {
            $data = Etc::many('*', $idx . '*', true);
            $data = array_column($data, null, 'id');
            return $data;
        }, $cache);
    }
}
