<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesClient
{
    public static $force_ext = false;
    public static $valid_ext = true;
    public static $config = [
        'url'       => '',
        'dir'       => 'uploads',
        'dir_format'=> '{md5:0,2}/{md5:2,3}/',
        'allow_ext' => 'gif,jpg,rar,swf,jpeg,png,zip,mp4',
        'check_md5' => false,
    ];
    public static $data = null;
    public static $ERROR = null;
    public static $EXTS = [
        'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'psd', 'tif', 'svg',
        'flv', 'swf', 'mkv', 'avi', 'rm', 'rmvb', 'mpeg', 'mpg', 'mp4', 'm3u8',
        'ogg', 'ogv', 'mov', 'wmv', 'webm', 'mp3', 'aac', 'm4a', 'wav', 'mid', 'amr',
        'rar', 'zip', 'tar', 'gz', '7z', 'bz2', 'cab', 'iso', 'skill',
        'doc', 'docx', 'csv','xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'txt', 'md', 'xml',
        'apk', 'ipa',
        'js', 'css', 'json',
        'html', 'htm', 'shtml',
    ];

    public static function init($config)
    {
        self::$config = $config;
    }

    public static function getConfig($key = null)
    {
        return Utils::getConfig($key, self::$config);
    }

    public static function url($urls = null)
    {
        $urls === null && $urls = self::$config['url'];
        return trim($urls);
    }

    public static function attachment($path, $filename = null)
    {
        $info = pathinfo($path);
        $filename === null && $filename = $info['basename'];
        ob_end_clean();
        header('Content-Type: application/force-download');
        header('Content-Transfer-Encoding: binary');
        header('Content-Type: ' . FilesMime::get($info['extension']));
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Length: ' . filesize($path));
        readfile($path);
        flush();
        ob_flush();
    }

    /**
     * 删除文件
     * @return void
     */
    public static function rmfile($basedir = null)
    {
        $path = Request::param('path');
        $path == '' && iPHP::alert('请选择要删除的文件');
        !Security::safePath($path) && iPHP::alert('非法文件路径');

        $dir = self::basedir($basedir);
        $rootPath = File::pathMerge($dir, $path);

        !file_exists($rootPath) && iPHP::alert('文件不存在');

        if (File::rm($rootPath)) {
            return true;
        }
        iPHP::alert('文件删除失败');
    }

    /**
     * 创建目录
     */
    public static function mkdir($basedir = null)
    {
        $name = Request::param('name');
        strstr($name, '.') !== false && iPHP::alert('您输入的目录名称有问题');
        strstr($name, '..') !== false && iPHP::alert('您输入的目录名称有问题');
        $pwd = trim(Request::param('pwd'), '/');
        $dir = self::basedir($basedir);
        $dir = File::pathMerge($dir, $pwd);
        $dir = File::pathMerge($dir, $name);
        file_exists($dir) && iPHP::alert('您输入的目录名称已存在,请重新输入');
        if (File::mkdir($dir)) {
            return true;
        }
        iPHP::alert('目录创建失败,请检查目录权限');
    }

    /**
     * 删除目录
     */
    public static function rmdir($basedir = null)
    {
        $path = Request::param('path');
        $path == '' && iPHP::alert('请选择要删除的目录');
        !Security::safePath($path) && iPHP::alert('非法目录路径');
        $dir = self::basedir($basedir);
        $dir = File::pathMerge($dir, $path);

        if (File::rmdir($dir)) {
            return true;
        }
        iPHP::alert('目录删除失败');
    }

    public static function basedir($basedir)
    {
        if ($basedir == 'template') {
            $dir = File::pathMerge(iPHP_APP_PATH, 'template');
        } elseif ($basedir == 'cache') {
            $dir = File::pathMerge(iPHP_APP_PATH, 'cache');
        } else {
            $dir = File::pathMerge(iPHP_PUBLIC, self::$config['dir']);
        }
        return $dir;
    }

    //获取文件夹列表
    public static function folder($basedir = '', $dir = null)
    {
        is_null($dir) && $dir = Request::param('dir');
        if ($dir) {
            $dir = trim($dir, '/');
            File::check($dir);
        }
        if ($basedir) {
            $basedir = trim($basedir, '/');
            File::check($basedir);
        }
        $BASE_DIR = self::basedir($basedir);
        $DIR_PATH = File::pathMerge($BASE_DIR, $dir);

        strpos($DIR_PATH, $BASE_DIR) === false && self::throwError('DIR_ERROR');

        if (!is_dir($DIR_PATH)) {
            return false;
        }

        $url = Route::make('dir');

        list($dirList, $fileList) = self::folderData($DIR_PATH, $BASE_DIR, $url);
        $arr = [
            'dirList'  => (array) $dirList,
            'fileList' => (array) $fileList,
            'URI'      => $url,
            'parent'   => '',
            'pwd'      => trim(str_replace($BASE_DIR, '', $DIR_PATH), '/'),
        ];
        $arr['parent'] = dirname($arr['pwd']) ?: '';
        return $arr;
    }

    public static function folderData($dir, $rdir = null, $url = null)
    {
        $dir = iconv('utf-8', 'gb2312', $dir);
        $rdir = iconv('utf-8', 'gb2312', $rdir);
        $type = Request::sparam('type');
        $dirArray = [];
        $fileArray = [];
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                $filepath = File::pathMerge($dir, $file);
                $filepath = rtrim($filepath, '/');

                $sFileType = @filetype($filepath);
                $path = str_replace($rdir, '', $filepath);
                $path = ltrim($path, '/');
                if ($sFileType == 'dir' && !in_array($file, ['.', '..', 'admincp', 'iPHP', 'core', '.git', '.svn'])) {
                    $da = [
                        'path' => $path,
                        'name' => $file,
                        'type' => 'folder',
                        'size' => 0,
                        'modified' => filemtime($filepath),
                        'ext' => null,
                    ];

                    $url && $da['url'] = Route::make(['dir' => $path], $url);
                    $dirArray[] = $da;
                }
                if ($sFileType == 'file' && !in_array($file, ['..'])) {
                    if (!self::checkExt($file)) {
                        continue;
                    }
                    $filext = File::getExt($file);
                    $fa = [
                        'path'     => $path,
                        'dir'      => dirname($path),
                        'name'     => $file,
                        'modified' => get_date(filemtime($filepath), 'Y-m-d H:i:s'),
                        'md5'      => md5_file($filepath),
                        'ext'      => $filext,
                        'size'     => File::sizeUnit(filesize($filepath)),
                        'type'     => 'file',
                    ];
                    $url && $fa['url'] = self::getUrl($path);
                    if ($type) {
                        in_array(strtolower($filext), $type) && $fileArray[] = $fa;
                    } else {
                        $fileArray[] = $fa;
                    }
                }
            }
        }
        return [$dirArray, $fileArray];
    }

    public static function getDir()
    {
        $dir = File::pathMerge(iPHP_PUBLIC, self::$config['dir']);
        return rtrim($dir, '/') . '/';
    }

    public static function createDir($udir = null, $md5 = null, $ext = null)
    {
        $FileDir = self::$config['dir_format'];
        preg_match_all('@\{(.+?)\}@', $FileDir, $matches);
        if ($matches[1]) {
            foreach ($matches[1] as $key => $value) {
                if (substr($value, 0, 4) == 'md5:') {
                    $format = substr($value, 4);
                    list($start, $len) = explode(',', $format);
                    if ($len === null) {
                        $len = $start;
                        $start = 0;
                    }
                    $dir = substr($md5, $start, $len);
                } elseif ($value == '{EXT}') {
                    $dir = $ext;
                } else {
                    if (substr($value, 0, 5) == 'date:') {
                        $value = substr($value, 5);
                    }
                    $dir = get_date(0, $value);
                }
                $FileDir = str_replace($matches[0][$key], $dir, $FileDir);
            }
        }

        if ($udir) {
            File::check($udir);
            $FileDir = $udir;
        }

        $FileDir = rtrim($FileDir, '/') . '/';
        $FileDir = ltrim($FileDir, './');
        $RootPath = self::getDir() . $FileDir;
        $RootPath = rtrim($RootPath, '/') . '/';
        File::mkdir($RootPath) or self::throwError('MKDIR_ERROR', $RootPath);
        return [$RootPath, $FileDir];
    }

    public static function saveUploadFile($file, $path)
    {
        $flag = true;
        if (function_exists('move_uploaded_file') && $flag = move_uploaded_file($file, $path)) {
            @chmod($path, 0644);
        } elseif ($flag = copy($file, $path)) {
            @chmod($path, 0644);
        } elseif (is_readable($file) && is_writable($path)) {
            if ($rb = @fopen($file, 'rb')) {
                @flock($rb, 2);
                $filedata = @fread($rb, @filesize($file));
                @fclose($rb);
            }
            if ($fp = fopen($path, 'wb')) {
                @flock($fp, 2);
                @fwrite($fp, $filedata);
                @fclose($fp);
                @chmod($path, 0644);
            }
        } else {
            $flag = false;
        }
        // is_file($file) && @unlink($file);
        return $flag ? true : self::throwError('UPLOAD_SAVE_ERROR');
    }

    /**
     * 流数据
     */
    public static function input($name = '', $udir = '', $ext = 'jpg', $type = '3', $data = null)
    {
        $data === null && $data = Request::input();
        if (empty($data)) {
            return false;
        }
        $name && File::check($name);
        $hash = md5($data);
        $name or $name = $hash;

        $size = strlen($data);
        $source = $name . '.' . $ext;
        $ext = self::validExt($source); //判断文件类型
        list($rootPath, $dir) = self::createDir($udir, $hash, $ext); // 文件保存目录方式
        $path = $dir . $source;
        $filePath = $rootPath . $source;
        File::put($filePath, $data);
        $data = compact('name', 'source', 'path', 'intro', 'ext', 'size');
        $data['id'] = files::insert($data, $type);
        self::hook('upload', [$filePath, $ext]);
        return $data;
    }

    public static function base64ToFile($base64Data, $udir = '', $FileExt = 'png')
    {
        if (empty($base64Data)) {
            return false;
        }

        $filedata = base64_decode($base64Data);
        return self::input(null, $udir, $FileExt, '2', $filedata);
    }

    public static function upload($field = 'upfile', $udir = '', $name = '', $ext = '')
    {
        $files = Request::file($field);
        if (is_array($files['name'])) {
            $fileArray = [];
            foreach ($files as $key => $value) {
                foreach ($value as $k => $v) {
                    $fileArray[$k][$key] = $v;
                }
            }
            $data = [];
            foreach ($fileArray as $key => $file) {
                if ($fd = self::file($file, $udir, $name, $ext)) {
                    $data[] = $fd;
                } else {
                    // $data[] = $fd;
                }
            }
            return $data;
        }

        return self::file($files, $udir, $name, $ext);
    }

    public static function file($file, $udir = '', $fname = '', $ext = '')
    {
        if ($file['tmp_name']) {
            $tmp_file = $file['tmp_name'];
            if (!is_uploaded_file($tmp_file)) {
                self::throwError('UPLOAD_ERROR');
            }
            if ($file['error'] > 0) {
                @unlink($tmp_file);
                switch ((int) $file['error']) {
                    case UPLOAD_ERR_NO_FILE:
                        self::throwError('NO_FILE_UPLOAD');
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        self::throwError('UPLOAD_MAX');
                        break;
                }
                self::throwError($file['error']);
            }
            $source = $file['name'];
            empty($ext) && $ext = self::validExt($source); //判断文件类型
            if (self::$data) {
                extract(self::$data);
            } else {
                $name = md5_file($tmp_file);
                $size = @filesize($tmp_file);
                $frs = files::getData('name', $name);
                if ($frs) {
                    $count = $frs['count'] + 1;
                    files::update(compact('count'), $frs['id']);
                    return $frs;
                };
            }
            list($rootPath, $dir) = self::createDir($udir, $name, $ext); // 文件保存目录方式

            if ($fname) {
                File::check($fname);
                $name = $fname;
            }
            $path = $dir . $name . '.' . $ext;

            self::validExt($path); //判断文件类型
            $filePath = self::getRoot($path);

            self::saveUploadFile($tmp_file, $filePath);
            $data = compact('name', 'source', 'path', 'intro', 'ext', 'size');
            if ($id) {
                $count = $count + 1 ;
                files::update(compact('count', 'source', 'size'), $id);
                $data['id'] = $id;
            } else {
                $data['id'] = files::insert($data, 0);
            }
            self::hook('upload', [$filePath, $ext]);
            return $data;
        } else {
            self::throwError('ERROR');
        }
    }

    public static function checkImageBin($path, $bin = false)
    {
        if (empty($path)) {
            return false;
        }
        if ($bin) {
            $head = substr($path, 0, 16);
        } else {
            if (!is_file($path)) {
                return false;
            }

            $fh = fopen($path, 'rb');
            //必须使用rb来读取文件，这样能保证跨平台二进制数据的读取安全
            //仅读取前面的16个字节
            $head = fread($fh, 16);
            fclose($fh);
        }

        $arr = unpack('C*', $head);
        $string = null;
        foreach ($arr as $k => $C) {
            if ($C >= 48 && $C <= 127) {
                $string .= chr($C);
            }
        }
        if (empty($string)) {
            return false;
        }

        $string = strtoupper($string);
        $format = [
            'JFIF', 'TIFF', 'RIFF',
            'PNG', 'GIF89A', 'GIF87A', 'JPEG',
            'BMP', 'WEBP',
            'JPEG 2000', 'EXIF', 'BPG', 'SVG'
        ];
        foreach ($format as $key => $f) {
            if (strpos($string, $f) !== false) {
                return $f;
            }
        }
        return false;
    }

    public static function allowExt($exts, $allow = null)
    {
        $exts_array = explode(',', $exts);
        is_null($allow) && $allow = self::$EXTS;
        foreach ($exts_array as $key => $ext) {
            if (!in_array($ext, $allow)) {
                return false;
            }
        }
        return true;
    }

    public static function checkExt($ext, $allow = null)
    {
        $ext = File::getExt($ext);
        $ext = strtolower($ext);
        return self::allowExt($ext, $allow);
    }

    public static function validExt($fn)
    {
        $_ext = strtolower(File::getExt($fn));
        $ext = self::allowExt($_ext) ? $_ext : 'file';

        if (self::$force_ext !== false) {
            if (empty($_ext) || strlen($_ext) > 4 || $ext == 'file') {
                $ext = self::$force_ext;
            }
            return $ext;
        }
        if (!self::$valid_ext) {
            return $ext;
        }

        $ext_array = explode(',', strtolower(self::$config['allow_ext']));
        if (in_array($_ext, $ext_array)) {
            return $ext;
        } else {
            self::throwError('TYPE', $fn);
        }
    }

    public static function getRoot($path)
    {
        return self::path($path, '+root');
    }

    public static function getUrl($path)
    {
        return self::path($path, '+http');
    }

    public static function getPath($path, $flag = null)
    {
        if ($flag !== null) {//兼容旧版本
            return self::path($path, $flag);
        }
        return self::path($path, 'relative');
    }

    public static function path($f, $m = '+http')
    {
        $config = self::$config;
        $url = self::$config['url'];
        $dir = File::pathMerge(iPHP_PUBLIC, $config['dir']);
        switch ($m) {
            case '+http':
                $fp = rtrim($url, '/') . '/' . ltrim($f, '/');
                Request::isUrl($f) && $fp = $f;
                break;
            case '-http':
                $fp = str_replace($url, '', $f);
                break;
            case 'http2root':
                $f = str_replace($url, '', $f);
                $fp = $dir . '/' . ltrim($f, '/');
                break;
            case 'root2http':
                $f = str_replace($dir, '', $f);
                $fp = $url . $f;
                break;
            case '+root':
                $fp = $dir . '/' . ltrim($f, '/');
                break;
            case '-root':
                $fp = str_replace($dir, '', $f);
                break;
            case 'relative':
                if (Request::isUrl($f)) {
                    $fp = str_replace($url, '', $f);
                } else {
                    $fp = str_replace($dir, '', $f);
                }
                break;
        }
        return $fp;
    }

    public static function name($path)
    {
        $path = trim($path);
        if (Request::isUrl($path)) {
            $url = self::url();
            $host = parse_url($url, PHP_URL_HOST);
            if (stripos($path, $host) !== false) {
                $path = self::getPath($path, '-http');
            } else {
                return false;
            }
        }
        $name = basename($path);
        $name = substr($name, 0, 32);
        return $name;
    }

    //--------upload---end-------------------------------
    public static function remote($http, &$ret = null, $times = 0)
    {
        $frs = files::getData('source', $http);

        if ($frs) {
            if (is_array($ret)) {
                $ret = $frs;
                return true;
            }
            return $frs['path'];
        }
        $ext = self::validExt($http); //判断过滤文件类型

        $fdata = Http::remote($http);
        if ($fdata) {
            $name = md5($fdata);
            list($rootPath, $dir) = self::createDir(null, $name, $ext); // 文件保存目录方式
            $frs = files::getData('name', $name);
            if ($frs) {
                $path = $frs['path'];
                $filePath = self::getRoot($path);
                if (!is_file($filePath)) {
                    File::mkdir(dirname($filePath));
                    File::put($filePath, $fdata);
                    self::hook('upload', [$filePath, $ext]);
                }
                if (is_array($ret)) {
                    $ret = $frs;
                    return true;
                }
            } else {
                $FileName = $name . '.' . $ext;
                $path = $dir . $FileName;
                $filePath = $rootPath . $FileName;
                File::put($filePath, $fdata);
                $size = @filesize($filePath) ?: 0;
                $source = $http;
                $data = compact('name', 'source', 'path', 'intro', 'ext', 'size');
                $data['id'] = files::insert($data, 1);
                self::hook('upload', [$filePath, $ext]);
                if (is_array($ret)) {
                    $ret = $data;
                    return true;
                }
            }
            return $path;
        } else {
            self::throwError('REMOTE_EMPTY');
        }
    }

    public static function hook($key, $param)
    {
        return Hooks::call("FilesClient.{$key}", $param);
    }

    public static function checkConf($config)
    {
        if (!self::allowExt($config['allow_ext'])) {
            self::throwError('ATTACHMENT_EXT_ILLEGAL');
        }
        $forbidArray = ['template', 'app', 'cache', 'config', 'core', 'iPHP'];
        $text = implode('，', $forbidArray);
        foreach ($forbidArray as $key => $forbid) {
            if (strpos($config['dir'], $forbid) !== false) {
                self::throwError('ATTACHMENT_DIR_FORBID', $text);
            }
            if (strpos($config['dir_format'], $forbid) !== false) {
                self::throwError('ATTACHMENT_DIR_FORMAT_FORBID', $text);
            }
        }
        if (substr_count($config['dir'], '..') > 2) {
            self::throwError('ATTACHMENT_DIR_DOT_FORBID');
        }
        if (!Security::safePath($config['dir_format'])) {
            self::throwError('ATTACHMENT_DIR_FORMAT_DOT_FORBID');
        }
    }

    public static function throwError($type, $msg = null)
    {
        iPHP::alert('FILES:' . $type);
    }
}
