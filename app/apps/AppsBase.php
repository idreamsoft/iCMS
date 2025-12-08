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

abstract class AppsBase
{
    public $id = 0;

    /**
     * 需要登录的方法
     * 在 UserHook::run_after >> UserHook::checkLogin 中判断是否需要登录
     * @var array
     */
    public static $LOGIN = [];
    public static $CONFIG = [];
    public static $APP = null;
    public static $APPID = 0;
    public static $MODEL = null;

    public static $PRIMARY = 'id';
    public static $pageNum = 1;
    public static $SETS = [];

    public function __construct()
    {
        $called = get_called_class();
        self::$APP = strtolower(substr($called, 0, -3));
        $apps = isset(iCMS::$config['apps']) ? iCMS::$config['apps'] : [];
        self::$APPID = isset($apps[self::$APP]) ? $apps[self::$APP] : 0;

        if (!self::$APPID) {
            self::$APP = Application::$NAME;
            self::$APPID = Application::$ID;
        }
        self::$CONFIG = Config::get(self::$APP);
        $this->id = (int)Request::sget('id');
        $this->init();
        // if (static::$LOGIN) {
        //     $do = Application::$DO;
        //     if (in_array($do, static::$LOGIN)) {
        //         iPHP::callback(['User', 'isLogin']);
        //     }
        // }
    }

    public function init()
    {
    }

    public function api_lang()
    {
        $lang = Lang::all();
        // $array3 = Admincp::$APP_SNAME ? Lang::load(Admincp::$APP, false) : [];

        // $lang = Lang::load();
        // $lang2 = Lang::load(Application::$NAME, false);
        // $lang = array_merge($lang, $lang2);
        // var_dump(Application::$NAME, $lang);

        // if(method_exists($this,'__lang')){
        //     $lang2 = (array)$this->__lang();
        // }
        // $lang = array_merge($lang, $array2, $array3, $lang);
        exit(sprintf('define(%s)', json_encode($lang)));
    }

    public function api_hits($id = null)
    {
        // View::display('/tools/tj.js');
        $id === null && $id = (int) Request::get('id');
        $id && self::updateHits(self::$APP, $id);
    }

    public function __destruct()
    {
        Application::destruct();
    }

    public function model($model)
    {
        self::$MODEL = new $model();
    }

    public function gets()
    {
        self::$pageNum = (int)Request::get('p', 1);
        $value = (int) Request::get(self::$PRIMARY);
        $field = self::$PRIMARY;

        $dir = Request::get('dir');
        $dir && self::$SETS['cid'] = NodeCache::get('dir2cid', $dir);

        $cid = Request::sget('cid');
        $cid && self::$SETS['cid'] = $cid;

        if (isset($_GET['clink'])) {
            $value = Request::get('clink');
            $field = 'clink';
        }
        if (isset($_GET['AUTHID'])) {
            $authID = Request::get('AUTHID');
            $value = auth_decode($authID);
            $value or self::alert('AUTHID decode error', 10001);
        }
        if (isset($_GET['HASHID'])) {
            $hashID = Request::get('HASHID');
            $salt = Request::get('salt');
            $len = strlen($hashID);
            $Hashids = Route::Hashids($salt, $len);
            $result = $Hashids->decode($hashID);
            $value = $result[0];
            $value or self::alert('HASHID decode error', 10002);
        }
        return [$value, $field];
    }

    public static function checkStaticUrl($url)
    {
        if ($url && stripos($url, '.php?') === false && stripos($_SERVER['REQUEST_URI'], '.php?') === false) {
            $path = parse_url($url, PHP_URL_PATH);
            if ($_SERVER['REQUEST_URI'] != $path) {
                self::alert('url error');
            }
        }
    }

    public static function initialize(&$data, $tpl)
    {
        isset($data['cid']) && $data['node_id'] = $data['cid'];

        $node = NodeApp::node($data['node_id'], false);
        $data['node'] = $node;
        $data['APP'] = $node['APP'];
        $appName = $data['APP']['app'];
        isset($data['appid']) or $data['appid'] = $data['APP']['id'];
        $data['iurl'] = (array) Route::get($appName, [$data, $node]);
        $data['url'] = $data['iurl']['href'];
        $node['status'] == 0 && iPHP::alert('apps initialize node status=0');
        self::isHtml($tpl, $node, $appName);

        if ($tpl && $node['mode'] == '1') {
            self::redirectToHtml($data['iurl']);
        }
        if ($data['APP']['apptype'] == Apps::CONTENT_TYPE) { //自定义应用模板信息
            Content::setAppData($data['APP']);
        }
    }

