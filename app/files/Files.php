<?php

/**
 * iPHP - i PHP Framework
 * Copyright (c) 2012 iiiphp.com. All rights reserved.
 *
 * @author icmsdev <iiiphp@qq.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 *
 * CREATE TABLE `iPHP_files` (
 *   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 *   `userid` int(10) unsigned NOT NULL DEFAULT '0',
 *   `name` varchar(255) NOT NULL DEFAULT '',
 *   `source` varchar(255) NOT NULL DEFAULT '',
 *   `path` varchar(255) NOT NULL DEFAULT '',
 *   `intro` varchar(255) NOT NULL DEFAULT '',
 *   `ext` varchar(10) NOT NULL DEFAULT '',
 *   `size` int(10) unsigned NOT NULL DEFAULT '0',
 *   `time` int(10) unsigned NOT NULL DEFAULT '0',
 *   `type` tinyint(1) NOT NULL DEFAULT '0',
 *   PRIMARY KEY (`id`),
 *   KEY `ext` (`ext`),
 *   KEY `path` (`path`),
 *   KEY `source` (`source`),
 *   KEY `fn_userid` (`name`,`userid`)
 * ) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
 *
 * CREATE TABLE `iPHP_files_map` (
 * `fileid` int(10) unsigned NOT NULL,
 * `appid` int(10) NOT NULL,
 * `indexid` int(10) NOT NULL,
 * `addtime` int(10) NOT NULL,
 * PRIMARY KEY (`fileid`),
 * UNIQUE KEY `unique` (`fileid`,`appid`,`indexid`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8
 *
 */
// use iPHP\core\File;

class Files
{
    const APP = 'files';
    const APPID = iCMS_APP_FILES;

    public static $check_data = true; //检测文件数据是否存在
    public static $check_md5 = false; //检测文件md5是否存在
    public static $userid = false;

    public static function init($params = null)
    {
        self::$check_md5 = FilesClient::getConfig('check_md5');

        isset($params['userid']) && self::$userid = $params['userid'];
        FilesCloud::$enable && FilesCloud::init(Config::get('cloud'));
        if (FilesMark::$enable) {
            FilesMark::$config = Config::get('watermark');
            FilesMark::$enable && Hooks::set('FilesClient.upload.mark', ['FilesMark', 'run']);
        }
    }

    public static function updateSize($id, $size = '0')
    {
        FilesModel::update(compact('size'), $id);
    }

    public static function getFileid($indexid, $appid = '1')
    {
        $fileid0 = FilesMapModel::field('fileid')->where(compact('indexid', 'appid'))->pluck();
        $result = [];
        if ($fileid0) {
            $fileid1 = FilesMapModel::field('fileid')
                ->where('fileid', $fileid0)
                ->where('indexid', '<>', $indexid)
                ->pluck();
            if ($fileid1) {
                $result = array_diff((array) $fileid0, (array) $fileid1);
            } else {
                $result = $fileid0;
            }
        }
        return $result;
    }

    public static function deleteFiles($where)
    {
        if (empty($where)) {
            return [];
        }
        $result = FilesModel::field('*')->where($where)->select();
        $ret = [];
        foreach ((array) $result as $value) {
            $filepath = FilesClient::getRoot($value['path']);
            File::rm($filepath);
            $ret[] = $value['path'];
        }
        return $ret;
    }

    public static function rmEmptyDir($path)
    {
        $dir = dirname($path);
        if ($dir == '.') {
            return false;
        }
        $path = FilesClient::getRoot($path);
        $rootDir = dirname($path);
        $rootDir = str_replace('\\', '/', $rootDir);
        if (iPHP_APP_PATH == rtrim($rootDir, '/') . '/') {
            return false;
        }
        $files = glob($rootDir . '/*');
        if (empty($files)) {
            File::rmdir($rootDir, false);
            self::rmEmptyDir($dir);
        }
    }

