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

class AppsStore
{
    public static $zip_name  = null;
    public static $is_git    = false;
    public static $success   = false;
    public static $msg_mode  = null;
    public static $uptime    = 0;
    public static $authcode  = null;

    public static $APIMAP  = [];

    public static $IS_TEST      = false;
    public static $IS_FORCE     = false;
    public static $typeArray = array(
        '0' => 'app',
        '1' => 'template',
        '2' => 'plugin',
        '3' => 'form'
    );
    public static $typeMap = array(
        '0' => '应用',
        '1' => '模板',
        '2' => '插件',
        '3' => '表单'
    );
    public static $appId    = null;
    public static $PKG_PATH  = null;
    public static $IS_UPDATE = false;
    public static $MESSAGES = [];
    public static $DATA = [];
    public static $FILELIST = [];

    public static function getPkg($name)
    {
        self::$PKG_PATH = AppsPackage::getLocalPath($name) . '.' . AppsPackage::EXT; //package
        return self::$PKG_PATH;
    }
    public static function installDefault()
    {
        $app = self::$DATA['application'];
        $files = self::unzip();
        $files && self::setup_app_file($files);
        self::$IS_TEST && self::$MESSAGES['TEST']['rm'] = self::$PKG_PATH;
        self::$IS_TEST or File::rm(self::$PKG_PATH);
        // self::$IS_UPDATE ? self::setup_update($app) : self::setup_install($app);
        self::$MESSAGES[] = '安装完成';
        return true;
    }
    public static function installTemplate()
    {
        $dir = self::$DATA['application'];
        $files = self::unzip();
        $files && self::setup_template_file($files, $dir);
        self::$IS_TEST && self::$MESSAGES['TEST']['rm'] = self::$PKG_PATH;
        self::$IS_TEST or File::rm(self::$PKG_PATH);
        self::$MESSAGES[] = '模板安装完成';
        return true;
    }

    public static function install($sid, $orderkey)
    {
        switch (AppsStore::$DATA['type']) {
            case '0':
                self::installApp();
                break;
            case '1':
                self::installTemplate();
                break;
            default:
                self::installDefault();
        }
        AppsStore::save([
            'sid'    => $sid,
            'appid'    => (int)AppsStore::$appId,
            'files'    => AppsStore::$FILELIST,
            'authkey'  => $orderkey,
            'transaction_id' => AppsStore::$DATA['transaction_id'],
            'app'      => AppsStore::$DATA['application'],
            'name'     => AppsStore::$DATA['name'],
            'git_time' => AppsStore::$DATA['git_time'],
            'git_sha'  => AppsStore::$DATA['git_sha'],
            'version'  => AppsStore::$DATA['version'],
            'type'     => AppsStore::$DATA['type'],
            'data'     => AppsStore::$DATA['authcode'],
        ], $sid);
    }
    public static function installApp()
    {
        $app = self::$DATA['application'];
        $files = self::unzip();
        $files && self::setup_data($files, '应用', AppsPackage::$LocalDataFile, 'AppsModel');  //安装应用数据
        $files && self::setup_table($files, AppsPackage::$LocalTableFile); //创建应用表
        $files && self::setup_app_file($files);

        self::$IS_UPDATE ?
            self::setup_update($app) :
            self::setup_install($app);

        Apps::cache() && self::$MESSAGES[] = '更新应用缓存';
        Menu::cache() && self::$MESSAGES[] = '更新菜单缓存';
        self::$IS_TEST && self::$MESSAGES['TEST']['rm'] = self::$PKG_PATH;
        self::$IS_TEST or File::rm(self::$PKG_PATH);
        self::$MESSAGES[] = '应用安装完成';
        return true;
    }
    public static function setup_template_file(&$pkgFiles)
    {
        self::$MESSAGES[] = '开始测试目录权限';
        if (!File::checkDir(iPHP_PATH)) {
            return self::throwMsg(iPHP_PATH . '根目录无写权限');
        }
        if (!File::checkDir(iPHP_TPL_DIR)) {
            return self::throwMsg(iPHP_TPL_DIR . '目录无写权限');
        }
        //测试目录文件是否写
        self::extractTest($pkgFiles);
        self::$MESSAGES[] = '开始安装模板';
        self::extract($pkgFiles);
        return true;
    }
    public static function setup_data(&$pkgFiles, $title, $dataFileName, $modelName)
    {
        $app = Security::safeStr(self::$DATA['application']);
        foreach ($pkgFiles as $key => $file) {
            $filename = basename($file['filename']);
            if ($filename == $dataFileName) {
                unset($pkgFiles[$key]);

                $content = get_php_content($file['content']);
                
                if (substr($content, 0, 2) == 'a:') {
                    $array = unserialize($content);
                } else if (substr($content, 0, 1) == '{') {
                    $array = json_decode($content, true);
                } else {
                    //v7
                    $content = base64_decode($content);
                    $array   = unserialize($content);    
                }

                empty($array) && self::throwMsg($dataFileName . ' 数据错误');

                $array['app'] = $app;

                $model = new $modelName;
                //强制安装
                if (!self::$IS_FORCE) {
                    $model->check(['app' => $app])
                        && self::throwMsg([
                            '该' . $title . '已经存在'
                        ]);
                }
                if ($array['table']) {
                    $tableArray = AppsTable::items($array['table']);
                    if ($tableArray) foreach ($tableArray as $value) {
                        self::$MESSAGES[] = '检测' . $title . '表是否存在';
                        if (DB::hasTable($value['table'])) {
                            if (self::$IS_FORCE) {
                                DB::rename($value['table'], $value['table'] . date("YmdHis"));
                            } else {
                                self::throwMsg('检测到[' . $value['table'] . ']数据表已经存在');
                            }
                        }
                    }
                }

                $array['addtime'] = time();
                self::$IS_TEST && self::$MESSAGES['TEST']['setup_data'][] = $array;
                self::$IS_TEST or self::$appId = $model->create($array, true);
            }
        }
        self::$MESSAGES[] = '应用信息保存完成';
        return true;
    }

