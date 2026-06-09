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
 * 后台基础功能
 */
abstract class AdmincpBase extends AdmincpView
{
    public static $orderBy = [
        'id' => 'ID',
    ];

    public $id = 0;
    public $ids = [];
    public $ACCESC_BASE = [
        'ADD'    => '新增',
        'INDEX'  => '管理',
        'EDIT'   => '编辑',
        'DELETE' => '删除',
    ];
    public $RESULT = [];
    public static $ID = 0;
    public static $APPID = 0;
    public static $CONFIG = [];
    public static $POST = [];
    public static $MODEL = null;
    public static $CLASS = null;
    public static $TABLE = null;
    public static $SDOMAP = [
        'inbox'   => '0', //草稿
        'normal'  => '1', //正常
        'trash'   => '2', //回收站
        'examine' => '3', //待审核
        'off'     => '4', //未通过
        'delete'  => '5', //删除
        'lock'    => '6', //锁定
        'hidden'  => '7', //隐藏
    ];

    public function __construct($appid = 0)
    {
        parent::__construct();
        self::$APPID = $appid ?: Admincp::$APP_ID;
        self::$CONFIG = Config::get(Admincp::$APP);

        $this->id = (int)Request::param('id');
        $this->ids = Request::param('ids');
        self::$ID = &$this->id;
        static::init();
        static::modelInit();
        ///views/$do.*.html
        ///views/add.aa.html
        self::$EXTENDS = self::getExtends(Admincp::$APP_DO, Admincp::$APP, false);
        $EXTENDS = self::getExtends(sprintf('app/%s/%s', Admincp::$APP, Admincp::$APP_DO),'*', false);
        self::$EXTENDS = array_merge(self::$EXTENDS, $EXTENDS);
    }

    public function __destruct()
    {
        Admincp::destruct();
    }

    public static function init()
    {
    }

    public function appid()
    {
        return self::$APPID;
        // var_dump(new self());
        // return constant(sprintf("iCMS_APP_"));
    }

    public function getModel()
    {
        self::$MODEL or self::alert('模型初化出错');
        return self::$MODEL;  // 使用 Base 类的 $MODEL
    }

    public function getPost()
    {
        return self::$POST;
    }

    /**
     * 自动获取接入应用类名，模型名
     *
     * @param string $name
     * @return void
     */
    public static function modelInit($name = null)
    {
        if (is_object(self::$MODEL)) {
            return;
        }
        if (empty(self::$CLASS)) {
            $called = get_called_class();
            self::$CLASS = substr($called, 0, -7);
        }

        //self::$TABLE = 'testTable'
        self::$TABLE && self::$MODEL = DB::table(self::$TABLE);
        //self::$MODEL = new testModel
        if (is_object(self::$MODEL)) {
            return;
        }
        //self::$MODEL = 'testModel'
        if (self::$MODEL && is_string(self::$MODEL)) {
            $modelClass = self::$MODEL;
        } else {
            if (empty($name)) {
                $name = get_called_class();
                $name = substr($name, 0, -7);
            }
            $modelClass = sprintf('%sModel', $name);
        }

        if ($modelClass && class_exists($modelClass)) {
            self::$MODEL = $modelClass;
            // self::$MODEL = new $modelClass;
        }
        if (static::$MODEL && empty(self::$MODEL)) {
            self::$MODEL = static::$MODEL;
        }
    }

    /**
     * 警告提示
     *
     * @param string $msg
     * @return void
     */
    public static function alert($msg, $code = -1)
    {
        Application::alert($msg, $code);
    }

    /**
     * 提示
     *
     * @param string $msg
     * @return void
     */
    public static function success($msg, $code = 1)
    {
        Application::error($msg, $code, 'success');
    }

    public static function title()
    {
        echo Lang::get(self::$ID ? 'Edit' : 'Add', Admincp::$APP_NAME);
    }

    // public static function catch($msg, $code = 0)
    // {
    //     Application::alert($msg, $code);
    // }
    public static function getEtcs($mod = 'batch', $app = null, $method = null)
    {
        $patterns = [];
        empty($app) && $app = Admincp::$APP_NAME;
        empty($method) && $method = Admincp::$APP_DO ?: 'index';
        //app/admincp/etc/admincp/default.index.batch*.json
        $pattern = sprintf('admincp/default.%s.%s*', $method, $mod);
        $patterns[] = $pattern;
        // var_dump($pattern);
        $many0 = (array)Etc::many('admincp', $pattern, true);
        //app/*/etc/admincp/test.index.batch*.json
        $pattern = sprintf('admincp/%s.%s.%s*', ucfirst($app), $method, $mod);
        $patterns[] = $pattern;
        // var_dump($pattern);
        $many1 = (array)Etc::many('*', $pattern, true);
        $many2 = [];
        $parent = get_parent_class(Admincp::$APP_CLASS);
        if ($parent == 'NodeAdmincp') {
            $pattern = sprintf('admincp/Node.%s.%s*', $method, $mod);
            $patterns[] = $pattern;
            //admincp/node.%s.batch*
            $many2 = (array)Etc::many('*', $pattern, true);
        }
        // var_dump($patterns);
        // var_dump($many0, $many1, $many2);
        // var_dump(iDebug::$DATA['Etc::many']);
        $actions = array_replace_recursive($many0, $many1, $many2);
        sortKey($actions);
        return $actions;
    }

