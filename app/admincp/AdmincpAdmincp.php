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

// class admincpAdmincp extends AdmincpCommon extends BaseAdmincp {
// use iPHP\core\Security;
// use iPHP\core\Captcha;
// use iPHP\core\Script;
// use iPHP\core\Waf;

class AdmincpAdmincp extends AdmincpBase
{
    public function headView()
    {
        include self::view('block.hero');
        include self::view('block.version');
    }

    public function contentView()
    {
        //*/view/plugin/home/content.asd.html
        list($views, $pattern) = AdmincpView::scan('plugin/home/content.*', '*', 1);
        if ($views) {
            foreach ($views as $key => $value) {
                include self::view($value[1], $value[0]);
            }
        }
    }

    public function quickView()
    {
        $btnList = Etc::many('*', 'admincp/home.quick*', true);
        $btnList = array_column($btnList, null, 'id');
        include self::view('block.quick');
    }

    public function statsView()
    {
        include self::view('block.stats');
    }

    /**
     * 管理首页
     *
     * @return void
     */
    public function do_dashboard()
    {
        include self::view();
    }

    public function do_stats()
    {
        $blocks = [
            [
                'title' => '文章',
                'count' => (int)iPHP::callback('ArticleModel::count'),
                'icon'  => 'fa fa-briefcase text-primary',
                'enbled'=> Apps::check('article')
            ],
            [
                'title' => '节点/分类',
                'count' => (int)iPHP::callback('NodeModel::count'),
                'icon'  => 'fa fa-folder text-primary',
                'enbled'=> Apps::check('node')
            ],
            [
                'title' => '标签',
                'count' => (int)iPHP::callback('TagModel::count'),
                'icon'  => 'fa fa-tags text-primary',
                'enbled'=> Apps::check('tag')
            ],
            [
                'title' => '评论',
                'count' => (int)iPHP::callback('CommentModel::count'),
                'icon'  => 'fa fa-comments text-primary',
                'enbled'=> Apps::check('comment')
            ],
            [
                'title' => '用户',
                'count' => (int)iPHP::callback('UserModel::count'),
                'icon'  => 'fa fa-users text-primary',
                'enbled'=> Apps::check('user')
            ],
            [
                'title' => '应用',
                'count' => (int)iPHP::callback('AppsModel::count'),
                'icon'  => 'fa fa-plug text-primary',
                'enbled'=> Apps::check('apps')
            ],
            [
                'title' => '文件',
                'count' => (int)iPHP::callback('FilesModel::count'),
                'icon'  => 'fa fa-paint-brush text-primary',
                'enbled'=> Apps::check('files')
            ],
        ];
        $rows = AppsModel::where(['apptype'=>3,'type'=>3])->select();
        foreach ($rows as $key => $value) {
           $model = Content::model($value);
           $blocks[] = [
                'title' => $value['title'],
                'count' => (int)$model->count(),
                'icon'  => 'fa fa-cube text-primary',
                'enbled'=> true
            ];
        }
        return [
            'blocks' => $blocks
        ];
    }

    public function do_setlang()
    {
        Session::set('language', Request::get('lg'));
        Utils::jumpto(ADMINCP_URL);
    }

    /**
     * 后台管理框架
     *
     * @auth no
     * @return void
     */
    public function do_index()
    {
        $path = '/admincp/dashboard';
        $src = Admincp::url($path);
        self::$IS_FRAME = true;
        $historyPath = Session::get('main-path');
        if ($historyPath && $historyPath != $path) {
            $app = Session::get('main-app');
            $navData = Menu::getNav($app, $historyPath);
            $title = $navData[1]['name'];
            $historyUrl = Admincp::url($historyPath);
            $_md5Hash = md5($historyUrl);
            $historyTabId = $_md5Hash.'-tab';
            $historyTabPaneId = $_md5Hash.'-pane';
            $historyIframeId = $_md5Hash.'-iframe';
        }
        Session::set('main-src', '');
        Session::set('main-path', '');
        include self::view('base/tabs-iframe', 'admincp');
    }

    public function do_index0()
    {
        // $frame = Config::get('admincp.frame');
        // //框架模式
        // if ($frame) {
        //     $src = Session::get('main-src');
        //     empty($src) && $src = 'admincp/dashboard';
        //     $src = Admincp::url($src . '#main');
        //     Session::set('main-src', '');
        //     include self::view('base/index', 'admincp');
        // } else {
        //     self::$IS_FRAME = true;
        //     include self::view('dashboard', 'admincp');
        // }
    }

    /**
     * 基于注解的自动权限方案
     * @auth true
     * @login true
     * @role superadmin
     * @permission admincp:test,test:phpinfo
     * @run true
     * @return void
     */
    public function do_test()
    {
        // * @auth true|false 是否需要鉴权
        // * @login true|false 是否需要登录
        // * @role superadmin 验证角色权限
        // * @permission admincp:test,test:phpinfo 
        // * @run true|false 是否需要运行
        var_dump(APP_DOURL);
    }
    /**
     * phpinfo
     * @role superadmin
     */
    public function do_phpinfo()
    {
        phpinfo();
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
            'iCMS_VERSION'      => iCMS_VERSION,
            'iCMS_RELEASE'      => iCMS_RELEASE
        ];
    }

    /**
     * 获取应用插件 异步执行
     *
     * /app/[*]/assets/js/hooks/[$app_name]/[$do]*.js
     *
     * @return Array
     */
    public function done_hooks()
    {
        $post = Request::post();
        $name = $post['name'];
        $do = $post['do'];
        $path = 'js/hooks/' . $name . '/' . $do . '*';
        return [self::jsScan($path, '*')];
    }

    /**
     * 获取后台异步执行插件
     *
     * /app/[*]/assets/js/plugin.home.foot.*.js
     *
     * @return Array
     */
    public function done_plugin()
    {
        return [self::jsScan('js/plugin.home.foot*', '*')];
    }

    public static function systemInfo()
    {
        include self::view('info', 'admincp');
    }

    // 检测函数支持
    public function isfun($fun = '')
    {
        if (!$fun || trim($fun) == '' || preg_match('~[^a-z0-9\_]+~i', $fun)) {
            return 'Error';
        }
        return self::isCheck((false !== @function_exists($fun)));
    }

    //检测PHP设置参数
    public function check($varName)
    {
        switch ($result = get_cfg_var($varName)) {
            case 0:
                return self::isCheck(0);
                break;
            case 1:
                return self::isCheck(1);
                break;
            default:
                return $result;
                break;
        }
    }

    public static function isCheck($is)
    {
        return $is ?
            '<font color="green"><i class="fa fa-fw fa-check"></i></font>' :
            '<font color="red"><i class="fa fa-fw fa-times"></i></font>';
    }
}
