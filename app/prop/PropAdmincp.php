<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PropAdmincp extends AdmincpCommon
{
    public static $app   = null;
    public static $field = null;
    public static $default = array();
    public function __construct()
    {
        parent::__construct();
        $this->id = (int) $_GET['id'];
    }

    public function do_update(){
        parent::do_update();
        Prop::cache();
    }
    public function do_addtest()
    {
        // $this->id = 1;
        $this->id && $rs = PropModel::get($this->id);
        if ($_GET['act'] == "copy") {
            $this->id = 0;
            $rs['val'] = '';
        }
        if (empty($rs)) {
            $rs['status']  = 1;
            $rs['app']  = Request::get('_app', '');
            $rs['field'] = Request::get('field', '');
        }
        include self::view("test");
    }
    public function do_savetest()
    {
        $data = PropModel::where(['app' => $_POST['sapp'], 'field' => $_POST['field']])->select();
        // echo DB::getQueryLog();
        // self::success($data, '保存成功');
    }
    public function do_add()
    {
        // $this->id = 1;
        $this->id && $rs = PropModel::get($this->id);
        if ($_GET['act'] == "copy") {
            $this->id = 0;
            $rs['val'] = '';
        }
        if (empty($rs)) {
            $rs['status']  = 1;
            $rs['app']  = Request::get('_app', '');
            $rs['field'] = Request::get('field', '');
        }
        include self::view("prop.add");
    }

    public function save()
    {
        $data = PropModel::postData();
        $data['app'] = Request::post('sapp');
        unset($data['sapp']);

        empty($data['field']) && self::alert('属性字段不能为空');
        empty($data['name']) && self::alert('属性名称不能为空');
        // $app OR self::alert('所属应用不能为空');

        if ($data['id']) {
            if ($data['field'] == 'prop_id' || $data['field'] == 'pid') {
                is_numeric($data['val']) or self::alert($data['field'] . '字段的值只能用数字');
                $data['val'] = (int) $data['val'];
            }
            PropModel::update($data, $data['id']);
        } else {
            $nameArray = explode("\n", $data['name']);
            foreach ($nameArray as $nkey => $name) {
                if (empty($name)) continue;

                if (strpos($name, ':') !== false) {
                    list($data['name'], $data['val']) = explode(':', trim($name));
                    empty($data['val']) && $data['val'] = $nkey + 1;
                } else {
                    $data['name'] = trim($name);
                    empty($data['val']) && $data['val'] = $data['name'];
                }

                $data['sortnum'] = $nkey;
                $result[$nkey] = $data;
                $result[$nkey]['id'] = Prop::create($data);
            }
            $data = $result;
        }
        Prop::cache();
        // self::success($data, '保存成功');
    }

    public function do_delete($id = null)
    {
        $id === null && $id = $this->id;
        $id or self::alert('请选择要删除的属性');
        Prop::delete($id);
        Prop::cache();
        // $dialog && self::success("属性删除完成");
    }
    public function do_batch()
    {
        $stype = AdmincpBatch::$config['stype'];
        $actions = array(
            'refresh' => function ($idArray, $ids, $batch) {
                Prop::cache();
            },
            'status' => function ($idArray, $ids, $batch) {
                PropModel::update(
                    ['status' => Request::post('mstatus')],
                    $idArray
                );
            },
            'dels' => function ($idArray, $ids, $batch) {
                Prop::delete($idArray);
                Prop::cache();
            },
            'default' => function ($idArray, $ids, $batch, $data = null) {
                $data === null && $data = Request::args($batch);
                $data && PropModel::update($data, $idArray);
            },
        );
        return AdmincpBatch::run($actions, "属性");
    }

    public function do_manage()
    {
        $field = Request::get('field');
        $field && $where['field'] = $field;
        $sapp = Request::get('sapp');
        $sapp && $where['app'] = $sapp;
        $cid = Request::get('cid');
        $cid && $where['cid'] = $cid;

        $orderby = self::setOrderBy();
        $result = PropModel::where($where)
            ->orderBy($orderby)
            ->paging();

        include self::view("prop.manage");
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
        Prop::cache();
    }
    public static function widget_count()
    {
        $total = PropModel::count();
        $widget[] = array($total, '全部');
        foreach (Prop::$statusMap as $status => $text) {
            $count = PropModel::where('status', $status)->count();
            $widget[] = array($count, $text);
        }
        return $widget;
    }
}