    public static function basicRender($app, $tpl, $rule)
    {
        $route = (array) Route::get($app, $rule);
        $rule == '{PHP}' or Route::setPageUrl($route);
        if (self::$CONFIG['mode']) {
            self::redirectToHtml($route);
        }

        return View::render($tpl, $app, $route);
    }

    /**
     * 优先查找 {iTPL}/$app.$do.htm
     * {iTPL}/self::$APP.$do.htm
     *
     * @param string $do
     * @param string $app
     * @return void
     */
    public static function baseTpl($do = null, $app = null)
    {
        is_null($do) && $do = Application::$DO;
        is_null($app) && $app = Application::$NAME;
        $app = Security::safeStr($app, '_');
        $do = Security::safeStr($do, '_');
        $format = '%s/%s.%s.htm';
        $tpl = sprintf($format, View::TPL_FLAG_1, $app, $do);
        if (!View::tplExist($tpl)) {
            $app = self::$APP;
            $tpl = sprintf($format, View::TPL_FLAG_1, $app, $do);
        }
        return [$tpl, $app];
    }

    /**
     * 基于当前应用的或者指定模板目录下的模板文件
     *
     * @param [type] $do
     * @param [type] $app
     * @return void
     */
    public static function view($do = null, $app = null)
    {
        list($tpl, $app) = self::baseTpl($do, $app);
        return View::render($tpl, $app);
    }

    public static function render($data, $tpl, $name = null, $app = null)
    {
        self::hooked($data);
        if ($tpl === false) {
            return $data;
        }
        if (isset(iCMS::$config['route']['single']) && iCMS::$config['route']['single']) {
            $sturl = $data['url'];
            $data['page']['pn'] > 1 && $sturl = $data['page']['url'];
            self::checkStaticUrl($sturl);
        }
        $name === null && $name = self::$APP;
        $app === null && $app = $name;
        $view_tpl = $data['tpl'];
        $view_tpl or $view_tpl = $data['node']['template'][$name];

        if ($data['node']) {
            $node = $data['node'];
            $node['config']['role'] && self::checkRole($data['node'], $view_tpl);
            if ($dirs = $node['template']['dirs']) {
                $dirName = $dirs[View::$template_device];
                $dirName && View::$template_dir = $dirName;
            }
            $nodeApp = isset($node['APP']) ? $node['APP'] : [];
            if ($nodeApp['app']) {
                $app = $nodeApp['app'];
                if ($name == 'content') {
                    $name = $app;
                }
                $view_tpl or $view_tpl = $node['template'][$app];
            }

            if ($tpl !== null) {
                unset($data['node']['APP']);
            }
            View::assign('APP', $nodeApp); //绑定的应用信息
            View::assign('node', $data['node']);
            if ($tpl !== null) {
                unset($data['node']);
            }
        } else {
            if ($APP_DATA = Content::$APP_DATA) {
                View::assign('APP', $APP_DATA); //绑定的应用信息
                if ($APP_DATA['apptype'] == Apps::CONTENT_TYPE) {
                    Content::setAppData($APP_DATA); //自定义应用模板信息
                }
            }
        }
        if ($data['SAPP']) {
            View::assign('SAPP', $data['SAPP']); //自身应用信息
            if ($data['SAPP']['apptype'] == Apps::CONTENT_TYPE) {
                View::assign('content', $data);
            }
        }
        Route::getUrls($data);
        $tpl && strstr($tpl, '.htm') && $view_tpl = $tpl;
        // View::generic($data['iurl'], 'iURL');
        View::assign($name, $data);

        //不解析模板返回原数据
        if ($tpl === null) {
            return $data;
        }
        return View::render($view_tpl, $app, $data['iurl']);
    }

    public static function hooked(&$data, $app = null)
    {
        if (is_null($app) && self::$APP) {
            $app = self::$APP;
        }
        //获取应用字段绑定的钩子
        if ($hooks = Config::get('hooks.fields', $app)) {
            AppsHooks::fields($app, $data, $hooks);
        }
    }

    public static function redirectToHtml($iurl)
    {
        $fp = $iurl['path'];
        $url = $iurl['href'];

        if (
            View::$gateway == 'html'
            || empty($url)
            || stristr($url, '.php?')
            || iPHP_DEVICE != 'desktop'
        ) {
            return false;
        }

        $fp && is_file($fp) && Utils::jumpto($url);
    }

    public static function isHtml($flag, $C, $key)
    {
        if (
            View::$gateway == 'html'
            && $flag
            && (strstr($C['rule'][$key], '{PHP}')
                || $C['outurl']
                || $C['mode'] == '0')
        ) {
            Application::nil('Node URL mode must be HTML mode');
        }
        return false;
    }

