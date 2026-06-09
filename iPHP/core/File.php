<?php

// namespace iPHP\core;
/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
class File
{
    public static function exist($file)
    {
        return file_exists($file) === false ? false : true;
    }

    public static function is($file)
    {
        return self::exist($file);
    }

    public static function isDir($file)
    {
        return is_dir($file);
    }

    public static function readable($file)
    {
        return is_readable($file);
    }

    public static function writable($file)
    {
        return is_writable($file);
    }

    public static function atime($file)
    {
        return fileatime($file);
    }

    public static function mtime($file)
    {
        return filemtime($file);
    }

    public static function check($fn, $throw = true)
    {
        $tmpname = $fn ? strtolower($fn) : '';
        $tmparray = ['://', "\0", '%00', '..'];
        if (str_replace($tmparray, '', $tmpname) != $tmpname) {
            if ($throw) {
                throw new sException('File check failed');
            }

            return false;
        }

        return true;
    }

    public static function rm($fn, $check = 1)
    {
        $check && self::check($fn);
        if (self::exist($fn)) {
            @chmod($fn, 0777);
            $del = @unlink($fn);
            Hooks::call('File.delete', [$fn]);

            return $del;
        }

        return false;
    }

    public static function get($fn, $check = 1, $method = 'rb')
    {
        $check && self::check($fn);
        if (function_exists('file_get_contents') && $method != 'rb') {
            $filedata = file_get_contents($fn);
        } else {
            if ($handle = fopen($fn, $method)) {
                flock($handle, LOCK_SH);
                $size = (int) filesize($fn);
                $filedata = $size > 0 ? fread($handle, $size) : '';
                fclose($handle);
            }
        }

        return $filedata;
    }

    public static function put($path, $data, $check = 1, $method = 'wb+', $iflock = 1, $chmod = 0)
    {
        $check && self::check($path);
        self::mkdir(dirname($path));
        touch($path);
        $handle = fopen($path, $method);
        $iflock && flock($handle, LOCK_EX);
        fwrite($handle, $data);
        $method == 'rb+' && ftruncate($handle, strlen($data));
        fclose($handle);
        $chmod && @chmod($path, 0644);
        Hooks::call('File.put', [$path, $data]);
    }

    // 将内容添加在文件原内容前面
    public static function prepend($path, $data)
    {
        $data = self::get($path) . $data;
        self::put($path, $data);
    }

    // 将内容添加在文件原内容后
    public static function append($path, $data)
    {
        self::put($path, $data, 1, 'ab+');
    }

    public static function copy($path, $target)
    {
        if (self::exist($path)) {
            self::mkdir(dirname($target));
            $data = self::get($path);

            return self::put($target, $data);
        }

        return false;
    }

    /**
     * 目录转换
     * @param  $dir
     * @return string
     */
    public static function escapeDir($dir)
    {
        $dir = str_replace(["'", '#', '=', '`', '$', '%', '&', ';', "\0"], '', $dir);

        return rtrim(preg_replace('/(\/){2,}|(\\\){1,}/', '/', $dir), '/');
    }

    //创建目录
    public static function mkdir($path)
    {
        $path = self::escapeDir($path);
        $path = str_replace('//', '/', $path);
        if (!file_exists($path)) {
            if (self::mkdir(dirname($path))) {
                $stat = stat(dirname($path));
                $perms = $stat['mode'] & 0007777;  // Get the permission bits.
                if ($path != '/' && mkdir($path, $perms)) {
                    @chmod($path, $perms);
                    return true;
                };
                return false;
            }
        } else {
            return true; // 目录已存在
        }
        return false; // 创建目录失败
    }

    public static function checkDir($dirpath)
    {
        if (empty($dirpath)) {
            return false;
        }
        if (!is_dir($dirpath)) {
            return false;
        }

        $dirpath = rtrim($dirpath, '/') . '/';
        $test = $dirpath . 'test.txt';
        if ($fp = @fopen($test, 'wb')) {
            fclose($fp);
            @unlink($test);

            return true;
        } else {
            return false;
        }
    }

    //删除目录
    public static function rmdir($dir, $df = true, $ex = null)
    {
        $dir = str_replace('\\', '/', $dir);

        $forbidDirArr = [iPHP_APP_PATH, iPHP_APP_DIR, iPHP_APP_CORE, iPHP_APP_CACHE, iPHP_PUBLIC, iPHP_TEMPLATE_DIR, iPHP_CONFIG_DIR];
        $sDir = rtrim($dir, '/');
        if (in_array($sDir, $forbidDirArr)) {
            return false;
        }

        $exclude = ['.', '..'];
        $ex && $exclude = array_merge($exclude, (array) $ex);
        if ($dh = @opendir($dir)) {
            while (($file = @readdir($dh)) !== false) {
                if (!in_array($file, $exclude)) {
                    $path = $dir . '/' . $file;
                    is_dir($path) ? self::rmdir($path, $df) : ($df ? @unlink($path) : null);
                }
            }
            closedir($dh);
        }
        $flag = @rmdir($dir);
        $flag && Hooks::call('File.rmdir', [$dir]);

        return $flag;
    }

    public static function getDirSize($dir)
    {
        $pattern = sprintf('%s/*', $dir);
        $variable = glob($pattern);
        $size = 0;
        if ($variable) {
            foreach ($variable as $key => $value) {
                if (is_dir($value)) {
                    $size += self::getDirSize($value);
                } else {
                    $size += filesize($value);
                }
            }
        }

        return $size;
    }

