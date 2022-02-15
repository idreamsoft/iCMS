<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class UserNodeApp extends UserContentApp
{
    public function __construct()
    {
        parent::__construct();
    }
    public function API_manage()
    {
        return $this->display();
    }
    
    public function do_delete()
    {
        $id = Request::post('id');
        if ($id) {
            $where = [
                'id' => $id,
                'userid' => User::$id
            ];
            try {
                UserNodeModel::where($where)->delete();
                iJson::success('user:node:delete');
            } catch (\sException $ex) {
                $msg = $ex->getMessage();
                iJson::error($msg);
            }
        }
    }

    public function ACTION_data()
    {
        $id = Request::post('id');
        if ($id) {
            $where = [
                'id' => $id,
                'userid' => User::$id
            ];
            $data = UserNodeModel::where($where)->get();
            iJson::success($data);
        } else {
            iJson::error();
        }
    }
    public function ACTION_create()
    {
        $data = Request::post();
        extract($data);

        $userid = User::$id;
        $appid = iCMS_APP_ARTICLE;
        $status = '1';

        empty($name) && iJson::error('user:node:name:empty');

        $fwd = iPHP::callback('Filter::run', array(&$name), false);
        $fwd && iJson::error('user:node:name:disable');

        $fwd = iPHP::callback('Filter::run', array(&$description), false);
        $fwd && iJson::error('user:node:description:disable');

        $max = UserNodeModel::where(compact(['userid', 'appid']))->count('id');
        $max >= User::$config['node']['max'] && iJson::error('user:node:max');;

        $fields = UserNodeModel::getFields();
        $data = compact($fields);
        unset($data['id']);
        try {
            if ($id) {
                $data['id'] = UserNodeModel::update($data, ['id' => $id]);
            } else {
                $data['id'] = UserNodeModel::create($data);
            }
            iJson::success($data, 'user:node:success');
        } catch (\sException $ex) {
            $msg = $ex->getMessage();
            iJson::error($msg);
        }
    }
}
