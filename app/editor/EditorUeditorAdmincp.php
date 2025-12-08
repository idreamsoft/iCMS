<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
/**
 * Ueditor编辑器
 */
class EditorUeditorAdmincp extends AdmincpBase
{
    /**
     * 获取编辑器配置
     *
     * @auth no
     * @return json
     */
    public function do_config()
    {
        $upload_max_filesize = get_cfg_var('upload_max_filesize');
        $maxSize = get_bytes($upload_max_filesize);
        empty($maxSize) && $maxSize = 2097152;
        $json = file_get_contents(__DIR__ . '/assets/ueditor/admincp/config.json');
        $json = preg_replace('@/\*.*\*/@', '', $json);
        $array = json_decode($json, true);

        $array['imageMaxSize'] = $maxSize;
        $array['scrawlMaxSize'] = $maxSize;
        $array['catcherMaxSize'] = $maxSize;
        $array['videoMaxSize'] = $maxSize;
        $array['fileMaxSize'] = $maxSize;

        $exts = array_map(function ($ext) {
            return '.' . $ext;
        }, FilesPic::$EXTS);

        $array['imageAllowFiles'] = $exts;
        $array['catcherAllowFiles'] = $exts;
        $array['fileAllowFiles'] += $exts;
        $array['imageManagerAllowFiles'] = $exts;
        $array['fileManagerAllowFiles'] += $exts;
        $driverConfig = Config::data(self::$APPID, 'ueditor.ai');
        if ($driverConfig) {
            $array['ai'] = [
                'driver'      => 'OpenAi',
                'driverConfig'=> $driverConfig
            ];
        }
        iJson::display($array);
    }

    /**
     * 编辑器图片管理
     *
     * GET {"action": "listimage", "start": 0, "size": 20}
     *
     * @return Json
     */
    public function do_imageManager()
    {
        $result = FilesClient::folder();
        $result['public'] = iCMS_PUBLIC_URL;
        // return $result;
        iJson::display($result);
    }

    /**
     * 编辑器文件管理
     *
     * @auth no
     * @return json
     */
    public function do_fileManager()
    {
        $result = FilesClient::folder();
        $result['public'] = iCMS_PUBLIC_URL;
        // return $result;
        iJson::display($result);
    }

    /**
     * 编辑器抓取远程图片
     *
     * GET {
     *     "action": "catchimage",
     *      "source": [
     *      	"http://a.com/1.jpg",
     *         "http://a.com/2.jpg"
     *     ]
     * }
     *
     * @return json
     *
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
    public function do_catchimage()
    {
        $url_array = (array) Request::post('source');
        /* 抓取远程图片 */
        $list = [];
        foreach ($url_array as $_k => $imgurl) {
            if (stripos($imgurl, iCMS_FILE_HOST) !== false) {
                unset($_array[$_k]);
            }
            try {
                $ret = [];
                FilesClient::remote($imgurl, $ret);
                $ret['path'] && $url = FilesClient::getUrl($ret['path']);
                $a = [
                    'state'    => 'SUCCESS',
                    'url'      => $url,
                    'size'     => $ret['size'],
                    'title'    => '',
                    'original' => Security::escapeStr($ret['source']),
                    'source'   => Security::escapeStr($imgurl)
                ];
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
     * 编辑器上传图片
     *
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
    public function done_uploadimage()
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
            $msg = $ex->getMessage();
            //Request::post('name') &&
            iJson::display(['state' => $msg]);
            // iPHP::alert($msg);
        }
    }

    /**
     * 编辑器上传文件
     *
     * GET {"action": "uploadfile"}
     * POST "upfile": File Data
     * @return Json
     * {
     *     "state": "SUCCESS",
     *     "url": "upload/demo.zip",
     *     "title": "demo.zip",
     *     "original": "demo.zip"
     * }     */
    public function done_uploadfile()
    {
        return $this->do_uploadfile();
    }
    public function do_uploadfile()
    {
        try {
            $file = FilesClient::upload('upfile');
            $file['path'] && $url = FilesClient::getUrl($file['path']);
            iJson::display([
                'state'    => 'SUCCESS',
                'url'      => $url,
                'path'     => $file['path'],
                'fid'      => $file['id'],
                'ext'      => $file['ext'],
                'original' => $file['source'],
            ]);
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            Request::post('name') && iJson::display(['state' => $msg]);
            iPHP::alert($msg);
        }
    }

    /**
     * 编辑器上传视频
     *
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
    public function done_uploadvideo(){
        return $this->do_uploadvideo();
    }
    public function do_uploadvideo()
    {
        try {
            $file = FilesClient::upload('upfile');
            $file['path'] && $url = FilesClient::getUrl($file['path']);
            iJson::display([
                'state'    => 'SUCCESS',
                'url'      => $url,
                'path'     => $file['path'],
                'fid'      => $file['id'],
                'fileType' => $file['ext'],
                'original' => $file['source'],
            ]);
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            Request::post('name') && iJson::display(['state' => $msg]);
            iPHP::alert($msg);
        }
    }
    public function done_uploadaudio(){
        return $this->do_uploadaudio();
    }
    public function do_uploadaudio()
    {
        try {
            $file = FilesClient::upload('upfile');
            $file['path'] && $url = FilesClient::getUrl($file['path']);
            iJson::display([
                'state'    => 'SUCCESS',
                'url'      => $url,
                'path'     => $file['path'],
                'fid'      => $file['id'],
                'fileType' => $file['ext'],
                'original' => $file['source'],
            ]);
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            Request::post('name') && iJson::display(['state' => $msg]);
            iPHP::alert($msg);
        }
    }

    /**
     * 编辑器上传涂鸦
     * GET {"action": "uploadscrawl"}
     * POST "content": Base64 Data
     * @return Json {
     *     "state": "SUCCESS",
     *     "url": "upload/demo.jpg",
     *     "title": "demo.jpg",
     *     "original": "demo.jpg"
     * }
     */
    public function done_uploadscrawl()
    {
        $tmpDir = 'scrawl/tmp';
        if (Request::sget('action') == 'tmpImg') { // 背景上传
            try {
                $file = FilesClient::upload('upfile', $tmpDir);
                $file['path'] && $url = FilesClient::getUrl($file['path']);
                echo "<script>parent.ue_callback('" . $url . "','SUCCESS')</script>";
            } catch (\Exception $ex) {
                $msg = $ex->getMessage();
                Request::post('name') && iJson::display(['state' => $msg]);
                iPHP::alert($msg);
            }
        } else {
            try {
                $dir = 'scrawl/' . get_date(0, 'Y/md');
                $file = FilesClient::base64ToFile(Request::post('upfile'), $dir);
                $file['path'] && $url = FilesClient::getUrl($file['path']);
                $tmp = FilesClient::getDir() . $tmpDir;
                file_exists($tmp) && File::rmdir($tmp);
                iJson::display([
                    'url'   => $url,
                    'state' => 'SUCCESS'
                ]);
            } catch (\Exception $ex) {
                $msg = $ex->getMessage();
                Request::post('name') && iJson::display(['state' => $msg]);
                iPHP::alert($msg);
            }
        }
    }
}