    /**
     * 错误提示
     * code = 1  iCMS.notify.success
     * code = 0  iCMS.notify.warning
     * code = -1  iCMS.ui.alert
     * code = -9  iCMS.ui.warning
     * code = -99  iCMS.ui.error
     * code = -999  iCMS.ui.error
     * @param  [type] $msg [description]
     * @param  [type] $code [description]
     * @throws sException
     */
    public static function alert($msg, $code = 0, $flag = null)
    {
        try {
            // if (!is_null($flag)) {
            //     return Application::nil($msg, $code);
            // }
            return Application::alert($msg, $code);
        } catch (sException $ex) {
            if ($flag === false) {
                return Application::nil($msg, $code);
            } elseif (Request::isGet()&&!Request::isAjax()) {
                $msg = $ex->getMessage();
                Utils::http404([$msg, $code], 'page.fail');
            }
            throw $ex;
        }
    }

    /**
     * 前台页面抛出错误
     * debug模式下直接显示错误
     * 非debug模式下 返回404状态码，
     * 后台设置有404页面，显示该页面
     * 404为链接，跳转到该链接
     *
     * @param string $msg
     * @param string $code
     * @param bool $flag
     * @return void
     */
    public static function fail($msg = '', $code = '', $flag = null)
    {
        if (is_array($msg)) {
            $msg = Lang::get($msg[0], $msg[1]);
        } elseif (is_string($msg)) {
            $msg = Lang::get($msg);
        }
        if (!is_null($flag)) {
            return Application::nil($msg, $code);
        }
        // iPHP_DEBUG && iPHP::error($msg, -99);
        Utils::http404([$msg, $code], 'page.fail');
    }

    //兼容旧版本
    public static function throwError($msg = '', $code = '', $flag = null)
    {
        return self::fail($msg, $code, $flag);
    }

    public static function getCustomData(&$data, $vars = null, $app = null)
    {
        if (is_array($data)) {
            $app === null && $app = self::$APP;
            if (empty($appid) && $data['APP']) {
                $app = $data['APP']['app'];
            }
            AppsMeta::data($app, $data['id'], $data);
            $appData = Apps::getData($app);
            $data['SAPP'] = Apps::getDataLite($appData);
            $appData['fields'] && FormerApp::data($data['id'], $appData, $app, $data, $vars, $data['node']);
        }
    }

    public static function getUpdateHits($all = true, $hit = 1)
    {
        $timerTask = Helper::timerTask();
        if ($all === false) {
            $time = time();
            $utime = Cache::get('update_hits_all');
            if ($time - $utime < 86400) {
                return false;
            }
            Cache::set('update_hits_all', $time, 0);
        }

        $update = [];
        $all && $update['hits'] = ['+', $hit];
        foreach ($timerTask as $key => $bool) {
            if ($key == 'yday') {
                if ($bool == 1) {
                    $update['hits_yday'] = DB::raw('hits_today');
                } elseif ($bool > 1) {
                    $update['hits_yday'] = 0;
                }
            } else {
                $update["hits_{$key}"] = [($bool ? '+' : '='), $hit];
            }
        }
        return $update;
    }

    public static function updateHits($app = null, $id = 1, $primary = 'id')
    {
        $model = Apps::model($app);
        $model = $model->where($primary, $id);
        $data1 = self::getUpdateHits(false, 0);
        $data1 && $model->update($data1);
        $data2 = self::getUpdateHits();
        $data2 && $model->update($data2);
    }

    public static function checkRole(&$node, &$tpl)
    {
        $role = $node['config']['role'] ?: [];
        if (empty($role)) {
            return;
        }
        $role_id = (int)User::$data['role_id'];
        // 将 allow 和 forbid 的字符串转换为数组
        $allowed = $role['allow'] != '' ? explode(',', $role['allow']) : [];
        $forbidden = $role['forbid'] != '' ? explode(',', $role['forbid']) : [];
        $allow_arr = $role['allow_text'] ? explode(',', $role['allow_text']) : [];
        $forbid_arr = $role['forbid_text'] ? explode(',', $role['forbid_text']) : [];

        $access = [
            'allow'  => array_combine($allowed, $allow_arr),
            'forbid' => array_combine($forbidden, $forbid_arr),
        ];
        $node['ACCESS'] = $access;
        // View::append('node', compact('access'), true);
        // 判断逻辑
        if ($forbidden && in_array($role_id, $forbidden)) {
            // self::throwError('node access denied');
            // View::render('iCMS://node.denied.htm');
            // echo '禁止访问：您的用户级别无权访问此内容。';
            $tpl = 'iCMS://node.denied.htm';
        } elseif ($allowed) {
            if (!in_array($role_id, $allowed)) {
                // self::throwError('node Unauthorized access');
                // View::render('iCMS://node.noauth.htm');
                // echo '无权访问：您的用户级别无权访问此内容。';
                $tpl = 'iCMS://node.noauth.htm';
            }
        }
    }
}
