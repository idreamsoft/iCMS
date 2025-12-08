<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesApp extends AppsBase
{
    public static $LOGIN = ['upload'];

    /**
     * 上传文件
     * @return array
     */
    public function api_upload()
    {
        $file = FilesClient::upload('upfile');
        empty($file) && self::alert('上传文件出错');
        $file['path'] && $url = FilesClient::getUrl($file['path']);
        $data = [
            'url'      => $url,
            'path'     => $file['path'],
            'value'    => $file['path'],
            'ext'      => $file['ext'],
            'image'    => in_array($file['ext'], FilesPic::$EXTS) ? 1 : 0,
            'original' => $file['source']
        ];
        return compact('data');
    }

    public function do_download()
    {
        $time = Request::get('time');
        $file = Request::get('file');
        $once = Request::get('once');
        $sign = Request::get('sign');
        // $auth = auth_decode($auth);
        // var_dump($auth);
        // list($file, $once, $time) = explode('@@', $auth);
        $type = 'download';
        $_sign = Utils::sign(compact('file', 'once', 'time'));
        if ($_sign !== $sign) {
            exit('非法请求!');
        }
        $data = Files::get('name', $file);
        if (!$data) {
            exit('文件不存在!');
        }
        $url = FilesClient::getUrl($data['path']);
        $path = FilesClient::getRoot($data['path']);
        if (!is_file($path)) {
            exit('文件不存在!');
        }
        $name = sprintf('%s.%s', random(6), $data['ext']);
        FilesClient::attachment($path, $name);
    }

    public function API_download()
    {
        $this->do_download();
    }

    public static function getArray($value)
    {
        $ret = [];
        if (substr($value, 0, 2) == 'a:') {
            $valArray = unserialize($value);
        } else {
            $valArray = json_decode($value, true);
        }
        if ($value && empty($valArray)) {
            $valArray = explode(',', $value);
        }
        if (is_array($valArray)) {
            foreach ($valArray as $i => $val) {
                if ($val) {
                    $pi = pathinfo($val);
                    $ret[$i] = [
                        'name' => $pi['filename'],
                        'ext'  => $pi['extension'],
                        'dir'  => $pi['dirname'],
                        'url'  => FilesApp::getUrl($pi['filename'])
                    ];
                }
            }
        }
        return $ret;
    }

    public static function getUrl($file, $type = 'download')
    {
        $time = $_SERVER['REQUEST_TIME'];
        $once = random(6);
        // $auth = Crypt::encrypt($file);
        $param = compact('file', 'once', 'time');
        $sign = Utils::sign($param);
        $param['sign'] = $sign;
        $url = Route::url('files/' . $type, $param);
        return $url;
    }
}
