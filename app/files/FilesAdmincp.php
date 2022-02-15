<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesAdmincp extends AdmincpBase
{
    //权限设置
    public $ACCESC_BASE = array(
        'ADD' => '新增',
        'MANAGE' => '管理',
        'BROWSE' => '浏览',
        'EDIT' => '编辑',
        'DELETE' => '删除',
    );

    public function __construct()
    {
        parent::__construct();
        $this->callback = Request::sparam('callback');
        $this->click    = Request::param('click');
        $this->target   = Request::param('target');
        $this->format   = Request::param('format');
        $this->id       = (int) Request::param('id');
        $this->upload_max_filesize = get_cfg_var("upload_max_filesize");
        $this->callback or $this->callback  = 'callback';
    }
    public function do_cloud_config()
    {
        if(Request::isPost()){
            return $this->save_cloud_config();
        }
        Config::$setting['title'] = '云存储';
        Config::$setting['file']  = 'cloud.config';
        Config::app(self::$appId, 'cloud');
    }
    /**
     * [保存云存储配置]
     * @return [type] [description]
     */
    public function save_cloud_config()
    {
        Config::$data = Request::post('config');
        $trimMap = function (&$value) {
            $value = trim($value);
        };
        array_walk_recursive(Config::$data, $trimMap);
        Config::save(self::$appId, 'cloud');
        // self::success('保存成功');
    }
    /**
     * [单文件上传页面]
     * @return [type] [description]
     */
    public function do_add()
    {
        $this->id && $rs = Files::get('id', $this->id);
        $href = '###';
        if ($rs) {
            $href = FilesClient::getUrl($rs['path']);
        }

        include self::view("files.add");
    }
    /**
     * [批量上传]
     * @return [type] [description]
     */
    public function do_multi()
    {
        $file_upload_limit  = Request::get('UN', 100);
        $file_queue_limit   = Request::get('QN', 10);
        $file_size_limit    = (int) $this->upload_max_filesize;
        $file_size_limit or self::alert("检测到系统环境脚本上传文件大小限制为{$this->upload_max_filesize},请联系管理员");
        stristr($this->upload_max_filesize, 'm') && $file_size_limit    = $file_size_limit * 1024;
        include self::view("files.multi");
    }

    public function do_manage()
    {
        // $sql = 'WHERE 1=1 ';
        $st = Request::get('st');
        $keywords = Request::get('keywords');
        if (!is_null($keywords)) {
            switch ($st) {
                case 'name':
                case 'source':
                case 'path':
                    $keywords && $where[] = array($st, 'REGEXP', $keywords);
                    break;
                case 'userid':
                case 'size':
                case 'ext':
                    $where[] = array($st, '=', $keywords);
                    break;
                default:
                    # code...
                    break;
            }
        }
        $appid = Request::get('appid');
        $indexid = Request::get('indexid');
        if (!is_null($indexid) || ($st == "indexid" && !is_null($keywords))) {
            !is_null($keywords) && $indexid = (int) $keywords;
            $mWhere[] = array('indexid', $indexid);
            $appid && $mWhere[] = array('appid', $appid);
            $mWhere && $fids = FilesMapModel::field('fileid')->where($mWhere)->pluck();
            $fids && $where[] = array('id', $fids);
        }
        $type = Request::get('type');
        is_numeric($type) && $where[] = array('type', $type);

        $starttime = Request::get('starttime');
        $starttime && $where[] = array('time', '>=', str2time($starttime . (strpos($starttime, ' ') !== false ? '' : " 00:00:00")));

        $endtime = Request::get('endtime');
        $endtime && $where[] = array('time', '<=', str2time($endtime . (strpos($endtime, ' ') !== false ? '' : " 00:00:00")));

        // isset($_GET['type']) && $_GET['type'] != '-1'  && $sql .= " AND `type`='" . (int) $_GET['type'] . "'";

        // $_GET['starttime'] && $sql .= " AND `time`>='" . str2time($_GET['starttime'] . (strpos($_GET['starttime'], ' ') !== false ? '' : " 00:00:00")) . "'";
        // $_GET['endtime']   && $sql .= " AND `time`<='" . str2time($_GET['endtime'] . (strpos($_GET['endtime'], ' ') !== false ? '' : " 23:59:59")) . "'";

        // $userid = Request::get('userid');
        // $userid && $where[] = array('userid',(int)$userid);

        // isset($_GET['userid'])     && $uri .= '&userid=' . (int) $_GET['userid'];

        $orderby = self::setOrderBy(array(
            'id'   => "ID",
            'size' => "文件大小",
            'ext'  => "后缀值",
        ));

        $result = FilesModel::where($where)
            ->orderBy($orderby)
            ->paging();

        $widget = array('search' => 1, 'id' => 1, 'uid' => 1, 'index' => 1);
        include self::view("files.manage");
    }
    /**
     * [流数据上传]
     * @return [type] [description]
     */
    public function do_IO()
    {
        try {
            $udir      = Request::get('udir');
            $name      = Request::get('name');
            $ext       = Request::get('ext');
            FilesMark::$enable = !Request::post('noWatermark');
            strpos($udir, '..') !== false && self::alert('非法目录名');
            strpos($name, '..') !== false && self::alert('非法文件名');
            FilesClient::checkExt($ext);

            $file = FilesClient::input($name, $udir, $ext);
            $file['path'] && $url = FilesClient::getUrl($file['path']);
            iJson::display([
                "state"    => 'SUCCESS',
                "url"      => $url,
                "path"     => $file["path"],
                "fid"      => $file["id"],
                "ext"      => $file["ext"],
                "image"    => in_array($file["ext"], FilesPic::$EXTS) ? 1 : 0,
                "original" => $file["source"],
            ]);
        } catch (\Exception $ex) {
            iJson::display([
                "state" => 'ERROR',
                "msg" => $ex->getMessage()
            ]);
        }
    }
    /**
     * [上传文件]
     * @return [type] [description]
     */
    public function do_upload()
    {
        Request::param('callback') && iJson::$jsonp = true;
        try {
            FilesMark::$enable = !Request::post('noWatermark');
            if ($this->id) {
                FilesClient::$data = Files::get('id', $this->id);
                $file = FilesClient::upload('upfile');
                if ($file && $file['size'] != FilesClient::$data['size']) {
                    Files::updateSize($this->id, $file['size']);
                }
            } else {
                $udir = ltrim(Request::post('udir'), '/');
                $file = FilesClient::upload('upfile', $udir);
            }

            $file['path'] && $url = FilesClient::getUrl($file['path']);
            $result = [
                "url"      => $url,
                "path"     => $file["path"],
                "value"    => $file["path"],
                "fid"      => $file["id"],
                "ext"      => $file["ext"],
                "image"    => in_array($file["ext"], FilesPic::$EXTS) ? 1 : 0,
                "original" => $file["source"],
                "target" => $this->target,
            ];
            DB::commit();
            iJson::success($result);
        } catch (\Exception $ex) {
            DB::rollBack();
            iJson::error($ex->getMessage());
        }
    }
    /**
     * [下载远程图片]
     * @return [type] [description]
     */
    public function do_download()
    {
        Files::$userid = false;
        $rs = Files::get('id', $this->id);
        $filePath  = FilesClient::getRoot($rs['path']);
        FilesClient::checkExt($rs['path']) or self::alert('文件类型不合法');
        Files::$userid = Member::$user_id;
        $data = Http::remote($rs['source']);

        if ($data) {
            FilesMark::$enable = !Request::post('noWatermark');
            Hooks::set('File.put', ['FilesCloud', 'upload']);
            File::put($filePath, $data);
            $size = strlen($data);
            if ($size != $rs['size']) {
                Files::updateSize($this->id, $size);
            }
            return [true, sprintf(
                "%s 》<br /> %s  <br />重新下载完成",
                $rs['source'],
                $rs['path']
            )];
        } else {
            self::alert("下载远程文件失败");
        }
    }
    public function do_batch()
    {
        $stype = AdmincpBatch::$config['stype'];
        $actions = array(
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    $this->do_delete($id);
                }
                // self::success('文件删除完成');
            },
        );
        return AdmincpBatch::run($actions, "文件");
    }
    public function do_delete($id = null)
    {
        $id === null && $id = $this->id;
        $id or self::alert("请选择要删除的文件");
        Files::deleteFile($id);
        Files::deleteData($id);
        // self::success('文件删除完成');
    }
    /**
     * [创建目录]
     * @return [type] [description]
     */
    public function do_mkdir()
    {
        $name    = Request::post('name');
        strstr($name, '.') !== false    && self::alert('您输入的目录名称有问题');
        strstr($name, '..') !== false    && self::alert('您输入的目录名称有问题');
        $pwd    = trim(Request::post('pwd'), '/');
        $dir    = File::pathMerge(iPHP_PATH, Config::get('FS.dir'));
        $dir    = File::pathMerge($dir, $pwd);
        $dir    = File::pathMerge($dir, $name);
        file_exists($dir) && self::alert('您输入的目录名称已存在,请重新输入');
        if (File::mkdir($dir)) {
            return true;
            // self::success('创建成功');
        }
        self::alert('创建失败,请检查目录权限');
    }

    public function getTemplatePreview($dir, &$preview)
    {
        empty($preview) && $preview = 'preview.jpg';
        $preview = sprintf('./template/%s/%s', $dir, $preview);
        $path = iPHP_TPL_DIR . $preview;
        if (!is_file($path)) {
            $preview = './assets/nopreview.jpg';
        }
    }
    public function do_select_template()
    {
        $files = glob(iPHP_TPL_DIR . '/*/package.json');
        if ($files) foreach ($files as $key => $path) {
            $dir = str_replace(array(iPHP_TPL_DIR, '/', 'package.json'), '', $path);
            $json = File::get($path);
            $package = json_decode($json, true);
            if ($package) {
                $this->getTemplatePreview($dir, $package['preview']);
                $paths = [];
                if ($package['templates']) foreach ($package['templates'] as $tkey => $value) {
                    $tdir = sprintf('%s/%s', $dir, $tkey);
                    $this->getTemplatePreview($tdir, $value['preview']);
                    $value['path'] = $tdir;
                    $package['templates'][$tkey] = $value;
                    $paths[] = $tdir;
                }
                $package['path'] = $paths ?: [$dir];
                $result[$dir] = $package;
            }
        }
        include self::view("files.template");
    }
    /**
     * [选择模板文件页]
     * @return [type] [description]
     */
    public function do_seltpl()
    {
        $this->explorer('template');
    }
    /**
     * [浏览文件]
     * @return [type] [description]
     */
    public function do_browse()
    {
        $this->explorer(Config::get('FS.dir'));
    }
    /**
     * [浏览图片]
     * @return [type] [description]
     */
    public function do_picture()
    {
        $this->explorer(Config::get('FS.dir'), FilesPic::$EXTS);
    }
    /**
     * [图片编辑器]
     * @return [type] [description]
     */
    public function do_editpic()
    {
        $pic = Request::get('pic');
        //$pic OR self::alert("请选择图片");
        if ($pic) {
            $src       = FilesClient::getUrl($pic) . "?" . time();
            $srcPath   = FilesClient::getRoot($pic);
            $fsInfo    = File::info($pic);
            $file_name = $fsInfo->name;
            $file_path = $fsInfo->dirname;
            $file_ext  = $fsInfo->extension;
            $file_id   = 0;
            $rs        = Files::get('name', $file_name);
            if ($rs) {
                $file_path = $rs->path;
                $file_id   = $rs->id;
                $file_ext  = $rs->ext;
            }
        } else {
            $file_name = md5(uniqid());
            $src      = false;
            $file_ext = 'jpg';
        }
        if ($indexid = (int) Request::get('indexid')) {
            $fileids = FilesMapModel::field('fileid')->where(compact('indexid'))->pluck();
            $rs = FilesModel::where($fileids)->limit(100)->select();
            foreach ((array) $rs as $key => $value) {
                $filepath = $value['path'] . $value['name'] . '.' . $value['ext'];
                $src[] = FilesClient::getUrl($filepath) . "?" . time();
            }
        }
        if ($pics = Request::get('pics')) {
            $src = explode(',', $pics);
            if (count($src) == 1) {
                $src = $pics;
            }
        }
        $max_size  = (int) $this->upload_max_filesize;
        stristr($this->upload_max_filesize, 'm') && $max_size = $max_size * 1024 * 1024;
        include self::view("files.editpic");
    }
    /**
     * [预览]
     * @return [type] [description]
     */
    public function do_preview()
    {
        Request::get('pic') && $src = FilesClient::getUrl(Request::get('pic'));
        include self::view("files.preview");
    }
    /**
     * [删除目录]
     * @return [type] [description]
     */
    public function do_deldir()
    {
        Request::get('path') or self::alert("请选择要删除的目录");
        strpos(Request::get('path'), '..') !== false && self::alert("目录路径中带有..");

        $hash = md5(Request::get('path'));
        $dirRootPath = FilesClient::getRoot(Request::get('path'));
        File::rmdir($dirRootPath);
        // self::success('目录删除完成');
    }
    /**
     * [删除文件]
     * @return [type] [description]
     */
    public function do_delfile()
    {
        Request::get('path') or self::alert("请选择要删除的文件");
        strpos(Request::get('path'), '..') !== false && self::alert("文件路径中带有..");

        $hash         = md5(Request::get('path'));
        $FileRootPath = FilesClient::getRoot(Request::get('path'));
        File::rm($FileRootPath);
        // self::success('文件删除完成');
    }
    public function explorer($dir = NULL, $type = NULL)
    {
        $res    = FilesClient::folder($dir, $type);
        $dirRs  = $res['DirArray'];
        $fileRs = $res['FileArray'];
        $pwd    = $res['pwd'];
        $parent = $res['parent'];
        $URI    = $res['URI'];
        $navbar = false;
        include self::view("files.explorer");
    }
    public static function widget_count()
    {
        $total = FilesModel::count();
        $widget[] = array($total, '全部');
        return $widget;
    }
}
