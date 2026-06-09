<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesAdmincp extends AdmincpCommon
{
    public static $orderBy = [
        'id'   => 'ID',
        'size' => '文件大小',
        'ext'  => '后缀值',
    ];
    //权限设置
    public $ACCESC_BASE = [
        'ADD'    => '新增',
        'MANAGE' => '管理',
        'BROWSE' => '浏览',
        'EDIT'   => '编辑',
        'DELETE' => '删除',
    ];
    public $upload_max_filesize = null;
    public $target = null;

    public function __construct()
    {
        parent::__construct();
        $this->target = Request::param('target');
        $this->upload_max_filesize = get_cfg_var('upload_max_filesize');
        // if (Admin::isSuperRole()) {
        //     Files::$userid = false;
        // }
    }

    public function do_test()
    {
        $f = '92/018/9201848ed8aaf3149812ccd778b17eac.jpg';
        Files::rmEmptyDir($f);
        // $filePath  = FilesClient::getRoot($f);
        // var_dump($filePath);
        // // FilesCloud::upload($filePath);
        // FilesCloud::delete($filePath);
    }

    /**
     * 云存储配置
     *
     * @return void
     */
    public function do_cloud_config()
    {
        ConfigView::set('title', '云存储');
        ConfigView::set('file', 'cloud');
        ConfigView::display(self::$APPID, 'cloud');
    }

    /**
     * 保存云存储配置
     * @return void
     */
    public function done_cloud_config()
    {
        $data = Request::post('config');
        $trimMap = function (&$value) {
            $value = trim($value);
        };
        array_walk_recursive($data, $trimMap);
        Config::save(self::$APPID, 'cloud', $data);
        // self::success('保存成功');
    }

    /**
     * 单文件上传页面
     * @return void
     */
    public function do_edit(){
        $this->do_add();
    }
    public function do_add()
    {
        $this->id && $rs = Files::get('id', $this->id);
        $href = '###';
        if ($rs) {
            $href = FilesClient::getUrl($rs['path']);
        }

        include self::view();
    }

    public function do_detail()
    {
        $this->id && $rs = Files::get('id', $this->id);
        empty($rs) && self::alert('文件不存在');
        $url = FilesClient::getUrl($rs['path']);
        $cloudMap = FilesCloud::clients();
        include self::view();
    }

    public function do_editor()
    {
        $this->id && $rs = Files::get('id', $this->id);
        $url = FilesClient::getUrl($rs['path']);
        $arr = [
            'files'  => [$url],
            'server' => [
                'version' => 1,
                'url'     => iCMS_API_URL,
                'formats' => ['psd:true', 'png', 'jpg:0.5']
            ],
            'environment' => []
        ];
        $json = json_encode($arr);
        // $hash = rawurlencode(json_encode($arr));
        //         echo $hash;
        //         echo '<br />';
        //         echo urldecode($hash);
        //         echo '<br />';
        //         echo urldecode('%7B%22files%22:%5B%22https://www.photopea.com/api/img2/pug.png%22%5D,%22environment%22:%7B%7D%7D');
        // exit;
        include self::view();
    }

    /**
     * 批量上传
     * @return void
     */
    public function do_multi()
    {
        $file_upload_limit = Request::get('UN', 100);
        $file_queue_limit = Request::get('QN', 10);
        $file_size_limit = (int) $this->upload_max_filesize;
        $file_size_limit or self::alert("检测到系统环境脚本上传文件大小限制为{$this->upload_max_filesize},请联系管理员");
        stristr($this->upload_max_filesize, 'm') && $file_size_limit = $file_size_limit * 1024;
        include self::view();
    }

    public function do_index()
    {
        $st = Request::get('st');
        $keywords = Request::get('keywords');
        if (!is_null($keywords)) {
            switch ($st) {
                case 'name':
                case 'source':
                case 'path':
                    $keywords && $where[] = [$st, 'REGEXP', $keywords];
                    break;
                case 'userid':
                case 'size':
                case 'ext':
                    $where[] = [$st, '=', $keywords];
                    break;
                default:
                    // code...
                    break;
            }
        }
        $appid = Request::get('appid');
        $indexid = Request::get('indexid');
        if (!is_null($indexid) || ($st == 'indexid' && !is_null($keywords))) {
            !is_null($keywords) && $indexid = (int) $keywords;
            $mWhere[] = ['indexid', $indexid];
            $appid && $mWhere[] = ['appid', $appid];
            $mWhere && $fids = FilesMapModel::field('fileid')->where($mWhere)->pluck();
            $fids && $where[] = ['id', $fids];
        }
        $type = Request::get('type');
        is_numeric($type) && $where[] = ['type', $type];

        $userid = Request::get('userid');
        $userid && $where[] = ['userid', (int)$userid];

        $ext = Request::get('ext');
        $ext && $where[] = ['ext', $ext];

        $starttime = Request::get('starttime');
        $starttime && $where[] = ['time', '>=', str2time($starttime . (strpos($starttime, ' ') !== false ? '' : ' 00:00:00'))];

        $endtime = Request::get('endtime');
        $endtime && $where[] = ['time', '<=', str2time($endtime . (strpos($endtime, ' ') !== false ? '' : ' 00:00:00'))];

        // isset($_GET['type']) && $_GET['type'] != '-1'  && $sql .= " AND `type`='" . (int) $_GET['type'] . "'";

        // $_GET['starttime'] && $sql .= " AND `time`>='" . str2time($_GET['starttime'] . (strpos($_GET['starttime'], ' ') !== false ? '' : " 00:00:00")) . "'";
        // $_GET['endtime']   && $sql .= " AND `time`<='" . str2time($_GET['endtime'] . (strpos($_GET['endtime'], ' ') !== false ? '' : " 23:59:59")) . "'";

        // isset($_GET['userid'])     && $uri .= '&userid=' . (int) $_GET['userid'];

        if (Request::isAjax()) {
            return self::tableData([
                'where' => $where
            ], function ($rows) {
                $rows = array_map(function ($value) {
                    $value['sizeUnit'] = File::sizeUnit($value['size']);
                    $value['url'] = FilesClient::getUrl($value['path']);
                    $value['icon'] = Files::icon($value['path']);
                    $value['iconUrl'] = Files::icon($value['path'],false);
                    $value['fileType'] = Files::getType($value['ext']);
                    // $value['_name_id'] = md5($value['name']);
                    // $value['_name_style'] = 'style';
                    // $value['_name_class'] = 'class';
                    $value['_name_title'] = $value['source'];
                    $value['_ext_title'] = $value['ext'];
                    return $value;
                }, $rows);
                return $rows;
            });
        }

        $widget = ['search' => 1, 'id' => 1, 'uid' => 1, 'index' => 1];
        include self::view();
    }

    /**
     * 流数据上传
     * @return void
     */
    public function done_IO()
    {
        try {
            $udir = Request::get('udir');
            $name = Request::get('name');
            $ext = Request::get('ext');
            FilesMark::$enable = !Request::post('noWatermark');
            !Security::safePath($udir) && self::alert('非法目录名');
            !Security::safePath($name) && self::alert('非法文件名');
            FilesClient::checkExt($ext);

            $file = FilesClient::input($name, $udir, $ext);
            $file['path'] && $url = FilesClient::getUrl($file['path']);
            iJson::display([
                'state'    => 'SUCCESS',
                'url'      => $url,
                'path'     => $file['path'],
                'fid'      => $file['id'],
                'ext'      => $file['ext'],
                'image'    => in_array($file['ext'], FilesPic::$EXTS) ? 1 : 0,
                'original' => $file['source'],
            ]);
        } catch (\Exception $ex) {
            iJson::display([
                'state' => 'ERROR',
                'msg'   => $ex->getMessage()
            ]);
        }
    }

    /**
     * 上传文件
     * @return void
     */
    public function done_upload()
    {
        FilesMark::$enable = !Request::post('noWatermark');
        if ($this->id) {
            FilesClient::$data = Files::get('id', $this->id);
            $file = FilesClient::upload('upfile');
            if ($file && $file['size'] != FilesClient::$data['size']) {
                Files::updateSize($this->id, $file['size']);
            }
        } else {
            $udir = Request::post('udir');
            $udir && $udir = ltrim($udir, '/');
            $file = FilesClient::upload('upfile', $udir);
        }
        empty($file) && self::alert('上传文件出错');
        $file['path'] && $url = FilesClient::getUrl($file['path']);
        $data = [
            'url'      => $url,
            'path'     => $file['path'],
            'value'    => $file['path'],
            'fid'      => $file['id'],
            'ext'      => $file['ext'],
            'name'     => $file['name'],
            'image'    => in_array($file['ext'], FilesPic::$EXTS) ? 1 : 0,
            'original' => $file['source'],
            'target'   => $this->target,
        ];
        return compact('data');
    }

    /**
     * 下载远程图片
     * @return void
     */
    public function done_download()
    {
        Files::$userid = false;
        $rs = Files::get('id', $this->id);

        $filePath = FilesClient::getRoot($rs['path']);
        FilesClient::checkExt($rs['path']) or self::alert('文件类型不合法');
        Files::$userid = Admin::$user_id;
        $data = Http::remote($rs['source']);

        if ($data) {
            FilesMark::$enable = !Request::get('noWatermark');
            Hooks::set('File.put', ['FilesCloud', 'upload']);
            File::put($filePath, $data);
            $size = strlen($data);
            if ($size != $rs['size']) {
                Files::updateSize($this->id, $size);
            }
            return [true, sprintf(
                '%s 》<br /> %s  <br />重新下载完成',
                $rs['source'],
                $rs['path']
            )];
        } else {
            self::alert('下载远程文件失败');
        }
    }

    /**
     * 删除文件
     *
     * @param [type] $path
     * @return void
     */
    public function done_rm($path = null)
    {
        $path === null && $path = Request::post('path');
        $path or self::alert('请选择要删除的文件');
        $fsInfo = File::info($path);
        $filename = $fsInfo->filename;
        $dirname = $fsInfo->dirname;
        $extension = $fsInfo->extension;
        $row = Files::get('name', $filename);
        return false;
        if ($row) {
            $this->__deleting([$row['id']]);
        } else {
            if (Admin::isSuperAdmin()) {
                $path = FilesClient::getRoot($path);
                File::rm($path);
            } else {
                return false;
            }
        }
    }
    public function done_delete(){
        $this->ids or self::alert('请选择要删除的文件');
        $this->__deleting($this->ids);
    }                
    public function __deleting($ids = [])
    {
        if ($ids) {
            foreach ($ids as $key => $id) {
                Files::deleteFile($id);
            }
        }
        return true;
    }

    public function getTemplatePreview($dir, &$preview)
    {
        empty($preview) && $preview = 'preview.jpg';
        $preview = sprintf('./template/%s/%s', $dir, $preview);
        $path = iPHP_TEMPLATE_DIR . $preview;
        if (!is_file($path)) {
            $preview = '/assets/nopreview.jpg';
        }
    }

    public function do_select_template()
    {
        $files = glob(iPHP_TEMPLATE_DIR . '/*/package.json');
        if ($files) {
            foreach ($files as $key => $path) {
                $dir = str_replace([iPHP_TEMPLATE_DIR, '/', 'package.json'], '', $path);
                $json = File::get($path);
                $package = json_decode($json, true);
                if ($package) {
                    $this->getTemplatePreview($dir, $package['preview']);
                    $paths = [];
                    if ($package['templates']) {
                        foreach ($package['templates'] as $tkey => $value) {
                            $tdir = sprintf('%s/%s', $dir, $tkey);
                            $this->getTemplatePreview($tdir, $value['preview']);
                            $value['path'] = $tdir;
                            $package['templates'][$tkey] = $value;
                            $paths[] = $tdir;
                        }
                    }
                    $package['path'] = $paths ?: [$dir];
                    $result[$dir] = $package;
                }
            }
        }
        include self::view();
    }

    /**
     * 图片编辑器
     * @return void
     */
    public function do_editpic()
    {
        $pic = Request::get('pic');
        //$pic OR self::alert("请选择图片");
        if ($pic) {
            $src = FilesClient::getUrl($pic) . '?' . time();
            $srcPath = FilesClient::getRoot($pic);
            $fsInfo = File::info($pic);
            $file_name = $fsInfo->filename;
            $file_path = $fsInfo->dirname;
            $file_ext = $fsInfo->extension;
            $file_id = 0;
            $rs = Files::get('name', $file_name);
            if ($rs) {
                $file_path = $rs['path'];
                $file_id = $rs['id'];
                $file_ext = $rs['ext'];
            }
        } else {
            $file_name = md5(uniqid());
            $src = false;
            $file_ext = 'jpg';
        }
        if ($indexid = (int) Request::get('indexid')) {
            $fileids = FilesMapModel::field('fileid')->where(compact('indexid'))->pluck();
            $rs = FilesModel::where($fileids)->limit(100)->select();
            foreach ((array) $rs as $key => $value) {
                $filepath = $value['path'] . $value['name'] . '.' . $value['ext'];
                $src[] = FilesClient::getUrl($filepath) . '?' . time();
            }
        }
        if ($pics = Request::get('pics')) {
            $src = explode(',', $pics);
            if (count($src) == 1) {
                $src = $pics;
            }
        }
        $max_size = (int) $this->upload_max_filesize;
        stristr($this->upload_max_filesize, 'm') && $max_size = $max_size * 1024 * 1024;
        include self::view();
    }

    /**
     * 预览
     * @return void
     */
    public function do_preview()
    {
        Request::get('pic') && $src = FilesClient::getUrl(Request::get('pic'));
        include self::view();
    }

    public static function saveCall_config_file($data = [])
    {
        FilesClient::checkConf($data);
        
        $data['url'] = trim($data['url'], '/') . '/';
        return $data;
    }

    public static function widget_count()
    {
        $total = FilesModel::count();
        $widget[] = [$total, '全部'];
        return $widget;
    }
}