    public static function setup_table(&$pkgFiles, $tableFileName = null)
    {
        foreach ($pkgFiles as $key => $file) {
            $filename = basename($file['filename']);
            if ($filename == $tableFileName) {
                unset($pkgFiles[$key]);

                $sql = get_php_content($file['content']);
                self::$IS_TEST && self::$MESSAGES['TEST']['setup_table'] = $sql;
                if (!self::$IS_TEST && $sql) {
                    try {
                        AppsTable::query($sql);
                    } catch (\sException $ex) {
                        self::throwMsg($ex->getMessage());
                    }
                }
                self::$MESSAGES[] = '应用表创建完成';
            }
        }
    }

    public static function setup_app_file(&$pkgFiles)
    {
        self::$MESSAGES[] = '开始权限测试';
        if (!File::checkDir(iPHP_PATH)) {
            self::throwMsg(iPHP_PATH . '根目录无写权限');
        }
        if (!File::checkDir(iPHP_APP_DIR)) {
            self::throwMsg(iPHP_APP_DIR . '目录无写权限');
        }
        if (!File::checkDir(iPHP_TPL_DIR)) {
            self::throwMsg(iPHP_TPL_DIR . '模板无写权限');
        }
        //测试目录文件是否写
        self::extractTest($pkgFiles);
        self::$MESSAGES[] = '开始安装应用';
        self::extract($pkgFiles);
        return true;
    }
    public static function setup_update($app, $flag = false)
    {
        $ROOTPATH = iAPP::path($app);
        $array = glob($ROOTPATH . "iCMS.APP.UPDATE.*.php");
        if (is_array($array)) {
            foreach ($array as $filename) {
                $d    = str_replace(array($ROOTPATH, 'iCMS.APP.UPDATE.', '.php'), '', $filename);
                $time = strtotime($d . '00');
                if ($time > self::$uptime) {
                    $files[$d] = $filename;
                }
            }
        }
        //插件
        $ROOTPATH = iPHP_APP_DIR . '/';
        $array2 = glob($ROOTPATH . "iCMS." . $app . ".UPDATE.*.php");
        if (is_array($array2)) {
            foreach ($array2 as $filename) {
                $d    = str_replace(array($ROOTPATH, 'iCMS.' . $app . '.UPDATE.', '.php'), '', $filename);
                $time = strtotime($d . '00');
                if ($time > self::$uptime) {
                    $files[$d] = $filename;
                }
            }
        }
        if ($files) {
            ksort($files);
            foreach ($files as $key => $file) {
                if ($flag === 'delete') {
                    File::rm($file);
                } else {
                    $name = $app . '_' . str_replace(array('.php', '.'), array('', '_'), basename($file));
                    self::setup_exec($file, $name, '升级');
                }
            }
        }
    }
    public static function unzip()
    {
        self::$MESSAGES[] = '正在对安装包进行解压缩';
        $package = self::$PKG_PATH;
        if (file_exists($package)) {
            Vendor::run('PclZip');
            $zip = new PclZip($package);
            if (false == ($pkgFiles = $zip->extract(PCLZIP_OPT_EXTRACT_AS_STRING))) {
                File::rm($package);
                self::throwMsg("ZIP包错误:" . $zip->errorInfo(true));
            }

            if (0 == count($pkgFiles)) {
                File::rm($package);
                self::throwMsg("空的ZIP文件");
            }
        } else {
            self::throwMsg("安装包不存在");
        }
        empty($pkgFiles) && self::throwMsg("安装包不存在");
        self::$MESSAGES[] = '解压完成';
        self::$IS_TEST && self::$MESSAGES['TEST']['unzip'] = $pkgFiles;

        return $pkgFiles;
    }
    public static function setup_install($app)
    {
        $path = iAPP::path($app, 'iCMS.APP.INSTALL');
        is_file($path) or $path = iPHP_APP_DIR . '/iCMS.' . $app . '.INSTALL.php';
        self::setup_update($app, 'delete');
        if (is_file($path)) {
            return self::setup_exec($path, $app, '安装');
        }
    }
    public static function setup_exec($file, $name, $title = '升级')
    {
        $func = require_once $file;
        if (is_callable($func)) {
            self::$MESSAGES[] = '执行[' . $name . ']' . $title . '程序';
            try {
                self::$IS_TEST && self::$MESSAGES['TEST']['setup_exec']['setup_app_data'] = $func;
                self::$IS_TEST or self::$MESSAGES[] = call_user_func($func);
                self::$MESSAGES[] = $title . '顺利完成!';
                self::$MESSAGES[] = '删除' . $title . '程序!';
            } catch (\Exception $e) {
                self::throwMsg('[' . $name . ']' . $title . '出错');
            }
        } else {
            self::throwMsg([
                '[' . $name . ']' . $title . '出错',
                '找不到' . $title . '程序'
            ]);
        }
        File::rm($file);
        return true;
    }
    public static function setup_func($func, $run = false)
    {
        if ($run) {
            $output = $func();
            $output = str_replace('<iCMS>', '<br />', $output);
            $output = Security::filterPath($output);
            echo $output;
        }
        return $func;
    }

