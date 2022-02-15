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

abstract class AdmincpBase extends AdmincpView
{
    public $config      = array();
    public $SPIDER      = array();
    public $ACCESC_BASE = array(
        'ADD'    => '新增',
        'MANAGE' => '管理',
        'EDIT'   => '编辑',
        'DELETE' => '删除',
    );
    public static $appConfig  = array();
    public static $appId      = 0;
    public static $primaryKey = 'id';
    public static $orderBySql = null;
    public static $orderBy    = array();
    public static $POST       = array();
    public static $GET        = array();
    public static $BATCH      = array();
    public static $MODEL      = null;
    public static $TABLE      = null;
    public static $CALLBACK   = array();
    public static $EXTENDS    = array();

    public function __construct($appid = null)
    {
        self::$appId     = $appid ?: Admincp::$APPID;
        self::$appConfig = Config::get(Admincp::$APP);
        self::$POST      = Request::post();
        self::$GET       = Request::get();

        self::modelInit();

        $this->id        = (int)Request::get('id');
        $this->config    = self::$appConfig;
        //test.add.aa.html
        self::$EXTENDS   = Config::scan(Admincp::$APP_NAME . '.' . Admincp::$APP_DO, Admincp::$APP, false);
    }
    public static function modelInit($name = null)
    {
        //self::$TABLE = 'testTable'
        if (self::$TABLE) {
            self::$MODEL = DB::table(self::$TABLE);
            return;
        }
        //self::$MODEL = new testModel
        if (is_object(self::$MODEL)) {
            return;
        }
        if (empty($name)) {
            $name = get_called_class();
            $name = substr($name, 0, -7);
        }
        $class = sprintf("%sModel", $name);

        //self::$MODEL = 'testModel'
        if (self::$MODEL && is_string(self::$MODEL)) {
            $class = self::$MODEL;
        }
        if (class_exists($class)) {
            self::$MODEL = new $class;
        }
    }
    /**
     * [{title}默认页]
     *
     * @access false
     */
    public function do_iCMS()
    {
        $args = func_get_args();
        if (method_exists($this, 'do_index')) {
            $method = 'do_index';
        } elseif (method_exists($this, 'do_manage')) {
            $method = 'do_manage';
        }
        return call_user_func_array(array($this, $method), $args);
    }
    //do_add 前置方法
    public function __do_add()
    {
        if (Request::isPost()) {
            Admincp::$METHOD_RUN = false;
            return $this->save();
        }
    }
    //do_add 后置方法
    public function do_add__()
    {
    }
    public function __do_edit()
    {
        return $this->__do_add();
    }
    public function save()
    {
        $data = self::$MODEL->postData();
        if (empty($data['id'])) {
            self::$MODEL->create($data, true);
            // $msg = "添加完成!";
        } else {
            self::$MODEL->update($data, $data['id']);
            // $msg = "编辑完成!";
        }
    }
    public function save_config()
    {
        Config::$data = Request::post('config');
        $appid = self::$appId;
        if ($GLOBALS['CONFIG_VAPPID']) {
            $GLOBALS['CONFIG_APPID'] = $appid;
            $appid = Config::VAPPID;
        }
        Config::save($appid);
    }

    //应用编辑内容时回调
    public static function added($that, $method, &$data)
    {
        // $class = get_class($that);
        $appId = self::$appId;
        $id    = $data[self::$primaryKey];
        $data['cid'] && Node::getAppMeta($data['cid']);
        AppsMeta::get($appId, $id);
        $apps = Apps::get($appId);
        if ($apps['fields']) {
            Content::model($apps);
            if (ContentDataModel::$unionKey) {
                $where[ContentDataModel::$unionKey] = $id;
                $_data = ContentDataModel::getData($where);
                is_array($_data) && $data = array_merge($data, $_data);
            }
            FormerApp::add($apps, $data, true);
        }
    }
    //应用保存内容时回调
    public static function saved($that, $method, $data)
    {
        $class = get_class($that);
        $appId = self::$appId;
        $id    = $data[self::$primaryKey];
        AppsMeta::save($appId, $id);

        $apps = Apps::get($appId);
        if ($apps['fields']) {
            Content::model($apps);
            FormerApp::save($apps, $id);
        }
        //Spider::callback
        iPHP::callback(array("Spider", "callback"), array($that, $id));
    }