    public static function gitignore($filePath, $rules = [])
    {
        if (empty($rules)) {
            return false;
        }
        // 统一路径分隔符并移除开头的斜杠
        $filePath = str_replace('\\', '/', $filePath);
        $filePath = ltrim($filePath, '/');
        
        foreach ($rules as $rule) {
            $rule = trim($rule);
            if (empty($rule) || strpos($rule, '#') === 0) {
                continue;
            }
            
            // 处理否定规则（以!开头）
            $negate = false;
            if (strpos($rule, '!') === 0) {
                $negate = true;
                $rule = substr($rule, 1);
            }
            
            // 移除规则开头的斜杠
            $rule = ltrim($rule, '/');
            
            // 检查是否是目录规则（以/结尾）
            $isDirRule = substr($rule, -1) === '/';
            if ($isDirRule) {
                $rule = rtrim($rule, '/');
            }
            
            // 检查规则是否包含路径分隔符（如 admin-ui/node_modules）
            $hasSlash = strpos($rule, '/') !== false;
            
            // 转换 gitignore 模式为正则表达式
            $pattern = preg_quote($rule, '/');
            // 处理 ** 通配符（匹配任意路径）
            $pattern = str_replace('\\*\\*', '.*', $pattern);
            // 处理 * 通配符（匹配除/外的任意字符）
            $pattern = str_replace('\\*', '[^\/]*', $pattern);
            // 处理 ? 通配符（匹配单个字符）
            $pattern = str_replace('\\?', '[^\/]', $pattern);
            
            // 构建完整的正则表达式
            if ($hasSlash) {
                // 包含路径的规则：从开头匹配，同时匹配目录本身和其下内容
                $regex = '/^' . $pattern . '(\/.*)?$/';
            } elseif ($isDirRule) {
                // 目录规则：匹配该目录及其下所有内容
                $regex = '/(?:^|\/)'  . $pattern . '(\/.*)?$/';
            } else {
                // 文件规则：匹配路径中包含该模式或以该模式结尾
                $regex = '/(?:^|\/)' . $pattern . '$/';
            }
            
            if (preg_match($regex, $filePath)) {
                if ($negate) {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    //获取文件夹下所有文件/文件夹列表
    public static function fileList($dir, $pattern = '*')
    {
        $lists = [];
        $dir = rtrim($dir, '/');
        self::check($dir);
        foreach (glob($dir . '/' . $pattern) as $value) {
            $lists[] = $value;
            if (is_dir($value)) {
                $_lists = self::fileList($value, $pattern);
                $lists = array_merge($lists, $_lists);
            }
        }

        return (array)$lists;
    }

    public static function info($path)
    {
        return (object) pathinfo($path);
    }

    public static function path($p = '')
    {
        $p && $p = str_replace("\0", '', $p);
        $end = substr($p, -1);
        $a = explode('/', $p);
        $o = [];
        $c = count($a);
        for ($i = 0; $i < $c; $i++) {
            if ($a[$i] == '.' || $a[$i] == '') {
                continue;
            }

            if ($a[$i] == '..' && $i > 0 && end($o) != '..') {
                array_pop($o);
            } else {
                $o[] = $a[$i];
            }
        }

        $o[0] == 'https:' && $o[0] = 'https:/';
        $o[0] == 'http:' && $o[0] = 'http:/';

        return (substr($p, 0, 1) == '/' ? '/' : '') . implode('/', $o) . ($end == '/' ? '/' : '');
    }

    public static function pathIsAbsolute($path)
    {
        // this is definitive if true but fails if $path does not exist or contains a symbolic link
        if (realpath($path) == $path) {
            return true;
        }

        if (strlen($path) == 0 || $path[0] == '.') {
            return false;
        }

        // windows allows absolute paths like this
        if (preg_match('#^[a-zA-Z]:\\\\#', $path)) {
            return true;
        }

        // a path starting with / or \ is absolute; anything else is relative
        return (bool) preg_match('#^[/\\\\]#', $path);
    }

    public static function pathMerge($base, $path, $rtrim = false)
    {
        //if (!self::path_is_absolute($path))

        $path = rtrim($base, '/') . '/' . ($path ? ltrim($path, '/') : '');
        $path = self::path($path);
        $rtrim && $path = rtrim($path, '/') . '/';

        return $path;
    }

    //文件名
    public static function name($fn)
    {
        return pathinfo($fn, PATHINFO_FILENAME);
        
        $_fn = substr(strrchr($fn, '/'), 1);
        return substr($_fn, 0, strrpos($_fn, '.'));
    }

    public static function getPath($fn)
    {
        // $_fn = substr(strrchr($fn, "/"), 1);
        return substr($fn, 0, strrpos($fn, '.'));
    }

    // 获得文件扩展名
    public static function getExt($fn)
    {
        if (Request::isUrl($fn)) {
            $fn = parse_url($fn, PHP_URL_PATH);
        }

        return pathinfo($fn, PATHINFO_EXTENSION);
    }

    // 获取文件大小
    public static function sizeUnit($filesize)
    {
        $SU = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $n = 0;
        while ($filesize >= 1024) {
            $filesize /= 1024;
            $n++;
        }

        return round($filesize, 2) . ' ' . $SU[$n];
    }
}