    public static function backupDir($name)
    {
        $dir = iPHP_PATH . $name;
        if (File::exist($dir)) {
            $bakdir = iPHP_PATH . '.backup/' . $name . '_' . date("Ymd");
            File::mkdir($bakdir) && self::$MESSAGES[] = '备份目录创建完成';
            return $bakdir;
        }
    }
    public static function extractTest($pkgFiles)
    {
        $msg = [];
        if (is_array($pkgFiles)) {
            foreach ($pkgFiles as $file) {
                $folder = $file['folder'] ? $file['filename'] : dirname($file['filename']);
                $dir = iPHP_PATH  . trim($folder, '/') . '/';
                if (!File::checkDir($dir) && File::exist($dir)) {
                    $msg[] = $dir . '目录无写权限';
                }
                self::$IS_TEST && self::$MESSAGES['TEST']['extract_test'][$dir] = '权限测试通过';

                if (empty($file['folder'])) {
                    $path = iPHP_PATH  . $file['filename'];
                    if (file_exists($path) && !@is_writable($path)) {
                        $msg[] = $path . '文件无写权限';
                    }
                    self::$IS_TEST && self::$MESSAGES['TEST']['extract_test'][$path] = '权限测试通过';
                }
            }
        }
        if ($msg) {
            self::$MESSAGES[] = '权限测试无法完成';
            self::$MESSAGES[] = '请设置好上面提示的文件写权限';
            self::$MESSAGES[] = '然后重新安装';
            self::throwMsg($msg);
        }
        self::$MESSAGES[] = '权限测试通过';
        return true;
    }
    public static function extract($pkgFiles)
    {
        $bakdir = self::backupDir(self::$DATA['application']);
        if (is_array($pkgFiles)) {
            foreach ($pkgFiles as $file) {
                $folder = $file['folder'] ? $file['filename'] : dirname($file['filename']);
                $dp = trim($folder, '/') . '/';
                $rdp = iPHP_PATH . $dp;
                if (!File::exist($rdp)) {
                    self::$FILELIST[] = $dp;
                    self::$IS_TEST or File::mkdir($rdp);
                    self::$MESSAGES[] = '创建文件夹 [' . $dp . ']';
                }
                if (!$file['folder']) {
                    $fn = $file['filename'];
                    $rfp = iPHP_PATH . $fn;
                    if ($bakdir) {
                        $bfp = $bakdir . '/' . $file['filename'];
                        File::copy($rfp, $bfp) && self::$MESSAGES[] = '备份 [' . $rfp . '] 文件 到 [' . $bfp . ']';
                    }
                    self::$FILELIST[] = $fn;
                    self::$IS_TEST or File::put($rfp, $file['content']);
                    self::$MESSAGES[] = '写入文件 [' . $rfp . ']';
                }
            }
        }
    }


    public static function save($data, $sid = null)
    {
        if (self::$IS_UPDATE) {
            $data['uptime'] = time();
            unset($data['appid'], $data['authkey'], $data['data']); //不更新
            AppsStoreModel::update($data, array('sid' => $sid));
        } else {
            $data['addtime'] = $data['uptime'] = time();
            AppsStoreModel::create($data, true);
        }
    }
    public static function delete($appid)
    {
        return AppsStoreModel::delete(compact('appid'));
    }
    public static function throwMsg($msg = '', $type = null)
    {
        is_array($msg) && $msg = implode("\n", $msg);
        throw new FalseEx($msg, $type);
    }
}
