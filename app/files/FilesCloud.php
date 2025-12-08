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
            if ($v = self::vendor($vendor)) {
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
     * 上传文件
     * @param  [type] $rootPath  [文件绝对路径]
     * @param  [type] $ext [description]
     * @return void
     */
    public static function upload($rootPath, $ext = null)
    {
        if (!self::$config['enable']) return false;

        self::upload_file($rootPath);
        //不保留本地功能
        if (self::$config['local']) {
            //删除delete hook阻止云端删除动作
            $cb = Hooks::get('File.delete');
            Hooks::set('File.delete', null);
            File::rm($rootPath);
            Hooks::set('File.delete', $cb);
        }
        return true;
    }
    /**
     * 上传文件
     * @param  [type] $rootPath   [文件绝对路径]
     * @return void
     */
    public static function upload_file($rootPath)
    {
        $path = self::getPath($rootPath);
        foreach ((array) self::$config['vendor'] as $vendor => $conf) {
            $client = self::vendor($vendor);
            if ($client) {
                try {
                    $client->upload($rootPath, $path);
                } catch (\Exception $ex) {
                    $msg = $ex->getMessage();
                    Utils::log(['upload', $msg, $client->response],$vendor);
                    throw $ex;
                }
            }
        }
    }
    /**
     * 删除文件
     * @param  [type] $rootPath   [文件绝对路径]
     * @return void
     */
    public static function delete($rootPath)
    {
        if (!self::$config['enable']) return false;

        $path = self::getPath($rootPath);
        foreach ((array) self::$config['vendor'] as $vendor => $conf) {
            $client = self::vendor($vendor);
            if ($client) {
                try {
                    $response = $client->delete($path);
                } catch (\Exception $ex) {
                    $msg = $ex->getMessage();
                    Utils::log(['delete', $msg, $client->response],$vendor);
                    throw $ex;
                }
            }
        }
    }
    public static function getPath($rootPath)
    {
        return ltrim(FilesClient::getPath($rootPath, '-root'), '/');
    }
}
