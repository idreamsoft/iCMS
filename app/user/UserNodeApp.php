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
    public function API_manage()
    {
        return $this->display();
    }

    /**
     * user:node:delete
     * @return void
     */
    public function api_delete()
    {
        $id = (int) Request::post('id');
        $where = ['id' => $id, 'userid' => User::$id];
        return (bool)UserNodeModel::where($where)->delete();
    }

    public function api_get()
    {
        $id = (int) Request::post('id');
        $where = ['id'=> $id, 'userid' => User::$id];
        return UserNodeModel::where($where)->get();
    }

    public function api_create()
    {
        $post = Request::post();
        extract($post);
        $userid = User::$id;
        $appid = iCMS_APP_ARTICLE;
        $status = '1';

        empty($name) && AppsBase::alert('user:node:name:empty');

        $fwd = iPHP::callback('Filter::run', [&$name], false);
        $fwd && AppsBase::alert('user:node:name:disable');

        $fwd = iPHP::callback('Filter::run', [&$description], false);
        $fwd && AppsBase::alert('user:node:description:disable');

        $max = UserNodeModel::where(compact(['userid', 'appid']))->count('id');
        if ($max && User::$config['node']['max'] && $max >= User::$config['node']['max']) {
            AppsBase::alert('user:node:max');
        }

        $fields = UserNodeModel::getFields();
        $data = compact($fields);
        unset($data['id']);
        if ($id) {
            $data['id'] = UserNodeModel::update($data, ['id' => $id]);
        } else {
            $data['id'] = UserNodeModel::create($data);
        }
        return 'user:node:success';
    }
}
