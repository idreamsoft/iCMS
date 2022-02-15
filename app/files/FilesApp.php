<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesApp
{
    public $methods = array(iPHP_APP, 'download', 'remote_save', 'remote_delete');
    public function do_iCMS()
    {
    }
    public function API_iCMS()
    {
    }
    // public function ACTION_remote_save()
    // {
    //     if (!Files::remoteAuth()) {
    //         return false;
    //     }
    //     $key  = Request::post('key');
    //     $path = Request::post('path');
    //     $info = pathinfo($path);
    //     $dir  = $info['dirname'];
    //     $name = $info['filename'];
    //     $ext  = $info['extension'];
    //     $F = FilesClient::upload($key, $dir, $name, $ext);
    //     if ($F === false) {
    //         $array = array(
    //             'error' => 1,
    //             'msg' => FilesClient::$ERROR
    //         );
    //     } else {
    //         $array = array(
    //             'error' => 0,
    //             'msg' => $F
    //         );
    //     }
    //     echo json_encode($array);
    // }
    // public function ACTION_remote_delete()
    // {
    //     if (!Files::remoteAuth()) {
    //         return false;
    //     }
    //     $path = Request::post('path');
    //     $FileRootPath = FilesClient::getRoot($path);
    //     $array = array('error' => '1', 'msg' => 'delete error');
    //     if (File::rm($FileRootPath)) {
    //         $array = array('error' => '0', 'msg' => 'delete success');
    //     }
    //     echo json_encode($array);
    // }
    public function do_download()
    {
        $t = Request::get('t');
        $auth = Request::get('file');
        $sign = Request::get('sign'); 
        $auth = auth_decode($auth);
        list($file, $once, $time) = explode('@@', $auth);
        $type = 'download';
        $_sign = Utils::sign(compact('file', 'once', 'time'));
        if ($_sign !== $sign) {
            exit("非法请求!");
        }
        $data = Files::get('name', $file);
        $url  = FilesClient::getUrl($data['path']);
        $path = FilesClient::getRoot($data['path']);
        if (!is_file($path)) {
            exit("文件不存在!");
        }
        $name = sprintf('%s.%s', random(6), $data['ext']);
        FilesClient::attachment($path, $name);
    }

    public function API_download()
    {
        $this->do_download();
    }
    public static function getUrl($file, $type = 'download')
    {
        $time = $_SERVER['REQUEST_TIME'];
        $once = random(6);
        $auth = auth_encode($file . '@@' . $once . '@@' . $time);
        $sign = Utils::sign(compact('file', 'once', 'time'));
        $url = sprintf(
            '%s?app=files&do=%s&file=%s&sign=%s&t=%s',
            iCMS_API,
            $type,
            urlencode($auth),
            $sign,
            $time
        );

        return $url;
    }
}
