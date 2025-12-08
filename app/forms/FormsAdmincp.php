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
defined('APP_URL') or define('APP_URL', 'admincp.php?app=forms');

class FormsAdmincp extends AdmincpCommon
{
    use AdmincpCommonTrait;

    public function __construct($fid = null)
    {
        parent::__construct();
        Node::$APPID = self::$APPID;

        AppsPackage::$LocalFormat = FormsPackage::$LocalFormat;
        AppsPackage::$LocalDataFile = FormsPackage::$LocalDataFile;
        AppsPackage::$LocalTableFile = FormsPackage::$LocalTableFile;
    }

    /**
     * 创建表单
     * @return void
     */
    public function do_add()
    {
        $this->id && $rs = Forms::get($this->id);
        if (empty($rs)) {
            $rs['type'] = '1';
            $rs['username'] = Admin::$nickname;
            $rs['status'] = '1';
            $rs['fields'] = Etc::get('forms', 'default/field');
            $base_fields = $rs['fields'];
            $rs['config']['enable'] = '1';
        }
        $rs['node_id'] = $rs['node_id'] ?: (int) $_GET['node_id'];

        $formerArray = $rs['fields'];
        //兼容旧v7
        // AppsHelper::compatibleV7($formerArray);

        empty($rs['tpl']) && $rs['tpl'] = View::TPL_FLAG_0.'forms.form.htm';
        AppsTable::$baseFieldsKeys = ['id'];
        include self::view();
    }

