<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

defined('APP_ASSETS') OR define('APP_ASSETS',iCMS_API_URL);
class EditorApp
{
    // public $noMethod = true;
    // public function __construct()
    // {
    //     parent::__construct();
    //     Security::csrf_check(User::$id,date("Ymd"));
    //     if (User::$data) {
    //         $method = Application::$DO;
    //         $editor = new EditorApi;
    //         if (method_exists($editor, $method)) {
    //             return $editor->$method();
    //         }
    //     }
    // }
    public function api_assets()
    {
        return Files::getAssets();
    }
    public function api_load()
    {
        return Editor::loadjs();
    }
}