    /**
     * 获取应用模型所有定义的Map变量
     *
     * @param array $data
     * @return array
     */
    public static function getFieldMap(&$data = [])
    {
        $model = self::$MODEL;
        is_object($model) && $model = get_class(self::$MODEL);
        $result = [];
        if ($model) {
            $vars = get_class_vars($model);
            if ($vars) {
                foreach ($vars as $key => $value) {
                    if (stripos($key, 'Map') !== false) {
                        $field = substr($key, 0, -3);
                        $data[$key] = $value ?: [];
                        if ($arr = Prop::get($key)) {
                            $data[$key] = array_merge($data[$key], $arr);
                        }
                        $result[$field] = $data[$key];
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 获取应用的排序
     *
     * @return string
     */
    public static function getTableOrderBy()
    {
        $default = [
            'id'      => 'ID',
            'sortnum' => Lang::get('Sortnum')
        ];
        $arr = static::$orderBy;
        $default = array_merge($default, $arr);
        if ($orderby = Request::param('orderby')) {
            list($order, $by) = explode(' ', $orderby);
            if ($by != 'DESC' && $by != 'ASC') {
                $by = 'DESC';
            }
        } else {
            if ($sortName = Request::sparam('sortName')) {
                $order = $sortName;
            }
            if ($sortOrder = Request::sparam('sortOrder')) {
                $by = strtoupper($sortOrder);
            }
        }
        $keys = array_keys($default);
        if (!isset($default[$order])) {
            list($order, $by) = [$keys[0], 'DESC'];
        }
        // return sprintf('`%s` %s', $order, $by);
        return [$order=>$by];
    }

    /**
     * 获取表格数据
     *
     * @param String|Array $model 模型名称|$query
     * @param Array|Callable $query 查询条件
     * @param Callable $func 回调处理
     * @return void
     */
    public static function tableData($model, $query = [], $func = null)
    {
        //self::tableData([query])
        if (is_array($model)) {
            $func = $query;
            $query = $model;
            $model = self::$MODEL;
        }

        $param = Request::param();
        // Paging::setName('pageNumber');
        $pageSize = $param['pageSize'] ? (int)$param['pageSize'] : 10;
        // !isset($param['pageSize']) && $pageSize = 100000;
        $obj = $model;
        if (!is_object($model)) {
            $obj = new $model();
        }

        // $obj = call_user_func_array([$model, 'where'], [$query['where']]);
        $query['where'] && $obj = $obj->where($query['where']);
        $query['field'] && $obj = $obj->field($query['field']);
        $query['with'] && $obj = $obj->where($query['with']);
        $query['groupby'] && $obj = $obj->groupBy($query['groupby']);

        if ($orderByArr = self::getTableOrderBy()) {
            $obj = $obj->orderBy($orderByArr);
        }
        $query['orderby'] && $obj = $obj->orderBy($query['orderby']);
        $query['pagesize'] && $pageSize = $query['pagesize'];
        $rows = $obj->paging($pageSize);
        $sql = $obj->getQueryLog();
        $func && $rows = call_user_func_array($func, [$rows]);
        $calls = AdmincpBase::getEtcs('table');
        if ($calls) {
            foreach ($calls as $k => $c) {
                iPHP::callback($c['call'], [&$rows]);
            }
        }

        // $itemFun && $rows = array_map($itemFun, $rows);
        $slaveApps = Apps::getArray(['rootid' => self::$APPID]);

        $result = [
            'total'       => Paging::$count, //总条数
            'pageTotal'   => Paging::$total, //总页数
            'rows'        => $rows,
            'slaveApps'   => array_values($slaveApps),
            'actions'     => [],
            'query'       => $query,
            'sql'         => $sql,
            'fixedScroll' => true
        ];
        return  $result;
    }

    public static function getMapOption($field, $sid = null)
    {
        $option = '';
        $format = '<option value="%s" %s>%s[%s="%s"]</option>';
        $maps = Prop::getMap(self::$MODEL, $field);
        foreach ($maps as $key => $type) {
            $selected = $sid == $key ? 'selected' : '';
            $option .= sprintf($format, $key, $selected, $type, $field, $key);
        }
        return $option;
    }

    public static function setEditor(&$post, $row, $field1 = 'userid', $field2 = 'editor')
    {
        empty($post[$field1]) && $post[$field1] = Admin::$user_id;
        empty($post[$field2]) && $post[$field2] = Admin::$nickname;

        if (!Admin::isSuperRole()) { //非超级管理
            $post[$field1] = Admin::$user_id;
            $post[$field2] = Admin::$nickname;
            if ($row && $row[$field1] != $post[$field1]) {
                self::alert('无权进行此项操作');
            }
        }
    }

    public static function actionBtns($data, $node, $app = null)
    {
        is_null($app) && $app = Admincp::$APP_NAME;
        $className = get_called_class();
        //app/*/test.index.action*.json
        $pattern = sprintf('admincp/{%s,all}.index.action*', $app);
        if ($className == 'ContentAdmincp') {
            $pattern = sprintf('admincp/{%s,content,all}.index.action*', $app);
        }
        $key = md5($pattern);
        $actions = Cache::temp($key, function () use ($pattern) {
            /**
             * admincp/{%s,all}.index.action*
             * admincp/{%s,content,all}.index.action*
             */
            $actions = (array)Etc::many('*', $pattern, true);
            $actions && $actions = array_map(function ($value) {
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
                return $value;
            }, $actions);

            return $actions;
        });

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
        include self::view('widget/index.action', 'admincp');
    }

    /**
     * 加载静态资源
     *
     * @auth false
     * @return void
     */
    public function do_assets()
    {
        return Files::getAssets();
    }
    public function do_views()
    {
        return Files::getAssets();
    }
}
