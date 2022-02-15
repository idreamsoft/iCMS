<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class AppsApp
{
    public static $app = null;
    public static $_app = null; //自定义应用
    public static $appid = 0;
    public static $primaryKey = 'id';
    public static $model   = null;
    public static $pageNum   = 1;
    public static $SETS   = null;
    public static $statusMap  = '1';
    public static $config = null;
    public static $APPDATA   = null;
    public static $node = array();

    public $methods  = array(iPHP_APP, 'clink', 'search', 'hits', 'vote', 'comment');

    /**
     * Undocumented function
     *
     * @param [type] $app
     * @param [type] $primary
     * @param OBJECT $model
     */
    public function __construct($app = null, $primary = null, $model = null)
    {
        empty($app) && self::throwError('$app is empty');

        if (is_object($model)) {
            self::$model = $model;
        } else if (is_null($model)) {
            $model = $app . 'Model';
            self::$model = new $model;
        }
        self::$app        = $app;
        self::$primaryKey = $primary ?: self::$model->getPrimaryKey();
        self::$config     = Config::get($app);

        $this->addMethod($app);
    }
    public function __destruct()
    {
        iAPP::destruct();
    }
    public function model($model)
    {
        self::$model = new $model;
    }
    public function gets()
    {
        self::$pageNum = (int)Request::get('p', 1);
        $value = (int) Request::get(self::$primaryKey);
        $field = self::$primaryKey;

        $dir = Request::get('dir');
        $dir && self::$SETS['cid'] = NodeCache::get('dir2cid', $dir);

        $cid = Request::get('cid');
        $cid && self::$SETS['cid'] = $cid;

        if (isset($_GET['clink'])) {
            $value = Request::get('clink');
            $field = 'clink';
        }
        if (isset($_GET['AUTHID'])) {
            $authID = Request::get('AUTHID');
            $value  = auth_decode($authID);
            $value or self::throwError('AUTHID decode error', 10001);
        }
        if (isset($_GET['HASHID'])) {
            $hashID  = Request::get('HASHID');
            $salt    = Request::get('salt');
            $len     = strlen($hashID);
            $Hashids = Route::Hashids($salt, $len);
            $result  = $Hashids->decode($hashID);
            $value   = $result[0];
            $value or self::throwError('HASHID decode error', 10002);
        }
        return [$value, $field];
    }
    public function do_iCMS($param = null)
    {
        is_null($param) && $param = $this->gets();
        method_exists($this, 'display') or self::throwError('Call to undefined method <b>' . get_called_class() . '->display()</b>', '1004');
        $cdn = Config::get('CDN');
        if ($cdn['enable']) {
            $expires = $cdn['expires'];
            @header("Cache-Control: " . $cdn['cache_control'] . ", max-age=" . $expires);
            @header("Pragma: " . $cdn['cache_control']);
            @header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
            @header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
        }
        return call_user_func_array([$this, 'display'], $param);
    }
    public function do_clink()
    {
        return $this->do_iCMS();
    }

    public function API_iCMS($param = null)
    {
        return $this->do_iCMS($param);
    }
    public function API_clink()
    {
        return $this->do_clink();
    }
    public function do_search()
    {
        $format = '%s/%s.search.htm';
        $app = self::$_app;
        $tpl = sprintf($format, View::TPL_FLAG_1, $app);
        if (!View::tplExist($tpl)) {
            $app = self::$app;
            $tpl = sprintf($format, View::TPL_FLAG_1, $app);
        }
        try {
            $search = new SearchApp();
            return $search->display($tpl, $app);
        } catch (ViewEx $ex) {
            $msg = $ex->getMessage();
            self::throwError($msg);
        }
    }
    public function API_search($a = null)
    {
        return $this->do_search($a);
    }
    public function API_hits($id = null)
    {
        $id === null && $id = (int) Request::get('id');
        $app = self::$_app ?: self::$app;
        $id && self::updateHits($app, $id);
    }

    public function API_comment()
    {
        $appid = (int) Request::get('appid');
        $cid = (int) Request::get('cid');
        $iid = (int) Request::get('iid');

        $format = '%s/%s.comment.htm';
        $app = self::$_app;
        $tpl = sprintf($format, View::TPL_FLAG_1, $app);
        if (!View::tplExist($tpl)) {
            $app = self::$app;
            $tpl = sprintf($format, View::TPL_FLAG_1, $app);
        }
        $param = [$iid, self::$primaryKey, $tpl];
        return call_user_func_array([$this, 'display'], $param);
    }

    public function getData($value, $field = null, $flag = true)
    {
        $field === null && $field = self::$primaryKey;
        $where = ['status' => self::$statusMap, $field => $value];
        self::$SETS['cid'] && $where['cid'] = (int) self::$SETS['cid'];

        $data = self::$model->where($where)->get();
        if ($flag === false) return $data;

        $data or self::throwError([self::$app . ":not_found", [$field, $value]], 10001);

        if ($data['url']) {
            View::$gateway == "html" && throwFalse('html mode');
            $app = self::$_app ?: self::$app;
            self::updateHits($app, $data['id']);
            Helper::redirect($data['url']);
        }
        return $data;
    }

    public function addMethod($methods)
    {
        $mArray = is_array($methods) ? $methods : explode(',', $methods);
        $this->methods = array_merge($mArray, $this->methods);
    }
    //--------------------------------------------------------------------
    public static function render($data, $tpl, $name = null, $app = null)
    {
        if ($tpl === false) return $data;

        $name === null && $name = self::$_app ?: self::$app;
        $app  === null && $app  = $name;
        $view_tpl = $data['tpl'];
        $view_tpl or $view_tpl = $data['node']['template'][$name];
        strstr($tpl, '.htm') && $view_tpl = $tpl;
        View::setGlobal($data['iurl'], 'iURL');
        if ($data['node']) {
            View::assign('APP', $data['node']['app']); //绑定的应用信息
            if ($tpl !== null) unset($data['node']['app']);
            View::assign('node', $data['node']);
            if ($tpl !== null) unset($data['node']);
        } else {
            if ($APPDATA = ContentApp::$APPDATA) {
                $APPDATA['type'] == "2" && ContentFunc::interfaced($APPDATA); //自定义应用模板信息
                View::assign('APP', $APPDATA); //绑定的应用信息
            }
        }

        if ($data['SAPP']) {
            View::assign('SAPP', $data['SAPP']); //自身应用信息
            self::$app == 'content' && View::assign('content', $data);
        }

        View::assign($name, $data);

        if ($tpl === null) return $data; //不解析模板返回原数据
        $view = View::render($view_tpl, $app);
        if ($view) return array($view, $data);
    }

    public static function getCustomData(&$data, $vars = null, $app = null)
    {
        if (is_array($data)) {
            $app === null && $app = self::$_app ?: self::$app;
            if (empty($appid) && $data['app']) {
                $app = $data['app']['app'];
            }
            $meta = (array) AppsMeta::data($app, $data['id']);
            $data = array_merge($data, $meta);
            $appData  = Apps::getData($app);
            $data['SAPP'] = Apps::getDataLite($appData);
            $appData['fields'] && FormerApp::data($data['id'], $appData, $app, $data, $vars, $data['node']);
        }
    }

    public static function hooked(&$data, $app = null)
    {
        if (is_null($app) && self::$app) {
            $app = self::$_app ?: self::$app;
        }
        //获取应用字段绑定的钩子
        if ($hooks = Config::get('hooks.fields', $app)) {
            AppsHooks::fields($app, $data, $hooks);
        }
        // //获取应用APP绑定钩子
        if ($hooks = Config::get('hooks.App', $app)) {
            // var_dump($hooks);
            $data = iPHP::callback($hooks, $data, E_USER_ERROR);
            // foreach ($hooks as $key => $value) {
            //     $data = iPHP::callback($value, $data,E_USER_ERROR);
            // }

            // AppsHooks::fields($app, $data, $hooks);
        }
        // //获取应用Admincp绑定钩子
        // if ($hooks = Config::get('hooks.Admincp', $app)) {
        //     AppsHooks::fields($app, $data, $hooks);
        // }
    }

    public static function bodyPicsPage(&$data, $picArray, $page, $total)
    {
        $imgArray = array_unique($picArray[0]);
        if (empty($imgArray)) return;

        is_array($data['page']['next']) && $nextUrl = $data['page']['next']['url'];
        foreach ($imgArray as $key => $img) {
            if (!self::$config['img_title']) {
                $img = preg_replace('@title\s*=\s*(["\']?).*?\\1\s*@is', '', $img);
                $img = preg_replace('@alt\s*=\s*(["\']?).*?\\1\s*@is', '', $img);
                $img = str_replace('<img', '<img title="' . addslashes($data['title']) . '" alt="' . addslashes($data['title']) . '"', $img);
            }
            if (self::$config['pic_center']) {
                $imgReplace[$key] = '<p class="pic_center">' . $img . '</p>';
            } else {
                $imgReplace[$key] = $img;
            }
            if (self::$config['pic_next'] && $total > 1) {
                $clicknext = '<a href="' . $nextUrl . '"><b>' . Lang::get('iCMS:clicknext') . ' (' . $page . '/' . $total . ')</b></a>';
                $clickimg = '<a href="' . $nextUrl . '" title="' . $data['title'] . '" class="img">' . $img . '</a>';
                if (self::$config['pic_center']) {
                    $imgReplace[$key] = '<p class="click2next">' . $clicknext . '</p>';
                    $imgReplace[$key] .= '<p class="pic_center">' . $clickimg . '</p>';
                } else {
                    $imgReplace[$key] = '<p>' . $clicknext . '</p>';
                    $imgReplace[$key] .= '<p>' . $clickimg . '</p>';
                }
            }
        }
        $data['body'] = str_replace($imgArray, $imgReplace, $data['body']);
    }
    public static function initialize(&$data, $tpl)
    {
        $data['node_id'] = $data['cid'];
        try {
            $node = NodeApp::node($data['cid'], false);
        } catch (\FalseEx $ex) {
            if ($tpl) {
                $msg = $ex->getMessage();
                self::throwError($msg, 10001);
            } else {
                throw $ex;
            }
        }

        $data['node'] = $node;
        $data['app']  = $node['app'];
        $appName      = $node['app']['app'];
        isset($data['appid']) or $data['appid'] = $node['app']['id'];

        $data['iurl'] = (array) Route::get($appName, array($data, $node));
        $data['url']  = $data['iurl']['href'];
        $node['status'] == 0 && throwFalse('apps initialize node status=0');

        self::isHtml($tpl, $node, $appName);

        if ($tpl && $node['mode'] == '1') {
            self::redirectToHtml($data['iurl']);
        }
        if ($node['app']['type'] == "2") { //自定义应用模板信息
            ContentFunc::interfaced($node['app']);
        }
    }
    public static function redirectToHtml($iurl)
    {
        $fp  = $iurl['path'];
        $url = $iurl['href'];

        if (
            View::$gateway == 'html'
            || empty($url)
            || stristr($url, '.php?')
            || iPHP_DEVICE != 'desktop'
        ) {
            return false;
        }

        is_file($fp) && Helper::redirect($url);
    }
    public static function isHtml($flag, $C, $key)
    {
        if (
            View::$gateway == "html"
            && $flag
            && (strstr($C['rule'][$key], '{PHP}')
                || $C['outurl']
                || $C['mode'] == "0")
        ) {
            throwFalse('Node URL mode must be HTML mode');
        }
        return false;
    }

    public static function setData($key, $value)
    {
        self::$APPDATA[$key] = $value;
    }
    public static function unData($key = null)
    {
        if ($key) {
            self::$APPDATA[$key] = null;
        } else {
            self::$APPDATA = null;
        }
    }
    public static function getUpdateHits($all = true, $hit = 1)
    {
        $timerTask = Helper::timerTask();
        if ($all === false) {
            $time  = time();
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
    public static function updateHits($table = null, $id = 1, $primary = 'id')
    {
        $model = DB::table($table);
        $model->where($primary, $id);
        $data1 = self::getUpdateHits(false, 0);
        $data1 && $model->update($data1);
        $data2 = self::getUpdateHits();
        $data2 && $model->update($data2);
    }

    public static function throwError($msg = "", $code = "")
    {
        is_array($msg) && $msg = Lang::get($msg[0], (array)$msg[1]);
        if (preg_match('/\w+:\w+/i', $msg)) {
            $msg = Lang::get($msg);
        }
        iPHP_DEBUG && iPHP::throwError($msg, $code);
        Request::status(404, $code);

        if (@constant('iPHP_URL_404')) {
            if (Request::isUrl(iPHP_URL_404)) {
                Helper::redirect(iPHP_URL_404 . '?url=' . urlencode($_SERVER['REQUEST_URL']));
            } else {
                $tpl = iPHP_APP . "://" . iPHP_URL_404;
                View::assign('error', $msg);
                if (View::tplExist($tpl)) {
                    View::display($tpl);
                } else {
                    View::display(iPHP_APP . "://404.htm");
                }
            }
        }
        exit;
    }
}
