<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ConfigDoneDistAdmincp extends AdmincpBase
{
    public function done_dist()
    {
        $sdo = Request::sget('sdo');
        if ($sdo) {
            return call_user_func([$this, iPHP_GET_PREFIX . $sdo], []);
        }
    }

    /**
    * 获取所有字典项
    */
    public function do_all()
    {
        return [ConfigDistModel::select()];
    }

    /**
     * 添加字典项
     */
    public function do_add()
    {
        $post = Request::post();
        $name = trim($post['name'] ?: '');
        $body = trim($post['body'] ?: '');

        if (!$name || !$body) {
            self::alert('Key 和 Value 都不能为空');
        }
        if (ConfigDistModel::where('name', $name)->get()) {
            self::alert('Key 已经存在');
        }
        $create_time = time();
        $insertId = ConfigDistModel::create(compact('name', 'body', 'create_time'));

        if ($insertId) {
            return true;
        } else {
            return [false, '该 Key 已存在或添加失败'];
        }
    }

    /**
     * 编辑字典项
     */
    public function do_edit()
    {
        $post = Request::post();
        $id = intval($post['id'] ?: 0);
        $name = trim($post['name'] ?: '');
        $body = trim($post['body'] ?: '');

        if (!$id || !$name || !$body) {
            self::alert('参数错误');
        }
        if (ConfigDistModel::where('id', '<>', $id)->where('name', $name)->value()) {
            self::alert('该 Key 已存在');
        }
        $update_time = time();
        $result = ConfigDistModel::update(compact('name', 'body', 'update_time'), $id);

        if ($result) {
            return true;
        } else {
            return [false, '该 Key 已存在或更新失败'];
        }
    }

    /**
     * 删除字典项
     */
    public function do_delete()
    {
        $id = Request::post('id');

        if (!$id) {
            self::alert('参数错误');
        }
        if (!ConfigDistModel::where('id', $id)->get()) {
            self::alert('该字典项不存在');
        }

        $result = ConfigDistModel::delete($id);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
