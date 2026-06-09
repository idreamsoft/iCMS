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
    use AdmincpCommonTrait;

    public function __construct()
    {
        parent::__construct();

        Http::$CURLOPT_TIMEOUT = 60;
        Http::$CURLOPT_CONNECTTIMEOUT = 10;
    }

    public function updated(&$result)
    {
        Apps::cache();
    }

    public function do_add()
    {
        $this->id && $rs = Apps::get($this->id);

        if (empty($rs)) {
            $rs['type'] = Apps::CONTENT_TYPE;
            $rs['status'] = '1';
            $rs['create'] = '1';
            //添加时默认为自定义应用
            if ($rs['type'] == Apps::CONTENT_TYPE) {
                $rs['apptype'] = Apps::CONTENT_TYPE;
                $rs['menu'] = 'default';
                $menuArray = Etc::get('apps', 'default/menu');
                $rs['fields'] = Etc::get('apps', 'default/field');
                $base_fields = $rs['fields'];
            }
            $rootid = Request::get('rootid');
            $rootid && $rs['rootid'] = $rootid;
        } else {
            if ($rs['apptype'] == Apps::CONTENT_TYPE) {
                empty($rs['fields']) && $rs['fields'] = json_decode('{"base": {"label": "自定义","icon": "fa fa-wrench","fields": []}}', true);
            }
            $menuArray = MenuHelper::get($rs);
            empty($menuArray) && $menuArray = Etc::get('apps', 'default/menu');
        }
        $formerArray = $rs['fields'];
        $menuJson = cnjson_encode($menuArray);
        // //兼容旧v7
        // AppsHelper::compatibleV7($formerArray);

        $rs['config']['template'] = Apps::getTplTag($rs);
        $is_custom = ($rs['apptype'] && $rs['type']); //是否能自定义字段
        // var_dump($rs);
        // if($rs['table']){
        //     $tables = [];
        //     foreach ($rs['table'] as $name => $tv) {
        //         isset($tv[0]) && $tables[$name]['name'] = $tv[0];
        //         isset($tv[1]) && $tables[$name]['primary'] = $tv[1];
        //         isset($tv[2]) && $tables[$name]['union'] = $tv[2];
        //         isset($tv[3]) && $tables[$name]['label'] = $tv[3];
        //     }
        //     AppsModel::update(['table'=>$tables], $rs['id']);
        //     var_dump($tables);
        // }

        // if (empty($rs['config']['iurl'])) {
        //     $rs['config']['iurl'] = Apps::getUrlType($rs);
        // }
        // // Menu::setData('breadcrumb', array(
        // //     'name'  => ($this->id ? '修改' : '创建') . '应用',
        // //     'url'   => $_SERVER["REQUEST_URI"]
        // // ));
        include self::view();
    }

    public function save(&$data = [])
    {
        @set_time_limit(0);
        $data = AppsModel::postData();
        $id = (int) $data['id'];

        $data['name'] or self::alert('应用名称不能为空');
        empty($data['title']) && $data['title'] = $data['name'];
        empty($data['app']) && $data['app'] = Pinyin::get($data['name']);
        $data['app'] != Security::safeStr($data['app']) && self::alert('非法应用标识');
        $data['app'] = Security::safeStr($data['app']);
        preg_match("/[a-zA-Z0-9_\-]/", $data['app']) or self::alert('表单标识字段名只能由英文字母、数字或_-组成,不支持中文');

        $route = [];
        $routeArray = Request::post('route');
        if ($routeArray) {
            foreach ($routeArray as $ridx => $rv) {
                $route[$rv[0]] = [$rv[1], $rv[2]];
            }
        }

        $tableArray = [];
        if ($tArr = $data['table']) {
            if (is_array($tArr)) {
                foreach ($tArr as $key => $value) {
                    $name = AppsTable::shortname($value['name']);
                    $tableArray[$name] = $value;
                }
            }
        }

        $config = (array) $data['config'];
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
        if ($data['rootid']) {
            $column = array_column($fArray, 'type', 'id');
            $relation = array_search('relation:id', $column);
            $relation === false && self::alert('应用设置了父级应用,请添加一个"关联父应用ID"字段');
            $indexs = [
                'index_' . $relation => 'KEY `index_' . $relation . '` (`' . $relation . '`,`id`)'
            ];
        }

        AppsHelper::parseFields($fields, $masterFields, $dataFields);

        $data['addtime'] = time();
        $data['fields'] = $fields;

        AppsModel::check(['app' => $data['app']], $id) && self::alert('该应用已存在');
        $dataTableName = AppsTable::getDataTableName($data['app']);

        if (empty($id)) {
            if ($data['apptype'] == Apps::CONTENT_TYPE) {
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
                $tb['union'] = '';
                $tb['label'] = $data['name'];

                $tableArray = [];
                $tableArray[$data['app']] = $tb; //记录基本表名

                //有MEDIUMTEXT类型字段就创建xxx_cdata附加表
                if ($dataFields) {
                    $unionKey = AppsTable::getDataUnionKey($data['app']); //关联基本表id
                    $dataBaseFields = AppsTable::getDataBaseFields($data['app']); //xxx_data附加表的基础字段
                    $dataFieldArray = array_merge($dataBaseFields, $dataFields);
                    $tableArray += AppsTable::createDataTable($dataFieldArray, $dataTableName, $unionKey);
                }
                // $tableArray += AppsMeta::getTables($app);
                $data['table'] = $tableArray;
                $config['template'] = Apps::getTplTag($data, []);
                $config['iurl'] = Apps::getUrlType($data);
                $data['config'] = $config;
            } else {
                $data['fields'] = [];
                $data['table'] = [];
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
                $model = DB::table($data['app']);
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
                        'name'    => AppsTable::fullname($dataTableName),
                        'primary' => 'id',
                        'union'   => $unionKey,
                        'label'   => '附加表'
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
            $tableArray && $data['table'] = $tableArray;
            // $data['config'] = array_merge($row['config'],$config);
            AppsModel::update($data, $id);
        }

        $nodeTable = $config['nodeTable'] ? $data['app'] . '_node' : false;
        if ($nodeTable) {
            !DB::hasTable($nodeTable) && DB::copy('node', $nodeTable);
        }
        AppsHelper::makeMenu($data);
        // AppsHelper::makeLang($data);
        // Apps::menuData($data,$menu);
        Apps::cache();
        Menu::cache();
        return $data;
        // self::success($data,'保存成功');
    }

    public static function menuTree($M, $level, $child, $pid = 0, $name = 'menuData')
    {
        $name .= '[' . (int)$GLOBALS['mIdx'][$level] . ']';
        $childName = $name . '[children]';

        $children = $M['children'];
        unset($M['children']);
        empty($M['caption']) && $M['caption'] = $M['id'];
        $M['ipt_name'] = $name;
        $M['data'] = $M;
        $M['expanded'] = false;
        $M['hasChildren'] = $child;
        $M['children'] = [];
        $GLOBALS['mIdx'][$level]++;
        if ($child) {
            foreach ($children as $item) {
                $child = $item['children'] ? true : false;
                $M['children'][] = self::menuTree($item, $level + 1, $child, $item['id'], $childName);
            }
        }
        return $M;
    }

    public function do_menuTree()
    {
        $app = Apps::get($this->id);
        $menu = MenuHelper::get($app);
        empty($menu) && $menu = Etc::get('apps', 'default/menu');
        $GLOBALS['mIdx'][1] = 0;
        Menu::$callback['func'] = [__CLASS__, 'menuTree'];
        Menu::$callback['mode'] = 1;
        return Menu::callfunc($menu);
    }

    public function do_index()
    {
        $apptype = Request::get('apptype');

        if (Request::isAjax()) {
            if (is_numeric($apptype)) {
                $where['apptype'] = (int)$apptype;
            } else {
                // $where['type'] = ['<>', '0'];
            }
            is_numeric($apptype) && $where['apptype'] = (int)$apptype;

            return  self::tableData(['where' => $where], function ($rows) {
                $rows = array_map(function ($row) {
                    $row['apptype_text'] = AppsModel::$appTypeMap[$row['apptype']];
                    $row['is_content'] = $row['apptype'] == Apps::CONTENT_TYPE;
                    $row['is_pack'] = in_array($row['type'], [3, 4]);
                    $row['operate'] = [
                        'detail' => false,
                        'delete' => !in_array($row['apptype'], [0]),
                        'edit'  => !in_array($row['apptype'], [0, 1]),
                        'trash' => in_array($row['apptype'], [3, 4])
                    ];
                    return $row;
                }, $rows);
                return $rows;
            });
        }
        self::setContext([
            'tablist' => 'params',
            'params'  => [
                'appType' => $apptype,
            ]
        ]);
        include self::view();
    }

    /**
     * autoCache 在更新所有缓存时，将会自动执行
     */
    public static function autoCache()
    {
        Apps::cache();
    }

    /**
     * 卸载应用
     * @return void
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
    public function deleting($ids = null)
    {
        foreach ($ids as $key => $id) {
            $app = Apps::get($id);
            if ($app && $app['type'] && $app['apptype']) {
                Apps::uninstall($app);
            }
        }
        return true;
    }
    public function do_local()
    {
        $pkgs = AppsPackage::getLocalPkg();
        include self::view();
    }
    /**
     * 本地安装应用
     * @return void
     */

    public function done_install()
    {
        try {
            $zipfile = trim(Request::post('zipfile'));
            if ($match = AppsPackage::matchFile($zipfile)) {
                AppsStore::$PKG_PATH = AppsPackage::getLocalPath($zipfile);
                AppsStore::$DATA['application'] = $match[1];
                AppsStore::installApp();
            } else {
                Admincp::error('What the fuck!');
            }
        } catch (\FalseEx $ex) {
            $msg = $ex->getMessage();
            self::alert($msg);
        }
    }

    /**
     * 打包下载应用
     * @return void
     */
    public function do_pack()
    {
        $rs = AppsModel::get($this->id);
        empty($rs) && self::alert('应用不存在');

        File::check($rs['app']);
        $dir = Application::path($rs['app']);
        if (File::isDir($dir)) { //本地应用
            $zip_remove_path = iPHP_APP_PATH;
        } else { //自定义应用
            $packTmpDir = iPHP_APP_CACHE . '/pack.app/';
            $zip_remove_path = $packTmpDir;
            $dir = $zip_remove_path . 'app/' . $rs['app'];
            File::mkdir($dir);
        }
        AppsPackage::packDataBase($dir, $rs, $rs['table']);

        $filename = AppsPackage::getName($rs['app'], $rs['version'] ?: 'v1.0.0');
        $package = AppsPackage::createPackage($filename, $rs['app'], $dir, $zip_remove_path);
        FilesClient::attachment($package);
        File::rm($package);
        // File::rm($app_data_file);
        // File::exist($app_table_file) && File::rm($app_table_file);
        File::isDir($packTmpDir) && File::rmdir($packTmpDir);
    }

    public function do_route()
    {
        $routes = Apps::getRouting();
        include self::view();
    }

    public function done_field()
    {
        $appid = Request::post('appid');
        empty($appid) && self::alert("请先选择应用");
        $rs = AppsModel::get($appid);
        $list = [];
        if ($rs['table']) {
            $master = $rs['table'][$rs['app']];
            $tbn = $master['name'];
            if (DB::hasTable($tbn)) {
                $fullFields = AppsTable::fullFields($tbn);
                foreach ((array) $fullFields as $field => $value) {
                    $list[] = iJson::listItem($field, $field, $field . ':' . $value['comment']);
                }
            }
        }
        $param = Request::param();
        $pageSize = $param['pageSize'] ? (int)$param['pageSize'] : 10;
        $offset = ($param['pageNumber'] - 1) * $pageSize;
        $result = array_slice($list, $offset, $pageSize);
        $totalRow = count($list);
        $totalPage = ceil($totalRow / $pageSize);
        return iJson::listData($result, $pageSize, $param['pageNumber'], $totalRow, $totalPage);
    }

    public function selectpage()
    {
        return [
            'item' => function ($value, $param) {
                $text = $value['name'] . ':' . $value['app'] . "[appid='" . $value['id'] . "']";
                return iJson::listItem($value['id'], $value['name'], $text, ['app' => $value['app'], 'title' => $value['title']]);
            },
            'list' => function (&$list, $param) {
                if (Request::sget('sdo') == 'root') {
                    $head = ['id' => '0', 'name' => '顶级应用', 'text' => '======顶级应用======', 'app' => ''];
                    array_unshift($list, $head);
                }
            },
        ];
    }

    // public function done_mapper()
    // {
    //     $arr = AppsModel::select();
    //     $ret = array_column($arr, 'name', 'id');
    //     return $ret;
    // }
    public function do_menu_source()
    {
        return Etc::get('apps', 'default/menu');
        // self::success($data);
    }

    /**
     * Undocumented function
     *
     * @login no
     * @return void
     */
    public function done_accessClass()
    {
        $input = Request::input();
        $name = $input['name'];
        $app = strtolower($name);
        $apps = Apps::get($app, 'app');
        $pattern = iPHP::getAppPath($app, '*Admincp');
        $lists = glob($pattern, GLOB_BRACE);
        $access = [];
        foreach ($lists as $key => $path) {
            preg_match('@.+/app/(\w+)/((\w+)Admincp)\.php@i', $path, $match);
            $app = $match[1];
            $class = $match[2];
            $name = $match[3];
            if ($class) {
                // $methods = get_class_methods($class);
                $doc = Helper::getDocComment($class);
                $title = $doc['title'] ? str_replace(['{app.name}'], [$apps['title']], $doc['title']) : '';

                if ($doc['auth'] === false || $doc['login'] === false || $doc['role'] === Role::SUPER_RID) {
                    continue;
                }
                if ($class == sprintf('%sAdmincp', ucfirst($app))) {
                    $title = $apps['title'];
                }
                $pieces = preg_split('/(?<=\w)(?=[A-Z])/', $name);
                $c = count($pieces);
                if ($c == 1) {
                    $title .= '应用管理';
                }
                $access[] = [
                    'title'  => $title ?: $name,
                    'class'  => $class,
                    'name'   => $name,
                    'role'   => $doc['role'],
                    'method' => []
                ];
            }
        }
        return [array_values($access)];
    }
    public static $menus = [];
    /**
     * Undocumented function
     *
     * @login no
     * @return void
     */
    public function done_accessData()
    {
        $appArray = Apps::getArray();
        $appArray = array_column($appArray, null, 'app');

        $pattern = iPHP::getAppPath('*', '*Admincp');
        $lists = glob($pattern, GLOB_BRACE);
        $access = [];
        $access2 = [];

        foreach ($lists as $key => $path) {
            preg_match('@.+/app/(\w+)/((\w+)Admincp)\.php@i', $path, $match);
            $app = $match[1];
            $class = $match[2];
            $name = $match[3];

            if ($class) {
                $apps = $appArray[$app];
                $name = lcfirst($name);
                $pieces = preg_split('/(?<=\w)(?=[A-Z])/', $name);
                $c = count($pieces);

                try {
                    $doc = Helper::getDocComment($class);
                    $title = $doc['title'] ? str_replace(['{app.name}'], [$apps['title']], $doc['title']) : '';

                    if ($doc['auth'] === false || $doc['login'] === false || $doc['role'] === Role::SUPER_RID) {
                        continue;
                    }
                    if ($class == sprintf('%sAdmincp', ucfirst($app))) {
                        $title = $apps['title'];
                    }
                    if ($c == 1) {
                        $access[$app] = [
                            'id'      => $app,
                            'app'     => $app,
                            'name'    => $name,
                            'path'    => $path,
                            'pieces'  => implode(',', $pieces),
                            'title'   => $title ?: $app
                        ];
                    }
                    // if ($c == 1) {
                    //     $title .='应用管理';
                    // }
                    $methods = AppsAccess::methods($app, $name, $class, $title);
                    $access2[$app]['classs'][$class] = [
                        'id'      => $name,
                        'title'   => $title ?: $name,
                        'class'   => $class,
                        'name'    => $name,
                        'role'    => $doc['role'],
                        'methods' => array_values($methods)
                    ];
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }
        }
        foreach ($access as $key => $value) {
            $access[$key]['classs'] = array_values($access2[$key]['classs']);
        }
        usort($access, function ($a, $b) {
            return strnatcasecmp($a['id'], $b['id']);
        });

        $menus = Menu::$DATA;
        self::recursiveSort($menus, 'sort');

        return [
            'apps' => array_values($access),
            'menus' => $menus
        ];
    }
    public static function recursiveSort(array &$array, string $sortKey)
    {
        // 先对当前层级的数组排序
        usort($array, function ($a, $b) use ($sortKey) {
            return strnatcasecmp($a[$sortKey], $b[$sortKey]);
        });

        // 遍历数组，检查是否存在嵌套数组并递归排序
        foreach ($array as &$item) {
            if (isset($item['children']) && is_array($item['children'])) {
                self::recursiveSort($item['children'], $sortKey);
            }
        }
    }
    public static function widget_count()
    {
        $total = AppsModel::count();
        $widget[] = [$total, '全部'];
        foreach (Apps::$typeMap as $type => $text) {
            $count = AppsModel::where('type', $type)->count();
            $widget[] = [$count, $text];
        }
        return $widget;
    }
}
