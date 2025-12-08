<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class Editor
{
    public static function create($name,$index,$data){
        echo $name,$index;
    }
    public static function ueditor($id, $config = array(), $gateway = true)
    {
        $gateway = $gateway ? 'admincp' : 'usercp';
        $app = $config['app']['app'];
        empty($app) && $app = 'content';
        $editor_id = self::getID($id,'UE');
        ob_start();
        include AdmincpView::view("ueditor.script", "editor");
        return AdmincpView::html();
    }
    public static function markdown($id, $config = array(), $gateway = true)
    {
        $gateway = $gateway ? 'admincp' : 'usercp';
        $app = $config['app']['app'];
        empty($app) && $app = 'content';
        $editor_id = self::getID($id,'MD');
        ob_start();
        include AdmincpView::view("markdown.script", "editor");
        return AdmincpView::html();
    }
    public static function getID($id,$prefix)
    {
        return sprintf("%s_%s",$prefix,md5($id));
    }
    /**
     * 加载 编辑器初始化JS
     *
     * @param string $name
     * @return array
     */
    public static function loadjs($name = 'boot')
    {
        $paths = [];
        // $many = Etc::many('editor', 'editor/*');
        // Etc::mergeRecursive($many, $editor, false);

        /* /app/editor/assets/[*]/boot.js*/
        /* /app/editor/assets/ueditor/boot.js */
        /* /app/editor/assets/vditor/boot.js */
        $fn = sprintf('*/%s', $name);
        $jsArray = AdmincpBase::jsScan($fn, "editor");
        if ($jsArray) foreach ($jsArray as $key => $value) {
            preg_match('@assets/(.+)/' . $name . '@', $value['jspath'], $match);
            $edName = $match[1];
            $path = rtrim($value['jspath'], '.js');
            $paths[$edName.'Boot'] = $path;
        }

        return compact('paths');
    }
}
