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

class AppsAdmincp extends AdmincpCommon
{
    public function __construct()
    {
        parent::__construct();
        $this->id = (int) Request::param('id');

        Http::$CURLOPT_TIMEOUT        = 60;
        Http::$CURLOPT_CONNECTTIMEOUT = 10;
    }

    public function do_update()
    {
        parent::do_update();
        Prop::cache();
    }
    public function do_add()
    {
        $this->id && $rs = Apps::get($this->id);
        if (empty($rs)) {
            $rs['type']   = "2";
            $rs['status'] = "1";
            $rs['create'] = "1";
            //添加时默认为自定义应用
            if ($rs['type'] == "2") {
                $rs['apptype'] = Apps::CONTENT_TYPE;
                $rs['menu']    = 'default';
                $menuArray     = Etc::get('apps', 'default/menu');
                $rs['fields']  = Etc::get('apps', 'default/field');
                $base_fields   = $rs['fields'];
            }
            $rootid = Request::get('rootid');
            $rootid && $rs['rootid'] = $rootid;
        } else {
            if ($rs['apptype'] == Apps::CONTENT_TYPE) {
                empty($rs['fields']) && $rs['fields'] = json_decode('{"base": {"label": "自定义","icon": "fa fa-wrench","fields": []}}', true);
            }
            $menuArray = MenuHelper::get($rs);
            empty($menuArray) && $menuArray = Etc::get('apps', 'default/menu');
            $rs['route'] = Etc::many($rs['app'], 'route/*', true);
        }

        $formerArray = $rs['fields'];

        //兼容旧v7
        AppsHelper::compatibleV7($formerArray);

        $rs['config']['template'] = Apps::getTplTag($rs);
        if (empty($rs['config']['iurl'])) {
            $rs['config']['iurl'] = Apps::getUrlRule($rs);
        }
        Menu::setData('breadcrumb', array(
            'name'  => ($this->id ? '修改' : '创建') . '应用',
            'url'   => $_SERVER["REQUEST_URI"]
        ));
        include self::view("apps.add");
    }