    public static function error($msg, $state = 'error')
    {
        return Admincp::exception($msg, $state);
    }
    /**
     * [alert description]
     *
     * @return iJson::error
     */
    public static function alert($msg)
    {
        return Admincp::exception($msg, 'alert');
    }
    /**
     * [success description]
     *
     * @return iJson::success
     */
    public static function success()
    {
        $args = func_get_args();
        if (Request::param('frame') || Request::post() || Request::file()) {
            iJson::$jsonp = 'AdmSuccess';
            Request::param('modal') && iJson::$jsonp = 'ModalSuccess';
        }

        Request::isAjax() && iJson::$jsonp = false;

        DB::commit();
        return call_user_func_array(array('iJson', 'success'), $args);
    }
    public static function orderByOption($array, $by = "DESC")
    {
        $opt = '';
        $byText = ($by == "ASC" ? "升序" : "降序");
        foreach ($array as $key => $value) {
            $opt .= '<option value="' . $key . ' ' . $by . '">' . $value . ' [' . $byText . ']</option>';
        }
        return $opt;
    }
    public static function setOrderBy($array = null)
    {
        empty($array) && $array = array('id' => "ID");

        list($order, $by) = explode(' ', $_GET['orderby']);

        if ($by != 'DESC' && $by != 'ASC') {
            $by = 'DESC';
        }

        $default = array_keys($array);
        self::$orderBySql = isset($array[$order]) ?
            (' `' . $order . '` ' . $by) :
            $default[0] . " DESC";

        self::$orderBy = array(
            'DESC' => self::orderByOption($array, "DESC"),
            'ASC'  => self::orderByOption($array, "ASC")
        );
        return self::$orderBySql;
    }
    public static function formSubmit()
    {
        include self::view("widget/form.submit", 'admincp');
    }
    public static function formFoot()
    {
        include self::view("widget/form.footer", 'admincp');
    }
    public static function appsExtends($data = null)
    {
        include self::view("widget/extends", "apps");
    }
    public static function actionBtns($data, $node, $app = null)
    {
        is_null($app) && $app = Admincp::$APP_NAME;
        $className = get_called_class();
        //app/*/test.manage.action*.json
        $pattern = sprintf("admincp/{%s,all}.manage.action*", $app);
        if ($className == 'ContentAdmincp') {
            $pattern = sprintf("admincp/{%s,content,all}.manage.action*", $app);
        }
        // var_dump($pattern);
        if (!$actions = $GLOBALS[$pattern]) {
            /**
             * admincp/{%s,all}.manage.action*
             * admincp/{%s,content,all}.manage.action*
             */
            $actions = (array)Etc::many('*', $pattern, true);
            if ($actions) {
                foreach ($actions as $key => $value) {
                    $attr = [];
                    if ($value['icon']) {
                        if (strpos($value['icon'], ' ') === false) {
                            $value['icon'] = 'fa fa-' . $value['icon'];
                        }
                        $value['icon'] .= ' fa-fw';
                        $value['icon'] = sprintf('<i class="%s"></i>', $value['icon']);
                    }
                    if ($value['data-toggle']) {
                        $attr[] = sprintf('data-toggle="%s"', $value['data-toggle']);
                    }
                    if ($attr) {
                        $value['attr'] = implode(' ', $attr);
                    }
                    $actions[$key] = $value;
                }
            }
            $GLOBALS[$pattern] = $actions;
        }
        if ($actions) {
            $json = json_encode($actions);

            $json = preg_replace_callback("/\{\\\$(v|c|s)\.(.*?)\}/", function ($item) use ($data) {
                if ($item[1] == 'c') {
                    return constant($item[2]);
                } elseif ($item[1] == 's') {
                    // var_dump($item,self::$appId);
                    $class = get_called_class();
                    return $class::${$item[2]};
                } elseif ($item[1] == 'v') {
                    return $data[$item[2]];
                }
            }, $json);
            $actions = json_decode($json, true);
        }
        include self::view("widget/manage.action", 'admincp');
    }
    /**
     * 不存在静态方法，调用 Admincp 同名方法
     *
     * @param String $method
     * @param Array $params
     * @return void
     */
    // public static function __callStatic($method, $params)
    // {
    //     return  Admincp::proxy($method, $params);
    // }
}
