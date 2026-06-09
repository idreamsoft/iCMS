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

class PatchAdmincp extends AdmincpBase
{
    public $patch = '';
    public function __construct()
    {
        parent::__construct();
        Hooks::set('File.delete', null);
        if (isset($_GET['git'])) {
            Patch::$release = Request::sget('release');
            $info = Cache::get('patch/git.info');
            if(is_array($info)){
                Patch::$zipName = $info['name'];
                Patch::$gitFiles = $info['files'];
            }
            // Patch::$test = true;
        } else {
            $this->patch = Patch::init(isset($_GET['force']) ? true : false);
        }
    }

    public function do_roll()
    {
        exit("开发中");
    }
    public function done_check_release()
    {
        $msg = sprintf(
            '您使用的 iCMS 版本,目前是最新版本<hr />当前版本：iCMS %s [%s]',
            iCMS_VERSION,
            iCMS_RELEASE
        );
        $code = 3;
        $url  = '';
        if ($this->patch) {
            $msg = sprintf(
                '发现iCMS最新版本<br /><span class="label label-warning">iCMS %s [%s]</span><br />%s<hr />您当前使用的版本<br /><span class="label label-info">iCMS %s [%s]</span><br /><br />',
                $this->patch[0],
                $this->patch[1],
                $this->patch[3],
                iCMS_VERSION,
                iCMS_RELEASE
            );
            $code = (int)Config::get('system.patch');
            switch ($code) {
                case "1": //自动下载,安装时询问
                    $msg .= Patch::download();
                    $url = ADMINCP_URL . '?app=patch&do=install&CSRF_TOKEN=' . Security::$CSRF_TOKEN;
                    $msg .= "新版本已经下载完成!! 是否现在更新?";
                    break;
                case "2": //不自动下载更新,有更新时提示
                    $url = ADMINCP_URL . '?app=patch&do=download&CSRF_TOKEN=' . Security::$CSRF_TOKEN;
                    $msg .= "请马上更新您的iCMS!!!";
                    break;
            }
        }
        return [
            "data" => (array)$this->patch,
            "message" => $msg,
            "url" => $url,
            "code" => $code
        ];
        // self::success((array)$this->patch, $msg, $url, $code);
    }
    /**
     * 下载升级包
     */
    public function do_download()
    {
        $log = Patch::download($flag); //下载文件包
        AdmincpView::set('breadcrumb', false);
        include self::view("patch");
    }
    /**
     * 安装升级包
     */
    public function do_install()
    {
        Patch::setTime();
        // Patch::$test = true;
        $log  = Patch::update(); //更新文件
        AdmincpView::set('breadcrumb', false);
        include self::view("patch");
    }
    /**
     * 升级程序
     */
    public function do_upgrade()
    {
        // Patch::$test = true;
        $log  = Patch::run(); //升级
        AdmincpView::set('breadcrumb', false);
        include self::view("patch");
    }
    public function done_check_upgrade()
    {
        Patch::setTime();
        $files = Patch::get_upgrade_files();
        Patch::$upgrade = null;
        $message = '';
        $json = [];
        if ($files) {
            foreach ($files as $d => $value) {
                $title = null;
                if (stripos($value, '.php') !== false) {
                    include_once $value;
                }
                $message .= $title ?
                    sprintf('<p class="my-1">【%s】升级程序</p>', $title) :
                    sprintf('<p class="my-1">第【%s】号升级程序</p>', $d);                    //code...
            }
            $json = array(
                'code' => 2,
                'upgrade_url'  => ADMINCP_URL . '?app=patch&do=upgrade&force=1&CSRF_TOKEN=' . Security::$CSRF_TOKEN,
                'message' => "发现！<br /><div>" . $message . "</div><hr class='clearfix'/>是否现在进行升级?",
            );
        }
        return $json;
    }

    //===================git=========
    /**
     * 开发版升级检查
     */
    public function do_git_log()
    {
        $log =  Patch::git('log');
        $last = Config::vget('patch.last');
        include self::view("git/log");
    }
    /**
     * 下载开发版升级包
     */
    public function do_git_download()
    {
        $info = Patch::git('zip');
        !is_array($info) && Application::nil('开发版升级包下载出错');
        $release = Request::sget('release');
        Cache::set('patch/git.info', $info, 3600);
        $url = sprintf('%s/download/release/%s/git/true', APP_URL, $release);
        Utils::jumpto($url);
    }
    public function do_git_diff()
    {
        $log =  Patch::git('diff');
        $type_map = array(
            'D' => '删除',
            'A' => '增加',
            'M' => '更改'
        );
        include self::view("git/diff");
    }
    /**
     * 查看开发版信息
     */
    public function do_git_show()
    {
        $log =  Patch::git('show');
        $type_map = array(
            'D' => '删除',
            'A' => '增加',
            'M' => '更改'
        );
        include self::view("git/show");
    }
    /**
     * 检查版信息
     */
    public static function done_check_version()
    {
        $data = Patch::version();
        if ($data['code']) {
            return $data;
        }
    }
    /**
     * 版本检查
     */
    public static function check_version()
    {
        include self::view("check_version", "patch");
    }
    /**
     * 升级检查
     */
    public static function check_release()
    {
        if (Config::get('system.patch') && Admin::isSuperRole()) {
            include self::view("check_release", "patch");
        }
    }
    /**
     * git检查
     */
    public static function check_upgrade()
    {
        if (Admin::isSuperRole()) {
            include self::view("check_upgrade", "patch");
        }
    }
}
