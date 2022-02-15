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
    public function __construct($fid = null)
    {
        parent::__construct();
        $this->id = (int) $_GET['id'];
        $fid === null && $fid = Request::param('fid');
        $this->fid = (int) $fid;
        $this->fid && Forms::init($this->fid);

        AppsPackage::$LocalFormat = FormsPackage::$LocalFormat;
        AppsPackage::$LocalDataFile  = FormsPackage::$LocalDataFile;
        AppsPackage::$LocalTableFile = FormsPackage::$LocalTableFile;
    }
    /**
     * [添加表单内容]
     * @return [type] [description]
     */
    public function do_submit()
    {
        if ($this->fid) {
            $rs = Forms::data($this->id);
            FormerApp::add(Forms::$DATA, $rs);
        }
        include self::view('forms.submit');
    }
    /**
     * [保存表单数据]
     * @return [type] [description]
     */
    public function do_save_data()
    {
        $data = FormerApp::save(Forms::$DATA, null, $update);
        $pk = AppsTable::getPrimaryKey();
        $id = $data[$pk];
        AppsMeta::save(self::$appId, $id);
        // Archive::save(self::$appId, $id,$data, Forms::$DATA);
        iPHP::callback(array("Spider", "callback"), array($this, $id));

        $REFERER_URL = $_POST['REFERER'];
        if (empty($REFERER_URL) || strstr($REFERER_URL, '=form_save')) {
            $REFERER_URL = APP_URL . '&do=data&fid=' . $this->fid;
        }

        return $data;
    }
    /**
     * [表单数据查看]
     * @param  string $stype [description]
     * @return [type]        [description]
     */
    public function do_data($stype = 'normal')
    {
        if ($this->fid) {
            Forms::$DATA['fields'] && $fields = Former::fields(Forms::$DATA['fields']);
            $keywords = Request::get('keywords');
            $sfield = Request::get('sfield');
            $pattern = Request::get('pattern');

            if ($keywords) {
                $search = array();
                if (empty($sfield)) {
                    foreach ((array) $fields as $fi => $field) {
                        $field['field'] == 'VARCHAR' && $search[] = $field['id'];
                    }
                    $search && $where["CONCAT(`" . implode('`,`', $search) . "`)"] = array('REGEXP', $keywords);
                } else {
                    empty($pattern) && $pattern = 'REGEXP';
                    $where[$sfield] = array($pattern, $keywords);
                }
            } else {
                $pattern && $where[$sfield] = array($pattern, $keywords);
            }

            $orderby = self::setOrderBy(array(
                Forms::$primaryKey => strtoupper(Forms::$primaryKey),
            ));
            $orderby = self::setOrderBy();

            $result = ContentModel::where($where)
                ->orderBy($orderby)
                ->paging();
        }
        AdmincpBatch::$config['sdo'] = 'data';
        include self::view('forms.data');
    }
    /**
     * [删除表单数据]
     * @param  [type]  $id     [description]
     * @param  boolean $dialog [description]
     * @return [type]          [description]
     */
    public function do_delete_data($id = null)
    {
        $id === null && $id = $this->id;
        $id or self::alert("请选择要删除的" . Forms::$DATA['name'] . "数据");
        Forms::deleteData($id);
        // $dialog && self::success(Forms::$DATA['name'] . "数据删除完成");
    }

    /**
     * [创建表单]
     * @return [type] [description]
     */
    public function do_add()
    {
        $this->id && $rs = Forms::get($this->id);
        if (empty($rs)) {
            $rs['type']   = "1";
            $rs['username'] = Member::$nickname;
            $rs['status'] = "1";
            $rs['fields'] = Etc::get('forms', 'default/field');
            $base_fields  = $rs['fields'];
            $rs['config']['enable'] = "1";
        }
        $rs['node_id'] = $rs['node_id'] ?: (int) $_GET['node_id'];

        $formerArray = $rs['fields'];
        //兼容旧v7
        AppsHelper::compatibleV7($formerArray);

        empty($rs['tpl']) && $rs['tpl'] = sprintf('%s/forms.htm', View::TPL_FLAG_1);
        AppsTable::$baseFieldsKeys = array('id');
        AppsMeta::get(self::$appId, $rs['id']);
        include self::view("add");
    }
    /**
     * [保存表单]
     * @return [type] [description]
     */
    public function save()
    {
        $data = FormsModel::postData();
        $data['app'] = Request::post('_app');
        $create  = (int) Request::post('create') ? true : false;
        $data['name'] or self::alert('表单名称不能为空');

        empty($data['app']) && $data['app'] = Pinyin::get($data['name']);
        strpos($data['app'], '..') !== false && self::alert('非法表单标识');
        empty($data['title']) && $data['title'] = $data['name'];
        preg_match("/[a-zA-Z0-9_\-]/", $data['app']) or self::alert('表单标识字段名只能由英文字母、数字或_-组成,不支持中文');

        AppsHelper::transFields($data['fields'], function ($arr) {
            preg_match("/[a-zA-Z0-9_\-]/", $arr['name']) or self::alert('[' . $arr['label'] . '] 字段名只能由英文字母、数字或_-组成,不支持中文');
            $arr['label'] or self::alert('发现自定义字段中空字段名称');
            // empty($arr['comment']) && $arr['comment'] = $arr['label'];
            // $arr['name'] or self::alert('发现自定义字段中有空字段名');
        });

        AppsHelper::parseFields($data['fields'], $masterFields, $dataFields);

        FilesPic::values($data);

        FormsModel::check(['app' => $data['app']], $data['id']) && self::alert('该表单已经存在');

        $tableName = Forms::getTableName($data['app']);
        $dataTableName = AppsTable::getDataTableName($tableName);
        $tableArray = (array)Request::post('table');
        $data['pubdate'] = strtotime($data['pubdate']);
        $data['userid'] = Member::$user_id;

        if (empty($data['id'])) {
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
            $data['table']  = $tableArray;
            $data['id'] = FormsModel::create($data);
        } else {
            $row = FormsModel::get($data['id']);
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
            $data['table']  = $tableArray;
            FormsModel::update($data, $data['id']);
        }
        AppsMeta::save(self::$appId, $data['id']);
        // self::success('保存成功');
    }

    public function do_update()
    {
        parent::do_update();
        $this->autoCache();
    }
    public function do_manage()
    {
        $keywords = Request::get('keywords');
        $keywords && $where['CONCAT(app,name,title,description)'] = array('REGEXP', $keywords);
        $orderby = self::setOrderBy();
        $result = FormsModel::where($where)
            ->orderBy($orderby)
            ->paging();

        AdmincpBatch::$config['sdo'] = 'manage';
        include self::view("forms.manage");
    }
    public function do_batch()
    {
        $actions = array(
            'data-dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    $this->do_delete($id);
                }
                return true;
            },
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    $this->do_delete($id);
                }
                return true;
            }
        );
        return AdmincpBatch::run($actions, "表单");
    }

    /**
     * [删除表单]
     * @return [type] [description]
     */
    public function do_delete($id = null)
    {
        $id === null && $id = $this->id;
        $id or self::alert('请选择要删除的表单');
        $forms = Forms::get($id);
        Forms::delete($this->id);
        // $dialog && self::success("表单已经删除");
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
        @set_time_limit(0);
        $rs = FormsModel::select();
        if ($rs) foreach ($rs as $a) {
            $a = Apps::item($a);
            $appid_array[$a['id']] = $a;
            $app_array[$a['app']]  = $a;
            Cache::set('forms/' . $a['id'], $a, 0);
        }
        Cache::set('forms/idarray',  $appid_array, 0);
        Cache::set('forms/array', $app_array, 0);
    }


    /**
     * [本地安装表单]
     * @return [type] [description]
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
     * [打包下载表单]
     * @return [type] [description]
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
        $data_file  && File::rm($data_file);
        $table_file && File::rm($table_file);
        File::exist($packTmpDir) && File::rmdir($packTmpDir);
    }
    public function select()
    {
        $rs = FormsModel::where('status', '1')->select();
        $option = '';
        if ($rs) foreach ($rs as $key => $value) {
            $option .= sprintf(
                '<option value="%d">%s/%s</option>',
                $value['id'],
                $value['app'],
                $value['name']
            );
        }
        return $option;
    }
}
