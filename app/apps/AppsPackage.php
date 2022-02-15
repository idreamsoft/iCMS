<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');

class AppsPackage
{
    const EXT = 'package';
    public static $LocalFormat    = "%s%s.APP.%s-v%s.%s";
    public static $LocalDataFile  = "iCMS.APP.DATA.php";
    public static $LocalTableFile = "iCMS.APP.TABLE.php";

    public static function getName($app, $version)
    {
        return sprintf(self::$LocalFormat, '', iPHP_APP, $app, ltrim($version, 'v'), self::EXT);
    }
    public static function matchFile($file)
    {
        $pattern = sprintf(preg_quote(self::$LocalFormat), '', 'iCMS', '(\w+)', '\d+\.\d+\.\d+', self::EXT);
        if (preg_match("/^" . $pattern . "$/", $file, $match)) {
            return $match;
        }
        return false;
    }
    public static function getLocalPath($name = null)
    {
        $dir = iPHP_APP_CACHE . '/pkg/';
        if (!File::exist($dir)) {
            File::mkdir($dir);
        }
        return $name ? $dir . $name : $dir;
    }
    //获取本地安装包
    public static function getLocalPkg()
    {
        $path = sprintf(self::$LocalFormat, self::getLocalPath(), iPHP_APP, '*', '*.*.*', self::EXT);
        $pkgs = glob($path);
        $pkgs = array_map(function ($file) {
            return str_replace(self::getLocalPath(), '', $file);
        }, $pkgs);
        return $pkgs;
    }

    public static function packDataBase($dir, $data, $tables)
    {
        unset($data['id']);
        $json = json_encode($data);
        //表单数据
        $data_file = $dir . '/' . self::$LocalDataFile;
        put_php_file($data_file, $json);

        //数据库结构
        $table_file = $dir . '/' . self::$LocalTableFile;
        if ($tables) {
            put_php_file(
                $table_file,
                AppsTable::makeTableSql($tables)
            );
        }
        return [$data_file, $table_file];
    }
    public static function createPackage($name, $app, $dir, $REMOVE_PATH = null)
    {
        Vendor::run('PclZip');

        $package = self::getLocalPath($name);
        $zip = new PclZip($package);
        $fileList = File::fileList($dir);
        $templateDir = sprintf('%s/%sApp', iPHP_TPL_DIR, $app);
        if (file_exists($templateDir)) {
            $tplfileList = File::fileList($templateDir);
            $fileList = array_merge($fileList, $tplfileList);
        }

        foreach ($fileList as $key => $value) {
            if (strpos($value, '/.git/') === false) {
                $lists[] = $value;
            }
        }

        if ($REMOVE_PATH) {
            $v_list = $zip->create($lists, PCLZIP_OPT_REMOVE_PATH, $REMOVE_PATH); //将文件进行压缩
        } else {
            $v_list = $zip->create($lists); //将文件进行压缩
        }
        $path = iPHP_PATH . $app . '.php';
        file_exists($path) && $zip->add(array($path), PCLZIP_OPT_REMOVE_PATH, iPHP_PATH);

        $v_list == 0 && iPHP::throwError($zip->errorInfo(true)); //如果有误，提示错误信息。
        return $package;
    }
}