    public function save()
    {
        @set_time_limit(0);

        $id      = (int) Request::post('id');
        $rootid  = (int) Request::post('rootid');
        $name    = Request::post('name');
        $title   = Request::post('title');
        $app     = Request::post('_app');
        $menu    =  Request::post('menu');
        $apptype = (int) Request::post('apptype');
        $type    = (int) Request::post('type');
        $status  = (int) Request::post('status');

        $name or self::alert('应用名称不能为空');
        strpos($app, '..') !== false && self::alert('非法应用标识');
        empty($app) && $app = Pinyin::get($name);
        empty($title) && $title = $name;

        $route = array();
        $routeArray = Request::post('route');
        if ($routeArray) foreach ($routeArray as $ridx => $rv) {
            $route[$rv[0]] = array($rv[1], $rv[2]);
        }

        $tableArray = (array)Request::post('table');

        $config = (array) Request::post('config');
        if ($config['template']) {
            $config['template'] = explode("\n", $config['template']);
            $config['template'] = array_map('trim', $config['template']);
        }
        if ($config['iurl']) {
            $config['iurl'] = json_decode($config['iurl'], true);
        }
        $config = array_filter($config);

        $fields = (array) Request::post('fields');
        $fArray = AppsHelper::transFields($fields, function ($arr) {
            preg_match("/[a-zA-Z0-9_\-]/", $arr['name']) or self::alert('[' . $arr['label'] . '] 字段名只能由英文字母、数字或_-组成,不支持中文');
            $arr['label'] or self::alert('自定义字段中存在空字段名称');
            // empty($arr['comment']) && $arr['comment'] = $arr['label'];
            // $arr['name'] or self::alert('发现自定义字段中有空字段名');
        });
        $indexs = [];
        if ($rootid) {
            $column = array_column($fArray, 'type', 'id');
            $relation = array_search('relation:id', $column);
            $relation === false && self::alert('应用设置了父级应用,请添加一个"关联父应用ID"字段');
            $indexs = [
                'index_' . $relation => 'KEY `index_' . $relation . '` (`' . $relation . '`,`id`)'
            ];
        }

        AppsHelper::parseFields($fields, $masterFields, $dataFields);

        $addtime  = time();
        $modelFields = AppsModel::getFields();
        $data = compact($modelFields);

        AppsModel::check(compact('app'), $id) && self::alert('该应用已存在');
        $dataTableName = AppsTable::getDataTableName($data['app']);
        if (empty($id)) {
            if ($type == '2') {
                DB::hasTable($data['app']) && self::alert('[' . $data['app'] . ']表已经存在');
                if ($dataFields) {
                    DB::hasTable($dataTableName) && self::alert('[' . $dataTableName . ']表已经存在');
                }
                //创建主表
                $tb = AppsTable::create(
                    $data['app'],
                    $masterFields, //获取字段数组
                    AppsTable::getMasterIndex($indexs) //索引
                );
                array_push($tb, null, $data['name']);

                $tableArray = array();
                $tableArray[$data['app']] = $tb; //记录基本表名

                //有MEDIUMTEXT类型字段就创建xxx_cdata附加表
                if ($dataFields) {
                    $unionKey = AppsTable::getDataUnionKey($data['app']); //关联基本表id
                    $dataBaseFields = AppsTable::getDataBaseFields($data['app']); //xxx_data附加表的基础字段
                    $dataFieldArray = array_merge($dataBaseFields, $dataFields);
                    $tableArray += AppsTable::createDataTable($dataFieldArray, $dataTableName, $unionKey);
                }
                // $tableArray += AppsMeta::getTables($app);
                $data['table']  = $tableArray;
                $config['template'] = Apps::getTplTag($data, []);
                $config['iurl'] = Apps::getUrlRule($data);
                $data['config'] = $config;
                $msg = "应用创建完成!";
            } else {
                $data['fields'] = '';
                $data['table'] = '';
                $msg = "应用信息添加完成!";
            }
            unset($data['id']);
            $id = AppsModel::create($data);
        } else {
            $row = AppsModel::get($id);
            $_fields = $row['fields'];
            //兼容旧v7
            AppsHelper::compatibleV7($_fields);
            AppsHelper::parseFields($_fields, $_masterFields, $_dataFields);
            // file_put_contents(__DIR__.'/a.txt',var_export($masterFields,true));
            // file_put_contents(__DIR__.'/b.txt',var_export($_masterFields,true));
            // var_dump($masterFields, $_masterFields);
            //基本表 新旧数据计算交差集 origin 为旧字段名
            $alterArray = AppsTable::makeAlterSql($masterFields, $_masterFields, $_POST['origin']);

            if ($alterArray) {
                $model = DB::table($app);
                $fieldList = $model->getFields();
                foreach ($alterArray as $field => $sql) {
                    if (strpos($sql, 'CHANGE') !== false || strpos($sql, 'DROP COLUMN') !== false) {
                        //字段改名或者删除字段,需要旧表存在该字段,否者报错
                        if (!in_array($field, $fieldList)) { //检查当前表 字段是否存在
                            unset($alterArray[$field]);
                        }
                    }
                }
                $alterArray && AppsTable::alter($data['app'], $alterArray);
            }
            //附加表存在
            if (DB::hasTable($dataTableName)) {
                //MEDIUMTEXT类型字段 新旧数据计算交差集 origin 为旧字段名
                $dataAlterArray = AppsTable::makeAlterSql($dataFields, $_dataFields, $_POST['origin']);
                //表存在 执行alter
                $dataAlterArray && AppsTable::alter($dataTableName, $dataAlterArray);

                if (empty($tableArray[$dataTableName])) {
                    $unionKey = AppsTable::getDataUnionKey($data['app']);
                    $tableArray[$dataTableName] = [
                        $dataTableName, 'id', $unionKey, '附加表'
                    ];
                }
                //表存在 但无表结构数据 则删除表
                if (empty($dataFields)) {
                    AppsTable::drop($dataTableName);
                    unset($tableArray[$dataTableName]);
                }
            } else {
                //表不存在 但有表结构数据 则创建表
                if ($dataFields) {
                    //有MEDIUMTEXT类型字段创建xxx_cdata附加表
                    $unionKey = AppsTable::getDataUnionKey($data['app']);
                    $dataBaseFields = AppsTable::getDataBaseFields($data['app']); //xxx_cdata附加表的基础字段
                    $dataFieldArray = array_merge($dataBaseFields, $dataFields);
                    $tableArray += AppsTable::createDataTable($dataFieldArray, $dataTableName, $unionKey);
                }
            }
            $tableArray && $data['table']  = $tableArray;
            // $data['config'] = array_merge($row['config'],$config);

            AppsModel::update($data, $id);
            $msg = "应用编辑完成!";
        }
        $nodeTable = $config['nodeTable'] ? $app . '_node' : false;
        if ($nodeTable) {
            !DB::hasTable($nodeTable) && DB::copy('node', $nodeTable);
        }
        Menu::makeJson($data);
        // Apps::menuData($data,$menu);
        Apps::cache();
        Menu::cache();
        return $data;
        // self::success($data,'保存成功');
    }

