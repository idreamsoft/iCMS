<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class EditorJsonApp
{
    public $templates = [];
    public function __construct()
    {
        $this->templates = Etc::get('editor', 'jsoneditor/templates');
        if (!$this->templates) {
            $this->templates = [];
        }
    }
    public function setData()
    {
        Etc::set('editor', 'jsoneditor/templates', $this->templates);
    }
    public function api_templates()
    {
        $method = Request::getMethod();
        $input = Request::input();

        if ($method == 'GET') {
            return $this->getList();
        }
        if ($method == 'POST') {
            $this->save($input);
        }
        if ($method == 'PUT') {
            $this->update($input);
        }
        if ($method == 'DELETE') {
            $this->delete($input);
        }
        $this->setData();
        return true;
    }
    public function getList()
    {
        return ['data' => $this->templates];
    }
    public function save($input)
    {
        $this->templates[] = [
            'id' => random(16),
            'name' => $input['name'],
            'content' => $input['content']
        ];
    }
    public function update($input)
    {
        foreach ($this->templates as $key => $template) {
            if ($template['id'] == $input['id']) {
                $this->templates[$key] = [
                    'id' => $input['id'],
                    'name' => $input['name'],
                    'content' => $input['content']
                ];
            }
        }
    }
    public function delete($input)
    {
        foreach ($this->templates as $key => $template) {
            if ($template['id'] == $input['id']) {
                unset($this->templates[$key]);
            }
        }
    }
}
