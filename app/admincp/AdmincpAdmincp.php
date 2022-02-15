<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
// namespace app\admincp;

// use app\admincp\BaseAdmincp;

// class admincpAdmincp extends AdmincpBase extends BaseAdmincp {
// use iPHP\core\Security;
// use iPHP\core\Captcha;
// use iPHP\core\Script;
// use iPHP\core\Waf;

class AdmincpAdmincp extends AdmincpBase
{

    public function __construct()
    {
    }

    public static function createLog()
    {
        //默认开启
        if (!Config::get('debug.access_log')) {
            return;
        }

        $data = array(
            'uid'       => Member::$id,
            'username'  => Member::$nickname,
            'app'       => Admincp::$APP_NAME,
            'ip'        => Request::ip(),
            'uri'       => Request::server('REQUEST_URI'),
            'useragent' => Request::server('HTTP_USER_AGENT'),
            'method'    => Request::server('REQUEST_METHOD'),
            'referer'   => Request::server('HTTP_REFERER'),
            'create_time'   => Request::server('REQUEST_TIME'),
        );
        AdmincpLogModel::create($data);
    }
    /**
     * [退出登录]
     * @return [type] [description]
     */
    public function do_logout()
    {
        Member::logout();
    }
    /**
     * [操作记录]
     * @return [type] [description]
     */
    public function do_log()
    {
        $keywords = Request::get('keywords');
        $keywords && $where[] = array(
            'CONCAT(username,app,uri,useragent,ip,method,referer)',
            'REGEXP',
            $keywords
        );
        $uid = Request::get('uid');
        $uid && $where[] = ['uid', $uid];

        $sapp = Request::get('sapp');
        $sapp && $where[] = ['app', $sapp];

        $ip = Request::get('ip');
        $ip && $where[] = ['ip', $ip];

        $orderby = self::setOrderBy();

        $result = AdmincpLogModel::where($where)
            ->orderBy($orderby)
            ->paging();

        AdmincpBatch::$config['enable'] = false;
        include self::view("admincp.log");
    }
    public function do_system_info()
    {
        include self::view("system.info");
    }
    public function do_developer_info()
    {
        include self::view("developer.info");
    }
    public function quickView()
    {
        $btnList = Etc::many('*', 'admincp/home.quick*', true);
        $btnList = array_column($btnList, null, 'id');
        include self::view("admincp.quick");
    }
    public function statsView()
    {
        $lists = Etc::many('*', 'admincp/home.stats*');
        sortKey($lists);
        if ($lists) foreach ($lists as $key => $value) {

            $appid = substr($value['app'], 0, -6);
            if ($appid == 'content') {
                parse_str($value['do'], $output);
                $output['appId'] && $appid = $output['appId'];
            }
            $apps = Apps::get($appid);
            if(!$apps){
                unset($lists[$key]);
                continue;
            }
            $value['apps'] = $apps;
            if(empty($value['url'])){
                $value['url'] = sprintf("%s=%s&do=%s", ADMINCP_URL, $value['app'], $value['do']);
                $path = iAPP::path($apps['app'], ucfirst($value['app']).'Admincp');
                if(!is_file($path)){
                    unset($lists[$key]);
                    continue;
                }
            }
            $lists[$key]['url'] = $value['url'];
            $lists[$key]['id'] = md5($value['url']);
        }
        include self::view("admincp.stats");
    }
    /**
     * [后台管理首页]
     *
     * @return  [type]  [return description]
     */
    public function do_index()
    {
        include self::view("admincp.index");
    }
    /**
     * phpinfo
     */
    public function do_phpinfo()
    {
        if (Member::isSuperRole()) {
            phpinfo();
        }
    }
    /**
     * 版本信息
     */
    public function do_version()
    {
        return [
            'iCMS_GIT_COMMIT'   => iCMS_GIT_COMMIT,
            'iCMS_GIT_AUTHOR'   => iCMS_GIT_AUTHOR,
            'iCMS_GIT_EMAIL'    => iCMS_GIT_EMAIL,
            'iCMS_GIT_TIME'     => iCMS_GIT_TIME,
            'iCMS_VERSION' => iCMS_VERSION,
            'iCMS_RELEASE' => iCMS_RELEASE
        ];
    }
    // 检测函数支持
    public function isfun($fun = '')
    {
        if (!$fun || trim($fun) == '' || preg_match('~[^a-z0-9\_]+~i', $fun)) {
            return '错误';
        }
        return Script::check((false !== @function_exists($fun)));
    }
    //检测PHP设置参数
    public function check($varName)
    {
        switch ($result = get_cfg_var($varName)) {
            case 0:
                return Script::check(0);
                break;
            case 1:
                return Script::check(1);
                break;
            default:
                return $result;
                break;
        }
    }
}
