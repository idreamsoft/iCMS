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
 * 后台通用方法
 */
class AdmincpCommon extends AdmincpBase
{
    /**
     * [添加{title}]
     */
    public function do_add()
    {
        if ($this->id) {
            $rs = self::$MODEL->get($this->id);
        }
        include self::view("add");
    }
    /**
     * [编辑{title}]
     */
    public function do_edit()
    {
        return $this->do_add();
    }
    /**
     * [复制{title}]
     */
    public function do_copy()
    {
        $rs = self::$MODEL->get($this->id);
        unset($rs['id']);
        $rs['name']  && $rs['name'] .= '副本';
        $rs['title'] && $rs['title'] .= '副本';
        $rs['sortnum'] && $rs['sortnum'] = time();

        $id = self::$MODEL->create($rs);
        $url = APP_URL . '&do=edit&id=' . $id;
        Helper::redirect($url);
    }
    /**
     * [删除{title}]
     */
    public function do_delete($id = null)
    {
        $id === null && $id = $this->id;
        $id or self::alert('请选择要删除的项目');
        self::$MODEL->delete($id);
    }


    public function do_manage()
    {
        $keyword = Request::get('keyword');
        $keyword && $where['CONCAT(title,description)'] = array('REGEXP', $keyword);
        $userid = Request::get('userid');
        $userid && $where['uid'] = $userid;

        $orderby = self::setOrderBy();
        $result = self::$MODEL->where($where)
            ->orderBy($orderby)
            ->paging();

        include self::view("manage");
    }

    public function do_config()
    {
        if (Request::isPost()) {
            return $this->save_config();
        }
        $appid = self::$appId;
        if ($GLOBALS['CONFIG_VAPPID']) {
            $GLOBALS['CONFIG_APPID'] = $appid;
            $appid = Config::VAPPID;
        }
        Config::app($appid);
    }

    /**
     * [{title}批处理]
     */
    public function do_batch()
    {
        $stype = AdmincpBatch::$config['stype'];
        $actions = array(
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    $this->do_delete($id);
                }
            }
        );
        return AdmincpBatch::run($actions);
    }
    /**
     * [更新{title}]
     *
     * @return void
     */
    public function do_update()
    {
        if ($this->id && self::$MODEL) {
            if ($data = Request::args()) {
                call_user_func_array(
                    [self::$MODEL, 'update'],
                    [$data, $this->id]
                );
            }
        }
    }
}