    public static function deleteFile($where)
    {
        $row = FilesModel::field('*')->where($where)->get();
        if (empty($row)) {
            return false;
        }
        $filepath = FilesClient::getRoot($row['path']);
        if ($row['count'] < 2) {
            File::rm($filepath);
            Files::deleteData($row['id']);
        } else {
            $count = $row['count'] - 1;
            $count = $count < 0 ? 0 : $count;
            files::update(compact('count'), $row['id']);
        }
        return $row;
    }

    public static function deleteData($ids, $indexid = 0, $appid = '1')
    {
        if (empty($ids)) {
            return [];
        }

        FilesModel::where($ids)->delete();
        $appid && $where['appid'] = $appid;
        $indexid && $where['indexid'] = $indexid;
        $where['fileid'] = $ids;
        FilesMapModel::where($where)->delete();
    }

    public static function delete($appid, $indexid = null)
    {
        $where['appid'] = $appid;
        is_null($indexid) or $where['indexid'] = $indexid;
        $Model = FilesMapModel::field('fileid')->where($where);
        $sql = $Model->getSql();
        FilesModel::where('id', 'in', DB::raw($sql))->where('count', '>', '0')->dec('count');
        $Model->delete();
    }

    public static function insert($data, $type = 0, $status = 1)
    {
        if (!self::$check_data) {
            return;
        }
        $userid = self::$userid === false ? 0 : self::$userid;
        $data['userid'] = $userid;
        $data['time'] = time();
        $data['type'] = $type;
        $data['status'] = $status;
        $data['count'] = 1;
        return FilesModel::create($data, true);
    }

    public static function update($data, $fid = 0)
    {
        if (!self::$check_data) {
            return;
        }
        if (empty($fid)) {
            return;
        }
        $userid = self::$userid === false ? 0 : self::$userid;
        $data['userid'] = $userid;
        $data['time'] = time();
        return FilesModel::update($data, $fid);
    }

    //需要检测是否存在
    public static function getData($key, $value, $field = '*')
    {
        if (!self::$check_data) {
            return;
        }
        if (!self::$check_md5) {
            return;
        }
        return self::get($key, $value, $field);
    }

    public static function get($key, $value, $field = '*')
    {
        $where = [];
        self::$userid && $where['userid'] = self::$userid;
        $where[$key] = $value;
        $result = FilesModel::field($field)->where($where)->get();
        if ($result) {
            // $filepath = FilesClient::getRoot($result['path']);
            // $result['exist'] = @is_file($filepath);
        }
        return (array)$result;
    }


    public static function getType($ext){
        // 如果传入的是带点的完整扩展名，则去掉点
        if (strpos($ext, '.') !== false) {
            $ext = File::getExt($ext);
        }
        $ext = strtolower($ext);

        // 定义图片类型的扩展名
        $imageExts = [
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico', 'tiff', 'tif'
        ];

        // 定义视频类型的扩展名
        $videoExts = [
            'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'm4v', '3gp', '3g2', 
            'mxf', 'roq', 'nsv', 'amv', 'mpg', 'mpeg', 'asf', 'm2v', 'm2ts', 'm2t',
            'mts', 'mtv', 'qt', 'divx', 'h264', 'avc', 'hevc', 'hls', 'f4v', 'f4p',
            'f4a', 'f4b', 'mp2', 'mpe', 'mpv', 'svi', '3gpp', '3gpp2', 'jpm', 'jpx',
            'ogv', 'ogg', 'vob', 'mod', 'iso', 'vcd', 'dat', 'ram', 'rm', 'rmvb'
        ];

        // 根据扩展名判断文件类型
        if (in_array($ext, $imageExts)) {
            return 'image';
        } elseif (in_array($ext, $videoExts)) {
            return 'video';
        } else {
            return 'file';
        }
    }

// ... existing code ...
    public static function icon($ext, $flag = true)
    {
        if (strpos($ext, '.') !== false) {
            $ext = File::getExt($ext);
        }
        $ext = strtolower($ext);
        $dir = '/assets/libs/file-icons/svg/';
        $rootdir = iPHP_PUBLIC . $dir;
        $iconMap = Etc::get('files', 'file-icons');
        $name = 'file_type_%s.svg';
        $icon = sprintf($name, $ext);
        if ($fext = $iconMap[$ext]) {
            $icon = sprintf($name, $fext);
        }
        // var_dump($rootdir.$icon);
        if ($ext == 'folder') {
            $icon = 'default_folder.svg';
        } elseif (!is_file($rootdir . $icon)) {
            $icon = 'default_file.svg';
        }
        $format = $flag ? '<img border="0" src="%s%s" align="absmiddle" class="svg_icon" style="height:24px;width:24px;">' : '%s%s';
        return sprintf($format, $dir, $icon);
    }

