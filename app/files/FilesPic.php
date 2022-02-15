<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesPic
{
    public static $FIELDS = array('pic', 'bpic', 'mpic', 'spic');

    public static $PREG_IMG         = '@<img[^>]+src=(["\']?)(.*?)\\1[^>]*?>@is';
    public static $EXTS          = array('jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg');

    public static function download($value, $key = 'pic')
    {
        if (Request::isUrl($value)  && !isset($_POST[$key . '_http'])) {
            $value  = FilesClient::remote($value);
        }
        return $value;
    }
    public static function values(&$data)
    {
        foreach (self::$FIELDS as $key) {
            if (isset($data[$key])) {
                $data[$key] = self::download($data[$key], $key);
            }
        }
        // $picdata && $data['picdata'] = self::data($data);
    }
    public static function data($data)
    {
        $picdata = array();
        foreach (self::$FIELDS as $key) {
            $path = $data[$key];
            if ($path) {
                list($width, $height, $type, $attr) = @getimagesize(FilesClient::getRoot($path));
                $picdata[$key] = array('w' => (int)$width, 'h' => (int)$height);
            }
        }
        return $picdata;
    }
    public static function delete($appid, $id)
    {
        $where['appid'] = $appid;
        $where['index_id'] = $id;
        // ArchiveModel::delete($where);
    }
    public static function change($data, $appid, $event, $indexid)
    {
        foreach (self::$FIELDS as $key) {
            $path = $data[$key];
            $path  && Files::change($key, $appid, [$path], $event, $indexid);
        }
    }
    public static function decode($data)
    {
        $array = array();
        if ($data) {
            //兼容6.0
            if (substr($data,0,2)=='a:') {
                $array = unserialize($data);
            } else {
                $array = json_decode($data, true);
            }
        }
        return $array;
    }
    public static function getArray($src, $size = 0, $thumb = 0)
    {
        if (empty($src)) return array();
        if (is_array($src)) return $src;

        if (stripos($src, '://') !== false) {
            return array(
                'src' => $src,
                'url' => $src,
                'width' => 0,
                'height' => 0,
            );
        }

        $data = array(
            'src' => $src,
            'url' => FilesClient::getUrl($src),
        );
        if ($size === true) {
            $path = FilesClient::getRoot($src);
            if (is_file($path)) {
                list($width, $height) = @getimagesize($path);
                $data['width']  = $width;
                $data['height'] = $height;
            }
        }
        if (is_array($size)) {
            $data['width']  = $size['w'];
            $data['height'] = $size['h'];
        }
        if (is_array($size) && is_array($thumb)) {
            $size = array_filter($size);
            $thumb = array_filter($thumb);
            $size && $thumb && $scale = bitscale(array(
                "tw" => (int) $thumb[0],
                "th" => (int) $thumb[1],
                "w"  => (int) $size['w'],
                "h"  => (int) $size['h'],
            ));
            $scale && $data = array_merge($data, $scale);
        }
        return $data;
    }
    public static function findImgUrl($content, &$imgArray = array())
    {
        $content = str_replace("<img", "\n\n<img", $content);
        $pattern = "/<img.*?src\s*=[\"|'|\s]*((http|https):\/\/.*?\.(" . implode('|', FilesPic::$EXTS) . "))[\"|'|\s]*.*?[^>]>/is";
        preg_match_all($pattern, $content, $imgArray);
        $pics = array_unique($imgArray[1]);
        $pics = array_map('trim', $pics);
        return $pics;
    }

    public static function findImg($content, &$match = array())
    {
        if (stripos($content, '<img')) {
            $match   = (array) $match;
            $content = str_replace("<img", "\n\n<img", $content);
            preg_match_all(self::$PREG_IMG, $content, $match);
            return array_unique($match[2]);
        } else {
            //markdown
            preg_match_all('@!\[.*?\]\((.+?)[\)|\s]@i', $content, $match);
            return array_unique($match[1]);
        }
    }
    public static $DELETE_ERROR_PIC = false;

    public static function remote($content, $remote = false, $that = null)
    {
        if (!$remote) return $content;

        FilesClient::$force_ext = "jpg";
        $array   = self::findImg($content, $match);
        $fArray  = array();
        $autopic = array();
        foreach ($array as $key => $value) {
            $value = trim($value);
            if (stripos($value, iCMS_FS_HOST) === false) {
                $filepath = FilesClient::remote($value);
                $rootfilpath = FilesClient::getRoot($filepath);
                list($owidth, $oheight, $otype) = @getimagesize($rootfilpath);
                empty($otype) && $otype = FilesClient::checkImageBin($rootfilpath);

                if ($filepath && !Request::isUrl($filepath) && $otype) {
                    $value = FilesClient::getUrl($filepath);
                } else {
                    if (self::$DELETE_ERROR_PIC) {
                        File::rm($rootfilpath);
                        $array[$key]  = $match[0][$key];
                        $value = '';
                    }
                }
                $fArray[$key] = $value;
            } else {
                unset($array[$key]);
                $rootfilpath = FilesClient::getPath($value, 'http2root');
                list($owidth, $oheight, $otype) = @getimagesize($rootfilpath);
                empty($otype) && $otype = FilesClient::checkImageBin($rootfilpath);

                if (self::$DELETE_ERROR_PIC && empty($otype)) {
                    File::rm($rootfilpath);
                    $array[$key]  = $match[0][$key];
                    $fArray[$key] = '';
                }
            }
            $remote === "autopic" && $autopic[$key] = $value;
        }
        if ($remote === "autopic") {
            return $autopic;
        }
        if ($array && $fArray) {
            krsort($array);
            krsort($fArray);
            $content = str_replace($array, $fArray, $content);
        }
        return $content;
    }
    public static function thumb($sfp, $w = '', $h = '', $scale = true)
    {
        if (empty($sfp)) {
            return iCMS_FS_URL . '1x1.gif';
        }
        if (strpos($sfp, '_') !== false) {
            if (preg_match('|.+\d+x\d+\.jpg$|is', $sfp)) {
                return $sfp;
            }
        }
        if (stripos($sfp, iCMS_FS_HOST) === false) {
            return $sfp;
        }
        $size = $w . 'x' . $h;

        if (Config::get('thumb.size') === 'ALL') {
            return $sfp . '_' . $size . '.jpg';
        }

        if (empty(Config::get('thumb.size'))) {
            return $sfp;
        }

        $size_map = explode("\n", Config::get('thumb.size'));
        $size_map = array_map('trim', $size_map);
        $size_map = array_flip($size_map);
        if (!isset($size_map[$size])) {
            return $sfp;
        }

        if (Config::get('FS.yun.enable')) {
            if (Config::get('FS.yun.vendor.QiNiuYun.Bucket')) {
                return $sfp . '?imageView2/1/w/' . $w . '/h/' . $h;
            }
            if (Config::get('FS.yun.vendor.TencentYun.Bucket')) {
                return $sfp . '?imageView2/2/w/' . $w . '/h/' . $h;
            }
        }
        return $sfp . '_' . $size . '.jpg';
    }
}