    public function do_manage()
    {
        $type = Request::get('type');
        if (is_numeric($type)) {
            $where['type'] = (int)$type;
        } else {
            $where['type'] = ['<>', '0'];
        }
        is_numeric($type) && $where['type'] = (int)$type;
        $orderby = self::setOrderBy();
        $result = AppsModel::where($where)
            ->orderBy($orderby)
            ->paging(50);
        //分组
        foreach ($result as $key => $value) {
            $resultGroup[$value['type']][$key] = $value;
        }
        include self::view("apps.manage");
    }

    public function do_batch()
    {
        $actions = array();
        return AdmincpBatch::run($actions, "应用");
    }
    public function do_cache()
    {
        $this->autoCache();
    }
    /**
     * [autoCache 在更新所有缓存时，将会自动执行]
     */
    public static function autoCache()
    {
        Apps::cache();
    }
    /**
     * [卸载应用]
     * @return [type] [description]
     */
    public function do_uninstall($id = null)
    {
        $id === null && $id = $this->id;
        $app = Apps::get($id);
        if ($app && $app['type'] && $app['apptype']) {
            Apps::uninstall($app);
        }
        // self::success('应用卸载完成');
    }
    /**
     * [本地安装应用]
     * @return [type] [description]
     */
    public function do_install()
    {
        try {
            $zipfile = trim(Request::post('zipfile'));
            if ($match = AppsPackage::matchFile($zipfile)) {
                AppsStore::$PKG_PATH = AppsPackage::getLocalPath($zipfile);
                AppsStore::$DATA['application'] = $match[1];
                AppsStore::installApp();
            } else {
                self::error('What the fuck!');
            }
        } catch (FalseEx $ex) {
            $msg = $ex->getMessage();
            self::alert($msg);
        } catch (sException $ex) {
            throw $ex;
        }
    }
    /**
     * [打包下载应用]
     * @return [type] [description]
     */
    public function do_pack()
    {
        $rs = AppsModel::get($this->id);
        empty($rs) && self::alert('应用不存在');

        File::check($rs['app']);
        $dir = iAPP::path($rs['app']);
        if (File::exist($dir)) { //本地应用
            $zip_remove_path = iPHP_PATH;
        } else { //自定义应用
            $packTmpDir = iPHP_APP_CACHE . '/pack.app/';
            $zip_remove_path = $packTmpDir;
            $dir = $zip_remove_path . $rs['app'];
            File::mkdir($dir);
        }
        AppsPackage::packDataBase($dir, $rs, $rs['table']);

        $filename = AppsPackage::getName($rs['app'], $rs['config']['version']);
        $package  = AppsPackage::createPackage($filename, $rs['app'], $dir, $zip_remove_path);

        FilesClient::attachment($package);
        File::rm($package);
        // File::rm($app_data_file);
        // File::exist($app_table_file) && File::rm($app_table_file);
        File::exist($packTmpDir) && File::rmdir($packTmpDir);
    }
    /**
     * [钩子管理]
     * @return [type] [description]
     */
    public function do_hooks()
    {
        if (Request::isPost()) {
            return $this->save_hooks();
        }
        Config::app(self::$appId, 'hooks');
    }
    public function do_route()
    {
        $routes =  Apps::getRoute();
        include self::view("apps.routes");
    }

    /**
     * [保存应用字段钩子]
     * @return [type] [description]
     */
    public function save_hooks()
    {
        $hooks = array();
        $data = (array)Request::post('hooks');
        if ($data['method']) foreach ($data['method'] as $key => $method) {
            $app   = $data['app'][$key];
            $field = $data['field'][$key];
            if ($method && $app && $field) {
                $hooks['fields'][$app][$field][] = $method;
            }
        }
        Config::$data = $hooks;
        Config::save(self::$appId, 'hooks');
    }
    public function do_menu_source()
    {
        return Etc::get('apps', 'default/menu');
        // self::success($data);
    }
    public static function widget_count()
    {
        $total = AppsModel::count();
        $widget[] = array($total, '全部');
        foreach (Apps::$typeMap as $type => $text) {
            $count = AppsModel::where('type', $type)->count();
            $widget[] = array($count, $text);
        }
        return $widget;
    }
}
