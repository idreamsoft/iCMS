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

class FormsDataAdmincp extends AdmincpCommon
{
    use AdmincpCommonTrait;
    public $fid = 0;

    public function __construct($fid = null)
    {
        parent::__construct();
        Node::$APPID = self::$APPID;
        $fid === null && $fid = Request::param('fid');
        $this->fid = (int) $fid;
        $this->fid && self::$MODEL = FormsData::init($this->fid);
    }

    /**
     * 添加表单内容
     */
    public function do_add()
    {
        $this->id && $rs = FormsData::get($this->id);
        FormerApp::add(FormsData::$DATA, $rs);
        include self::view();
    }

    /**
     * 保存表单数据
     * @return void
     */
    public function save(&$data = [])
    {
        $data = FormerApp::save(FormsData::$DATA, null, $update);
        return $data;
    }

    /**
     * 表单数据查看
     */
    public function do_index()
    {
        if ($this->fid && FormsData::$DATA['fields']) {
            $fields = Former::fields(FormsData::$DATA['fields']);
        }
        if (Request::isAjax()) {
            $keywords = Request::get('keywords');
            $sfield = Request::get('sfield');
            $pattern = Request::get('pattern');

            if ($keywords) {
                $search = [];
                if (empty($sfield)) {
                    foreach ((array) $fields as $fi => $field) {
                        $field['field'] == 'VARCHAR' && $search[] = $field['id'];
                    }
                    $search && $where['CONCAT(`' . implode('`,`', $search) . '`)'] = ['REGEXP', $keywords];
                } else {
                    empty($pattern) && $pattern = 'REGEXP';
                    $where[$sfield] = [$pattern, $keywords];
                }
            } else {
                $pattern && $where[$sfield] = [$pattern, $keywords];
            }

            return  self::tableData(['where' => $where], function ($rows) {
                $nodeArray = Node::many($rows, 'cid');
                return array_map(function ($row) use ($nodeArray) {
                    $C = (array)$nodeArray[$row['cid']];
                    $iurl = Route::get(Forms::APP, [$row, $C]);
                    $row['url'] = $iurl->href;
                    return $row;
                }, $rows);
            });
        }
        $columns = [];
        if ($fields) {
            foreach ($fields as $key => $field) {
                if ($field['admincp_index'] && $field['id']) {
                    if ($field['id'] != 'id') {
                        $row = [
                            'field' => $field['id'],
                            'title' => $field['label'] ?: 'Lang("' . $field['id'] . '")',
                        ];
                        if ($field['type'] == 'textarea') {
                            $row['_formatter'] = 'content';
                        }
                        if ($field['type'] == 'datetime') {
                            $row['_formatter'] = 'datetime';
                        }
                        if (isset($field['admincp_format']) && $field['admincp_format']) {
                            $row['_formatter'] = $field['admincp_format'];
                        }
                        $columns[] = $row;
                    }
                }
            }
        }
        $columns = json_encode($columns);

        include self::view();
    }

    /**
     * 删除表单数据

     */
    public function deleting($ids = null)
    {
        FormsData::delete($ids);
        return true;
    }

    public function do_detail()
    {
        $rs = FormsData::get($this->id);
        $fields = ContentModel::fullFields();
        include self::view();
    }
}