    /**
     * 保存表单
     * @return void
     */
    public function save(&$data = [])
    {
        $data = FormsModel::postData();
        $id = (int) $data['id'];

        $data['name'] or self::alert('表单名称不能为空');
        empty($data['title']) && $data['title'] = $data['name'];
        empty($data['app']) && $data['app'] = Pinyin::get($data['name']);
        $data['app'] != Security::safeStr($data['app']) && self::alert('非法表单标识');
        $data['app'] = Security::safeStr($data['app']);
        preg_match("/[a-zA-Z0-9_\-]/", $data['app']) or self::alert('表单标识字段名只能由英文字母、数字或_-组成,不支持中文');

        $tableArray = [];
        if ($tArr = $data['table']) {
            if (is_array($tArr)) {
                foreach ($tArr as $key => $value) {
                    $name = AppsTable::shortname($value['name']);
                    $tableArray[$name] = $value;
                }
            }
        }
        $fields = (array) Request::post('fields');
        $fArray = AppsHelper::transFields($fields, function ($arr) {
            preg_match("/[a-zA-Z0-9_\-]/", $arr['name']) or self::alert('[' . $arr['label'] . '] 字段名只能由英文字母、数字或_-组成,不支持中文');
            $arr['label'] or self::alert('自定义字段中存在空字段名称');
            // empty($arr['comment']) && $arr['comment'] = $arr['label'];
            // $arr['name'] or self::alert('发现自定义字段中有空字段名');
        });
        AppsHelper::parseFields($fields, $masterFields, $dataFields);


        FilesPic::values($data);

        FormsModel::check(['app' => $data['app']], $id) && self::alert('该表单已经存在');

        $tableName = Forms::getTableName($data['app']);
        $dataTableName = AppsTable::getDataTableName($tableName);
        $data['userid'] = Admin::$user_id;
        $data['fields'] = $fields;

        if (empty($id)) {
            $data['addtime'] = time();
            DB::hasTable($tableName) && self::alert('[' . $tableName . ']表已经存在');
            if ($dataFields) {
                DB::hasTable($dataTableName) && self::alert('[' . $dataTableName . ']表已经存在');
            }
            //创建基本表
            $tb = AppsTable::create(
                $tableName,
                $masterFields, //获取字段数组
                Forms::getMasterIndex() //索引
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
            $data['table'] = $tableArray;
            $data['id'] = FormsModel::create($data);
        } else {
            $row = FormsModel::get($id);
            $_fields = $row['fields'];
            //兼容旧v7
            AppsHelper::compatibleV7($_fields);
            AppsHelper::parseFields($_fields, $_masterFields, $_dataFields);
            //基本表 新旧数据计算交差集 origin 为旧字段名
            $alterArray = AppsTable::makeAlterSql($masterFields, $_masterFields, $_POST['origin']);
            if ($alterArray) {
                $model = DB::table($tableName);
                $fieldList = $model->getFields();
                foreach ($alterArray as $field => $sql) {
                    if (strpos($sql, 'CHANGE') !== false || strpos($sql, 'DROP COLUMN') !== false) {
                        //字段改名或者删除字段,需要旧表存在该字段,否者报错
                        if (!in_array($field, $fieldList)) { //检查当前表 字段是否存在
                            unset($alterArray[$field]);
                        }
                    }
                }
                $alterArray && AppsTable::alter($tableName, $alterArray);
            }
            //附加表
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
            FormsModel::update($data, $data['id']);
        }
    }

    public function updated(&$result)
    {
        $this->autoCache();
    }

    public function index()
    {
        $keywords = Request::get('keywords');
        $keywords && $where['CONCAT(app,name,title,description)'] = ['REGEXP', $keywords];
        return  self::tableData(['where' => $where], function ($rows) {
            $nodeArray = Node::many($rows, 'node_id');
            return array_map(function ($row) use ($nodeArray) {
                $node = (array)$nodeArray[$row['node_id']];
                $iurl = Route::get(Forms::APP, [$row, $node]);
                $row['url'] = $iurl->href;
                return $row;
            }, $rows);
        });
    }

    public function batching()
    {
        return [
            'data-dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    $this->done_delete($id);
                }
                return true;
            },
        ];
    }

    /**
     * 删除表单
     */
    public function deleting($ids = [])
    {
        if ($ids) {
            foreach ($ids as $key => $id) {
                Forms::delete($id);
            }
        }
        return true;
    }

    /**
     * autoCache 在更新所有缓存时，将会自动执行
     */
    public static function autoCache()
    {
        @set_time_limit(0);
        $rs = FormsModel::select();
        if ($rs) {
            foreach ($rs as $a) {
                $a = Apps::item($a);
                $appid_array[$a['id']] = $a;
                $app_array[$a['app']] = $a;
                Cache::set('forms/' . $a['id'], $a, 0);
            }
        }
        Cache::set('forms/idarray', $appid_array, 0);
        Cache::set('forms/array', $app_array, 0);
    }

    /**
     * 本地安装表单
     * @return void
     */
    public function do_install()
    {
        try {
            $zipfile = trim(Request::post('zipfile'));
            if ($match = AppsPackage::matchFile($zipfile)) {
                AppsStore::$PKG_PATH = AppsPackage::getLocalPath($zipfile);
                AppsStore::$DATA['application'] = $match[1];
                FormsPackage::install();
            } else {
                Admincp::error('What the fuck!');
            }
        } catch (FalseEx $ex) {
            $msg = $ex->getMessage();
            self::alert($msg);
        } catch (sException $ex) {
            throw $ex;
        }
    }

    /**
     * 打包下载表单
     * @return void
     */
    public function do_pack()
    {
        $rs = FormsModel::get($this->id);
        empty($rs) && self::alert('表单不存在');

        //自定义表单
        $packTmpDir = iPHP_APP_CACHE . '/pack.forms/';
        $dir = $packTmpDir . $rs['app'];
        File::mkdir($dir);

        list($data_file, $table_file) = AppsPackage::packDataBase($dir, $rs, $rs['table']);

        $filename = AppsPackage::getName($rs['app'], '1.0.0');
        $package = AppsPackage::createPackage($filename, $rs['app'], $dir, $packTmpDir);
        FilesClient::attachment($package);

        File::rm($package);
        $data_file && File::rm($data_file);
        $table_file && File::rm($table_file);
        File::isDir($packTmpDir) && File::rmdir($packTmpDir);
    }

    public static function select()
    {
        $rs = FormsModel::where('status', '1')->select();
        $option = '';
        if ($rs) {
            foreach ($rs as $key => $value) {
                $option .= sprintf(
                    '<option value="%d">%s/%s</option>',
                    $value['id'],
                    $value['app'],
                    $value['name']
                );
            }
        }
        return $option;
    }
}
