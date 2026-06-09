<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class EditorJsonAdmincp extends AdmincpBase
{
    public static $templates = [];
    public static function init()
    {
        self::$templates = Etc::get('editor', 'jsoneditor/templates');
        if (!self::$templates) {
            self::$templates = [];
        }
    }
    public function setData()
    {
        Etc::set('editor', 'jsoneditor/templates', self::$templates);
    }
    public function do_templates()
    {
        $method = Request::getMethod();
        $input = Request::input();

        if ($method == 'GET') {
            return $this->getList();
        }
        // if ($method == 'POST') {
        //     $this->save($input);
        // }
        if ($method == 'PUT') {
            $this->update($input);
        }
        if ($method == 'DELETE') {
            $this->delete($input);
        }
        $this->setData();
        return true;
    }
    public function done_templates()
    {
        $input = Request::input();
        $this->save($input);
        $this->setData();
        return true;
    }
    /**
     * 获取模板列表
     *
     * @return void
     */
    public function getList()
    {
        return ['data' => self::$templates];
    }
    public function save($input)
    {
        var_dump('sss');
        self::$templates[] = [
            'id' => random(16),
            'name' => $input['name'],
            'content' => $input['content']
        ];
    }
    public function update($input)
    {
        foreach (self::$templates as $key => $template) {
            if ($template['id'] == $input['id']) {
                self::$templates[$key] = [
                    'id' => $input['id'],
                    'name' => $input['name'],
                    'content' => $input['content']
                ];
            }
        }
    }
    public function delete($input)
    {
        foreach (self::$templates as $key => $template) {
            if ($template['id'] == $input['id']) {
                unset(self::$templates[$key]);
            }
        }
    }
}
