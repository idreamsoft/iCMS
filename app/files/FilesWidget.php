<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesWidget
{
    public static $picData = null;
    public static $vars = [];

    public static function setData($value = null, $vars = [])
    {
        self::$picData = $value;
        self::$vars = (array)$vars;
        return new self();
    }
    public static function uploadBtnModal($title = '本地上传', $field = null)
    {
        include AdmincpView::display("widget/uploadBtnModal", "files");
    }
    public static function uploadBtn($action = null)
    {
        include AdmincpView::display("widget/uploadBtn", "files");
    }
    public static function picBtn($field,$indexid = 0) {
        $json = '{}';
        $unid = uniqid();
        is_array(self::$vars) && extract(self::$vars);
        if ($multi) {
            // if (preg_match('/^a:\d+:\{/', self::$picData)) {
            if (substr(self::$picData, 0, 2) == 'a:') {
                $data = unserialize(self::$picData);
            } else {
                $data = json_decode(self::$picData, true);
            }
            if (self::$picData && empty($data)) {
                $data = explode("\n", self::$picData);
            }
            $array = array();
            if (is_array($data)) foreach ($data as $value) {
                $url = FilesClient::getUrl($value);
                $array[] = ['data' => compact("url", "value")];
            }
            $array && $json = json_encode($array);
        }

        $return && ob_start();
        include AdmincpView::display("widget/picbtn", "files");
        if ($return) {
            return AdmincpView::html();
        }
    }
    public static function modalBtn(
        $title = '',
        $target = 'template_index',
        $click = 'file',
        $callback = '',
        $do = 'seltpl',
        $from = 'modal'
    ) {
        $href = sprintf(
            '%s=files&do=%s&from=%s&click=%s&target=%s&callback=%s',
            ADMINCP_URL,
            $do,
            $from,
            $click,
            $target,
            $callback
        );
        $_title = $title . '文件';
        $click == 'dir' && $_title = $title . '目录';
        ob_start();
        include AdmincpView::display("widget/modalBtn", "files");
        return AdmincpView::html();
    }
}
