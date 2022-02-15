<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesCloud
{
    public static $enable = true;
    public static $config = null;
    public static $error  = null;

    public static function init($config)
    {
        if (!$config['enable']) return false;

        self::$config = $config;
        Hooks::set('FilesClient.upload.cloud', [__CLASS__, 'upload']);
        Hooks::set('File.delete', [__CLASS__, 'delete']);
    }
    public static function clients($vendor = null)
    {
        $map = [];
        empty(self::$config) && self::$config = Config::get('cloud');
        if ($vendor) {
            return self::vendor($vendor);
        }
        foreach ((array) self::$config['vendor'] as $vendor => $conf) {
            if($v = self::vendor($vendor)){
                $map[$vendor] = $v;
            }

        }
        return $map;
    }
    public static function vendor($vendor = null)
    {
        if ($vendor === null) return false;

        $conf = self::$config['vendor'][$vendor];
        if ($conf['AccessKey'] && $conf['SecretKey']) {
            $class = 'FilesCloud' . $vendor;
            if (!class_exists($class)) {
                $path = sprintf('%s/vendor/%s.php', __DIR__, $class);
                if (is_file($path)) {
                    require_once $path;
                }
            }

            return new $class($conf);
        } else {
            return false;
        }
    }
    /**
     * [上传文件]
     * @param  [type] $fileRootPath  [文件绝对路径]
     * @param  [type] $ext [description]
     * @return [type]      [description]
     */
    public static function upload($fileRootPath, $ext)
    {
        if (!self::$config['enable']) return false;

        $res = self::upload_file($fileRootPath);
        //不保留本地功能
        if (self::$config['local']) {
            //删除delete hook阻止云端删除动作
            $cb = Hooks::get('File.delete');
            Hooks::set('File.delete', null);
            File::rm($fileRootPath);
            Hooks::set('File.delete', $cb);
        }
        return $res;
    }
    /**
     * [上传文件]
     * @param  [type] $fileRootPath   [文件绝对路径]
     * @return [type]        [description]
     */
    public static function upload_file($fileRootPath)
    {
        $filePath = self::getPath($fileRootPath);
        foreach ((array) self::$config['vendor'] as $vendor => $conf) {
            $client = self::vendor($vendor);
            if ($client) {
                $res = $client->_upload_file($fileRootPath, $filePath);
                $res = json_decode($res, true);
                if ($res['error']) {
                    self::$error[$vendor] = array(
                        'action' => 'upload',
                        'code'   => 0,
                        'state'  => 'Error',
                        'msg'    => $res['msg']
                    );
                }
            }
        }
    }
    /**
     * [删除文件]
     * @param  [type] $fileRootPath   [文件绝对路径]
     * @return [type]        [description]
     */
    public static function delete($fileRootPath)
    {
        if (!self::$config['enable']) return false;

        $filePath = self::getPath($fileRootPath);
        foreach ((array) self::$config['vendor'] as $vendor => $conf) {
            $client = self::vendor($vendor);
            if ($client) {
                $res = $client->_delete_file($filePath);
                $res = json_decode($res, true);
                if ($res['error']) {
                    self::$error[$vendor] = array(
                        'action' => 'delete',
                        'code'   => 0,
                        'state'  => 'Error',
                        'msg'    => $res['msg']
                    );
                }
            }
        }
    }
    public static function getPath($fileRootPath)
    {
        return ltrim(FilesClient::getPath($fileRootPath, '-root'), '/');
    }
}
