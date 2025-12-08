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
    use AdmincpCommonTrait;
    public static $app   = null;
    public static $field = null;
    public static $default = array();

    public function updated(&$result)
    {
        Prop::cache();
    }

    public function do_add()
    {
        // $this->id = 1;
        $this->id && $rs = PropModel::get($this->id);
        if (Request::sget('act') == "copy") {
            $this->id = 0;
            $rs['val'] = '';
        }
        if (empty($rs)) {
            $rs['status']  = 1;
            $rs['appid']  = Request::get('appid', '');
            $rs['field'] = Request::get('field', '');
        }
        include self::view();
    }
    public function do_copy()
    {
        $data = PropModel::get($this->id);
        unset($data['id']);
        $data['name'] .= '副本';
        $data['val'] = 0;
        $data['sortnum']++;
        $id = PropModel::create($data);
        $url = APP_URL . '&do=edit&id=' . $id;
        Utils::jumpto($url);
    }
    public function save(&$data = [])
    {
        $data = PropModel::postData();
        empty($data['field']) && self::alert('属性字段不能为空');
        empty($data['name']) && self::alert('属性名称不能为空');

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
                $prop = $data;
                if (strpos($name, ':') !== false) {
                    list($prop['name'], $prop['val']) = explode(':', trim($name));
                    empty($prop['val']) && $prop['val'] = $nkey + 1;
                } else {
                    $prop['name'] = trim($name);
                    $prop['val'] = $prop['name'];
                }

                $prop['sortnum'] = $nkey;
                $result[$nkey] = $prop;
                $result[$nkey]['id'] = Prop::create($prop);
            }
        }
        Prop::cache();

    }
    public function batching()
    {
        return array(
            'title' => function ($ids, $data, $batch) {
                $title = $data['title'];
                PropModel::update(compact('title'), $ids);
                Prop::cache();
            },
            'fields' => function ($ids, $data, $batch) {
                $app = $data['app'];
                $field = $data['field'];
                PropModel::update(compact('app','field'), $ids);
                Prop::cache();
            }
        );
    }
    public function deleting($ids = [])
    {
        if ($ids) foreach ($ids as $key => $id) {
            Prop::delete($id);
        }
        Prop::cache();
        return true;
    }
    /**
     * 获取属性字段映射
     *
     * @auth no
     * @return void
     */
    public function done_mapper()
    {
        $post = Request::spost();
        $arr = Cache::get(sprintf("%s/%s/%s", Prop::PREFIX, $post['appname'], $post['field']));
        $ret = is_array($arr) ? array_column($arr, 'name', 'val') : [];
        return $ret;
    }

    public function index()
    {
        $field = Request::get('field');
        $field && $where['field'] = $field;
        $sapp = Request::get('sapp');
        $sapp && $where['app'] = $sapp;
        $cid = Request::get('cid');
        $cid && $where['cid'] = $cid;

        return  self::tableData(['where' => $where], function ($rows) {
            // $rows = array_map(function ($row) {
            //     return $row;
            // }, $rows);
            return $rows;
        });
    }

    /**
     * autoCache 在更新所有缓存时，将会自动执行
     */
    public static function autoCache()
    {
        Prop::cache();
    }
    public static function widget_count()
    {
        $total = PropModel::count();
        $widget[] = array($total, '全部');
        foreach (PropModel::$statusMap as $status => $text) {
            $count = PropModel::where('status', $status)->count();
            $widget[] = array($count, $text);
        }
        return $widget;
    }
}
