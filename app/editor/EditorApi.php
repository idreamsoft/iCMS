<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class EditorApi
{
    public function config()
    {
        $upload_max_filesize = get_cfg_var('upload_max_filesize');
        $maxSize = get_bytes($upload_max_filesize);
        empty($maxSize) && $maxSize = 2097152;
        $json = file_get_contents(__DIR__ . '/assets/ueditor.config.json');
        $json = str_replace(
            ['@maxSize', '@imageExts'],
            [$maxSize, '.' . implode('", ".', FilesPic::$EXTS)],
            $json
        );
        $json = preg_replace("/\/\*[\s\S]+?\*\//", "", $json, true);

        if ($callback = Request::sget('callback')) {
            printf('%s(%s)', $callback, $json);
        } else {
            echo $json;
        }
    }
    /**
     * [编辑器图片管理]
     * GET {"action": "listimage", "start": 0, "size": 20}
     * @return Json
     */
    public function imageManager()
    {
        $result = FilesClient::folder(Config::get('FS.dir'), FilesPic::$EXTS);
        $result['public'] = iCMS_PUBLIC_URL;
        iJson::display($result);
    }
    /**
     * [编辑器文件管理]
     * @return [type] [description]
     */
    public function fileManager()
    {
        $result = FilesClient::folder(Config::get('FS.dir'));
        $result['public'] = iCMS_PUBLIC_URL;
        iJson::display($result);
    }
    /**
     * [编辑器抓取远程图片]
     * GET {
     *     "action": "catchimage",
     *      "source": [
     *      	"http://a.com/1.jpg",
     *         "http://a.com/2.jpg"
     *     ]
     * }
     * @return jsonp
     * 需要支持callback参数,返回jsonp格式
     * list项的state属性和最外面的state格式一致
     *{
     *    "state": "SUCCESS",
     *    "list": [{
     *        "url": "upload/1.jpg",
     *        "source": "http://b.com/2.jpg",
     *        "state": "SUCCESS"
     *    }, {
     *        "url": "upload/2.jpg",
     *        "source": "http://b.com/2.jpg",
     *        "state": "SUCCESS"
     *    }, ]
     *}
     */
    public function catchimage()
    {
        $url_array = (array) Request::post('source');
        /* 抓取远程图片 */
        $list = array();
        foreach ($url_array as $_k => $imgurl) {
            if (stripos($imgurl, iCMS_FS_HOST) !== false) {
                unset($_array[$_k]);
            }
            try {
                $ret = array();
                FilesClient::remote($imgurl, $ret);
                $ret['path'] && $url = FilesClient::getUrl($ret['path']);
                $a = array(
                    "state"    => 'SUCCESS',
                    "url"      => $url,
                    "size"     => $ret["size"],
                    "title"    => '',
                    "original" => Security::escapeStr($ret["source"]),
                    "source"   => Security::escapeStr($imgurl)
                );
                array_push($list, $a);
            } catch (\Exception $ex) {
                // throw $ex;
            }
        }
        /* 返回抓取数据 */
        iJson::display([
            'code'  => count($list) ? '1' : '0',
            'state' => count($list) ? 'SUCCESS' : 'ERROR',
            'list'  => $list
        ]);
    }
    /**
     * [编辑器上传图片]
     * GET {"action": "uploadimage"}
     * POST "upfile": File Data
     * @return Json 
     * {
     *     "state": "SUCCESS",
     *     "url": "upload/demo.jpg",
     *     "title": "demo.jpg",
     *     "original": "demo.jpg"
     * }
     */
    public function uploadimage()
    {
        try {
            $file = FilesClient::upload('upfile');
            $file['path'] && $url = FilesClient::getUrl($file['path']);
            iJson::display([
                'state'    => 'SUCCESS',
                'url'      => $url,
                'original' => $file['source'],
                'title'    => Request::post('pictitle'),
            ]);
        } catch (\Exception $ex) {
            throw $ex;
            $msg = $ex->getMessage();
            //Request::post('name') && 
            iJson::display(['state' => $msg]);
            // self::alert($msg);
        }
    }

    /**
     * [编辑器上传文件]
     * GET {"action": "uploadfile"}
     * POST "upfile": File Data
     * @return Json 
     * {
     *     "state": "SUCCESS",
     *     "url": "upload/demo.zip",
     *     "title": "demo.zip",
     *     "original": "demo.zip"
     * }     */
    public function uploadfile()
    {
        try {
            $file = FilesClient::upload('upfile');
            $file['path'] && $url = FilesClient::getUrl($file['path']);
            iJson::display([
                "state"    => 'SUCCESS',
                "url"      => $url,
                "path"     => $file["path"],
                "fid"      => $file["id"],
                "ext"      => $file["ext"],
                "original" => $file["source"],
            ]);
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            Request::post('name') && iJson::display(['state' => $msg]);
            self::alert($msg);
        }
    }
    /**
     * [编辑器上传视频]
     * GET {"action": "uploadvideo"}
     * POST "upfile": File Data
     * @return Json 
     * {
     *     "state": "SUCCESS",
     *     "url": "upload/demo.mp4",
     *     "title": "demo.mp4",
     *     "original": "demo.mp4"
     * }
     */
    public function uploadvideo()
    {
        try {
            $file = FilesClient::upload('upfile');
            $file['path'] && $url = FilesClient::getUrl($file['path']);
            iJson::display([
                "state"    => 'SUCCESS',
                "url"      => $url,
                "path"     => $file["path"],
                "fid"      => $file["id"],
                "fileType" => $file["ext"],
                "original" => $file["source"],
            ]);
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            Request::post('name') && iJson::display(['state' => $msg]);
            self::alert($msg);
        }
    }
    /**
     * [编辑器上传涂鸦]
     * GET {"action": "uploadscrawl"}
     * POST "content": Base64 Data
     * @return Json {
     *     "state": "SUCCESS",
     *     "url": "upload/demo.jpg",
     *     "title": "demo.jpg",
     *     "original": "demo.jpg"
     * }
     */
    public function uploadscrawl()
    {
        $tmpDir = 'scrawl/tmp';
        if (Request::sget('action') == "tmpImg") { // 背景上传
            try {
                $file = FilesClient::upload('upfile', $tmpDir);
                $file['path'] && $url = FilesClient::getUrl($file['path']);
                echo "<script>parent.ue_callback('" . $url . "','SUCCESS')</script>";
            } catch (\Exception $ex) {
                $msg = $ex->getMessage();
                Request::post('name') && iJson::display(['state' => $msg]);
                self::alert($msg);
            }
        } else {
            try {
                $dir = 'scrawl/' . get_date(0, 'Y/md');
                $file = FilesClient::base64ToFile(Request::post('upfile'), $dir);
                $file['path'] && $url = FilesClient::getUrl($file['path']);
                $tmp = FilesClient::getDir() . $tmpDir;
                File::rmdir($tmp);
                iJson::display([
                    "url"   => $url,
                    "state" => 'SUCCESS'
                ]);
            } catch (\Exception $ex) {
                $msg = $ex->getMessage();
                Request::post('name') && iJson::display(['state' => $msg]);
                self::alert($msg);
            }
        }
    }
    /**
     * [markdown上传图片]
     * @return [type] [description]
     */
    public function md_uploadimage()
    {
        try {
            $file = FilesClient::upload('editormd-image-file');
            $file['path'] && $url = FilesClient::getUrl($file['path']);
            $result = [
                'success'    => 1,
                'message'  => '上传成功',
                'url'      => $url,
            ];
        } catch (\Exception $ex) {
            $result = array(
                'message'  => $ex->getMessage(),
                'success'  => 0
            );
        }
        iJson::display($result);
    }
}