    // public static function remoteAuth()
    // {
    //     $conf = FilesClient::$config['remote'];
    //     if (
    //         $conf['enable'] &&
    //         $_POST['AccessKey'] == $conf['AccessKey'] &&
    //         $_POST['SecretKey'] == $conf['SecretKey']
    //     ) {
    //         return true;
    //     } else {
    //         iJson::error();
    //     }
    // }
    public static function change($field, $appid, $paths, $event, $indexid)
    {
        $name = array_map(['FilesClient', 'name'], $paths);
        $where = compact('name');

        // $FilesModel = FilesModel::field('id')->where($where);
        // $FilesModel->inc('count');
        $fileids = FilesModel::field('id')->where($where)->pluck();
        if (empty($fileids)) { //files表无相关数据
        }
        $mWhere = compact('appid', 'indexid', 'field');
        $_fileids = FilesMapModel::field('fileid')->where($mWhere)->pluck();
        $diff = array_diff_values($fileids, $_fileids);
        if ($diff['+']) {
            $userid = (int)self::$userid;
            $addtime = time();
            FilesModel::where('id', $diff['+'])->inc('count');
            foreach ($diff['+'] as $fileid) {
                FilesMapModel::create(compact('fileid', 'userid', 'appid', 'indexid', 'addtime', 'field'));
            }
        }
        if ($diff['-']) {
            FilesModel::where('id', $diff['-'])->where('count', '>', '0')->dec('count');
            FilesModel::where('count', '<', '1')->delete();
            $mWhere['fileid'] = $diff['-'];
            FilesMapModel::where($mWhere)->delete();
        }
    }

    public static function cacheControl($expires = 86400, $control = 'public')
    {
        header('Cache-Control: ' . $control . ', max-age=' . $expires);
        header('Pragma: ' . $control);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
    }

    public static function readfile($paths)
    {
        $path = implode('/', $paths);
        Security::safePath($path) or Application::error('errors:invalid_path');
        $assets = sprintf('%s/%s', iPHP_APP_DIR, $path);
        $ext = File::getExt($path);
        if (in_array($ext, ['js', 'css', 'woff2', 'ttf']) && file_exists($assets)) {
            $expires = 86400 * 30;
            self::cacheControl($expires);
            header('Content-Type: ' . FilesMime::get($ext) . '; charset=UTF-8');
            header('Content-Length:' . filesize($assets));
            readfile($assets);
            exit;
        }
        return true;
    }

    public static function getAssets()
    {
        if ($paths = Request::getPaths()) {
            $app = Request::sget('app');
            empty($app) && $app = $paths[0];
            $app = Application::get($app, $sub);
            unset($paths[0]);
            if ($sub) {
                $sub = strtolower($sub);
                array_unshift($paths, $sub);
            }
            array_unshift($paths, $app);
            if (self::readfile($paths)) {
                $paths = array_map(function ($item) {
                    return $item === 'assets' ? 'views' : $item;
                }, $paths);
                $dist = ['dist', 'assets'];
                if ($sub) {
                    $sub = strtolower($sub);
                    $paths = array_filter($paths, function ($item) use ($sub) {
                        return $item !== $sub;
                    });
                    array_unshift($dist, $sub);
                }
                array_splice($paths, -1, 0, $dist);
                // $path = implode('/', $paths);
                // $assets = sprintf('%s/%s', iPHP_APP_DIR, $path);
                // var_dump($paths,$path ,$assets);
                ///admincp.php/testXxx/assets/aa.js
                ///app/test/views/xxx/dist/assets/aa.js
                if (self::readfile($paths)) {
                    Request::status(404);
                }
            }
        }
    }
}
