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
class FileCache
{
    protected $_cache_sock;
    protected $_have_zlib;
    protected $_compress_enable;
    protected $_compress_level;
    protected $_dir_level;
    protected $_dirs;
    protected $_file;

    public function __construct($args = ['dirs' => '', 'level' => '0', 'compress' => true])
    {
        $this->_dirs = rtrim(iPHP_APP_CACHE . '/' . $args['dirs'], '/') . '/';
        $this->_dir_level = is_numeric($args['level']) && $args['level'] ? floor(32 / (int)$args['level']) : -1;
        $this->_compress_enable = $args['compress'];
        $this->_compress_level = 9;
        $this->_have_zlib = function_exists('gzcompress');
        $this->_cache_sock = [];
    }

    public function ping()
    {
        return true;
    }

    public function set($key, $val, $exp = 0)
    {
        return $this->add($key, $val, $exp);
    }

    public function add($key, $val, $exp = 0)
    {
        $this->_file = $this->file($key, 'add');
        return $this->store($key, $val, $exp);
    }

    private function store($key, $val, $exp)
    {
        $type = gettype($val);
        $value = [
            'Time'    => time(),
            'Expires' => $exp,
            'Type'    => $type,
            'Data'    => $type === 'object' ? serialize($val) : $val,
        ];
        $data = json_encode($value, JSON_UNESCAPED_UNICODE);
        $this->_cache_sock = '<?php exit;?>';
        if ($this->_have_zlib && $this->_compress_enable) {
            $this->_cache_sock .= gzcompress($data, $this->_compress_level);
        } else {
            $this->_cache_sock .= $data;
        }
        return $this->write($this->_file, $this->_cache_sock);
    }

    public function get($key)
    {
        $this->_file = $this->file($key, 'get');
        if (!file_exists($this->_file)) {
            return null;
        }
        $handle = fopen($this->_file, 'rb');
        $size = filesize($this->_file);
        $content = $size > 0 ? fread($handle, filesize($this->_file)) : '';
        fclose($handle);
        $D = str_replace('<?php exit;?>', '', $content);
        $value = json_decode(($this->_have_zlib && $this->_compress_enable) ? @gzuncompress($D) : $D, true);
        if ($value['Type'] === 'object') {
            $value['Data'] = unserialize($value['Data']);
        }
        if ($value['Expires'] == 0) {
            return $value['Data'];
        } else {
            $_time = time();
            return ($_time - $value['Time'] < $value['Expires']) ? $value['Data'] : false;
        }
    }

    public function get_multi($keys)
    {
        foreach ($keys as $key) {
            $value[$key] = $this->get($key);
        }
        return $value;
    }

    public function replace($key, $value, $exp = 0)
    {
    }

    public function delete($key = '', $time = 0)
    {
        $this->_file = $this->file($key, 'get');
        if (file_exists($this->_file)) {
            @chmod($this->_file, 0777);
            if (!unlink($this->_file)) {
                throw new \Exception("Failed to delete file: {$this->_file}");
            }
            return true;
        }
        return false;
    }

    public function file($key, $method)
    {
        if (is_file($key)) {
            return $key;
        }
        $key = str_replace(':', '/', $key);
        $dirPath = $this->_dirs . (strpos($key, '/') !== false ? dirname($key) : '');
        if ($this->_dir_level != -1) {
            $md5_array = $this->split(md5($key), $this->_dir_level);
            $md5_array && $dirPath .= '/' . implode('/', $md5_array) . '/';
        }
        if (!file_exists($dirPath) && $method == 'add') {
            $this->mkdir($dirPath);
        }
        $strrchr = strrchr($key, '/');
        $strrchr !== false && $key = $strrchr;
        $filePath = $dirPath . $key . '.php';
        $this->check($filePath); // 添加路径安全检查
        return $filePath;
    }

    public function keys($pattern = '*')
    {
        $lists = [];
        if (strpos($pattern, '*') === false) {
            $pattern = sprintf('%s/%s/*', trim($this->_dirs, '/'), trim($pattern, '/'));
        }
        $array = glob($this->_dirs . $pattern);
        if ($array) {
            foreach ($array as $value) {
                $lists[] = $value;
                if (is_dir($value)) {
                    $_lists = $this->keys($value, $pattern);
                    $lists = array_merge($lists, $_lists);
                }
            }
        }
        return (array)$lists;
    }

    private function check($fn)
    {
        if (!Security::safePath($fn)) {
            throw new sException('What are you doing?', -99);
        }
    }

    private function write($fn, $data, $check = 1, $method = 'wb+', $iflock = 1, $chmod = 0)
    {
        $check && $this->check($fn);
        // @touch($fn);
        $handle = fopen($fn, $method);
        $iflock && flock($handle, LOCK_EX);
        fwrite($handle, $data);
        // $method=="rb+" && ftruncate($handle,strlen($data));
        fclose($handle);
        $chmod && @chmod($fn, 0644);
    }

    private function escapeDir($dir)
    {
        $dir = str_replace(["'", '#', '=', '`', '$', '%', '&', ';'], '', $dir);
        return rtrim(preg_replace('/(\/){2,}|(\\\){1,}/', '/', $dir), '/');
    }

    private function mkdir($d)
    {
        $d = $this->escapeDir($d);
        if (file_exists($d)) {
            return is_dir($d);
        }
        if (empty($d)) {
            return false;
        }
        if (mkdir($d, 0777, true)) {
            return true;
        }
        return false;
    }

    private function split($str, $level = 1)
    {
        if ($level < 1) {
            return false;
        }
        return array_map('strval', str_split($str, $level));
    }
}

//$c = new FileCache(array(
//				'dirs'=>"cache_dir_1",
//				'level'=>"1",
//		));
//$c->add("test",array(1,2,3,4,5,6),1000);
//$c->add("asd",array(1,2,3,4,5,6),10);
//$c->add("123123",array(1,2,3,4,5,6),1);
//$rs[]=$c->get("test");
//$rs[]=$c->get("asd");
//$rs[]=$c->get("123123");
//$rs2=$c->get_multi(array("test","asd","123123"));
//var_dump($rs);
//var_dump($rs2);
