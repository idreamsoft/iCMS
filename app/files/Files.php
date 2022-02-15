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
    const APP = 'fiels';
    const APPID = iCMS_APP_FILES;

    public static $check_data = true; //检测文件数据是否存在
    public static $check_md5 = false; //检测文件md5是否存在
    public static $userid  = false;

    public static function config()
    {
        self::$check_md5 = Config::get('FS.check_md5');
    }

    public static function init($vars = null)
    {
        Files::config();

        isset($vars['userid']) && Files::$userid = $vars['userid'];
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
        $result  = array();
        if ($fileid0) {
            $fileid1 = FilesMapModel::field('fileid')
                ->where('fileid', $fileid0)
                ->where('indexid', '<>', $indexid)
                ->pluck();
            if ($fileid1) {
                $result  = array_diff((array) $fileid0, (array) $fileid1);
            } else {
                $result  = $fileid0;
            }
        }
        return $result;
    }
    public static function deleteFile($where)
    {
        if (empty($where)) return array();
        $result = FilesModel::field("*")->where($where)->select();
        $ret  = array();
        foreach ((array) $result as $value) {
            $path = self::path($value);
            $filepath = FilesClient::getRoot($path);
            File::rm($filepath);
            $ret[] = $path;
        }
        return $ret;
    }
    public static function deleteData($ids, $indexid = 0, $appid = '1')
    {
        if (empty($ids)) return array();

        FilesModel::where($ids)->delete();
        $appid && $where['appid'] = $appid;
        $indexid && $where['indexid'] = $indexid;
        $where['fileid'] = $ids;
        FilesMapModel::where($where)->delete();
    }
    public static function path($F, $root = false)
    {
        $path = $F['path'] . $F['name'] . '.' . $F['ext'];
        $root && $path = FilesClient::getRoot($path);
        return $path;
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
        $data['time']   = time();
        $data['type']   = $type;
        $data['status'] = $status;
        $data['count']  = 0;
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
        self::$userid && $where['userid'] = self::$userid;
        $result = FilesModel::field($field)->where($key, $value)->where($where)->get();

        if ($result && $field == '*') {
            if ($key == 'source') {
                $filepath = FilesClient::getRoot($result['path']);
                if (is_file($filepath)) {
                    return $result;
                } else {
                    return false;
                }
            }
        }
        return $result;
    }

    public static function icon($ext, $dir = null)
    {
        if (strpos($ext, '/') !== false) {
            $ext = File::getExt($ext);
        }
        $ext = strtolower($ext);
        $rootdir = __DIR__ . '/assets/icon/';
        $iconArray = glob($rootdir . '*.gif');
        $icons = implode(',', str_replace($rootdir, '', $iconArray));
        $icon = $ext . '.gif';
        strpos($icons, $icon) === false && $icon = "common.gif";
        $dir or $dir = "./app/files/assets";
        return sprintf(
            '<img border="0" src="%s/icon/%s" align="absmiddle" class="icon">',
            $dir,
            $icon
        );
    }

    public static function remoteAuth()
    {
        $conf = FilesClient::$config['remote'];
        if (
            $conf['enable'] &&
            $_POST['AccessKey'] == $conf['AccessKey'] &&
            $_POST['SecretKey'] == $conf['SecretKey']
        ) {
            return true;
        } else {
            iJson::error();
        }
    }
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
            $userid  = (int)Files::$userid;
            $addtime = time();
            FilesModel::where($diff['+'])->inc('count');
            foreach ($diff['+'] as $fileid) {
                FilesMapModel::create(compact('fileid', 'userid', 'appid', 'indexid', 'addtime', 'field'));
            }
        }
        if ($diff['-']) {
            FilesModel::where(array('id' => $diff['-']))->where('count', '>', '0')->dec('count');
            FilesModel::where('count', '<', '1')->delete();
            $mWhere['fileid'] = $diff['-'];
            FilesMapModel::where($mWhere)->delete();
        }
    }
}
