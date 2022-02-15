<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ContentAdmincp extends AdmincpCommon
{
    public static $app = null;
    public static $title  = null;
    public $callback  = array();

    protected $VIEW_ADD     = 'add';
    protected $VIEW_MANAGE  = 'manage';
    protected $VIEW_DIR = null;

    public function __construct()
    {
        parent::__construct();
        $this->init();
        self::$MODEL = "Content";
    }
    public function init()
    {
        self::$appId     = Admincp::$APPID;
        self::$app       = Admincp::$APP_DATA['app'];
        self::$title     = Admincp::$APP_DATA['title'];

        $this->id        = (int) $_GET['id'];
        $this->dataid    = (int) $_GET['dataid'];
        $this->_postype  = '1';
        $this->_status   = '1';
        Node::$APPID     = self::$appId;

        Content::model(Admincp::$APP_DATA);
        if (Admincp::$APP_DATA['config']['nodeTable']) {
            NodeModel::setTable(self::$app);
        }
        Former::$prefix = self::$app . 'Data';
    }
    public function setViewDir($dir)
    {
        $this->VIEW_DIR = $dir;
    }
    public function do_add()
    {
        $this->id && $rs = Content::data($this->id);
        isset($rs['status']) or $rs['status'] = '1';
        if (empty($rs['cid']) && isset($_GET['cid'])) {
            $rs['cid'] = (int) $_GET['cid'];
        }

        self::added($this, __METHOD__, $rs);
        include self::view($this->VIEW_ADD, $this->VIEW_DIR);
    }
    public function do_update_sort()
    {
        foreach ((array) $_POST['sortnum'] as $sortnum => $id) {
            Content::update(compact('sortnum'), compact('id'));
        }
    }
    public function do_batch()
    {
        AdmincpBatch::$config['etc.app'] = 'content';
        $actions = array(
            'move' => function ($idArray, $ids, $batch) {
                $cid = (int) $_POST['cid'];
                $cid or self::alert("请选择目标栏目");
                NodeAccess::check($cid, 'ca');
                Content::update(compact('cid'), $idArray);
                return true;
            },
            'prop' => function ($idArray, $ids, $batch) {
                $pid = (array) $_POST['pid'];
                Content::update(compact('pid'), $idArray);
                return true;
            },
            'keyword' => function ($idArray, $ids, $batch) {
                $pattern = Request::post('pattern');
                if ($pattern == 'replace') {
                    $data = array('keywords' => Request::post('bkeyword'));
                    $data && Content::update($data, $idArray);
                } elseif ($pattern == 'addto') {
                    foreach ($idArray as $id) {
                        $keywords = Content::value('keywords', $id);
                        $keywords = ($keywords ? $keywords . ',' : '') . Request::post('bkeyword');
                        Content::update(compact('keywords'), compact('id'));
                    }
                }
                return true;
            },
            'tag' => function ($idArray, $ids, $batch) {
                $pattern = Request::post('pattern');
                $mtag = Request::post('btag');
                foreach ($idArray as $id) {
                    $art  = Content::get($id, 'tags,cid');
                    $tagArray  = $art['tags'] ? explode(',', $art['tags']) : array();
                    $mtagArray = explode(',', $mtag);
                    if ($pattern == 'replace') {
                    } elseif ($pattern == 'delete') {
                        foreach ($mtagArray as $key => $value) {
                            $tk = array_search($value, $tagArray);
                            if ($tk !== false) {
                                unset($tagArray[$tk]);
                            }
                        }
                        $mtag   = implode(',', $tagArray);
                    } elseif ($pattern == 'addto') {
                        $pieces = array_merge($tagArray, $mtagArray);
                        $pieces = array_unique($pieces);
                        $mtag   = implode(',', $pieces);
                    }
                    $mtag = ltrim($mtag, ',');
                    Content::update(compact('tags'), compact('id'));
                }
                return true;
            },
            'meta' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    AppsMeta::save(self::$appId, $id);
                }
                return true;
            },
            'quick_dels' => function ($idArray, $ids, $batch) {
                $_count = count($idArray);
                foreach ($idArray as $i => $id) {
                    $this->remove($id);
                }
                return true;
            },
            'dels' => function ($idArray, $ids, $batch) {
                $_count = count($idArray);
                foreach ((array) $idArray as $i => $id) {
                    $msg = $this->remove($id);
                }
                return true;
            },
            'default' => function ($idArray, $ids, $batch, $data = null) {
                $data === null && $data = Request::args($batch);
                $data && Content::update($data, $idArray);
                return true;
            },
        );
        return AdmincpBatch::run($actions, self::$title);
    }
    public function do_check()
    {
        $id    = (int) Request::get('id');
        $title = Request::get('title');
        if ($this->config['repeatitle'] && Content::check($title, $id)) {
            return '该标题的文章已经存在请检查是否重复';
        } else {
            return true;
            // self::success();
        }
    }
    /**
     * [JSON数据]
     * @return [type] [description]
     */
    public function do_getJson()
    {
        $id = (int) $_GET['id'];
        return Content::get($id);
        // self::success($rs);
    }
    /**
     * [简易编辑]
     * @return [type] [description]
     */
    public function do_simpleEdit()
    {
        $data = Request::post();
        $id = $data['id'];
        Content::update($data, $id);
        // self::success();
    }
    /**
     * [查找正文图片]
     * @return [type] [description]
     */
    public function do_findpic($id = null)
    {
        $id === null && $id = $this->id;
        $rs = $this->getContentPics($id);
        $_count = count($rs);
        include self::view("files.manage", "files");
    }
    public static function getContentPics($id = null)
    {
        $content  = ContentDataModel::getBody($id);
        $picArray = array();
        if ($content) {
            $content = stripslashes($content);
            $array   = FilesPic::findImg($content);
            $fArray  = array();
            foreach ($array as $key => $value) {
                $value = trim($value);
                // echo $value.PHP_EOL;
                if (stripos($value, iCMS_FS_HOST) !== false) {
                    $filepath = FilesClient::getPath($value, '-http');
                    $rpath    = FilesClient::getPath($value, 'http2root');
                    if ($filepath) {
                        $pf   = pathinfo($filepath);
                        $picArray[] = array(
                            'id'       => 'path@' . $filepath,
                            'rootpath' => $rpath,
                            'path'     => rtrim($pf['dirname'], '/') . '/',
                            'filename' => $pf['filename'],
                            'size'     => @filesize($rpath),
                            'time'     => @filectime($rpath),
                            'ext'      => $pf['extension']
                        );
                    }
                }
            }
        }
        return $picArray;
    }

    /**
     * [正文预览]
     * @return [type] [description]
     */
    public function do_preview()
    {
        echo ContentDataModel::getBody($this->id);
    }
    public function do_inbox()
    {
        $this->manage("inbox");
    }
    public function do_trash()
    {
        $this->_postype = 'all';
        $this->manage("trash");
    }
    public function do_user()
    {
        $this->_postype = 0;
        $this->manage();
    }
    public function do_examine()
    {
        $this->_postype = 0;
        $this->manage("examine");
    }
    public function do_off()
    {
        $this->_postype = 0;
        $this->manage("off");
    }
    public function do_normal(){
        $this->do_manage();
    }
    public function do_manage($stype = 'normal')
    {
        $this->manage($stype);
    }
    public function manage($stype = 'normal')
    {
        AdmincpBatch::set('stype', $stype);

        if ($cid = (int) $_GET['cid']) {
            $node = Node::get($cid);
        }

        //$stype OR $stype = Admincp::$APP_DO;

        $map_where = array();
        //status:[0:草稿][1:正常][2:回收][3:待审核][4:不合格]
        //postype: [0:用户][1:管理员]
        $stype && $this->_status = Content::$stypeMap[$stype];

        $where = array();
        is_numeric($_GET['postype']) && $this->_postype = (int) $_GET['postype'];
        is_numeric($_GET['status']) && $this->_status = (int) $_GET['status'];
        is_numeric($this->_postype) && $where[] = array('postype', $this->_postype);
        is_numeric($this->_status) && $where[] = array('status', $this->_status);

        $userid = Member::$user_id;
        if (AdmincpAccess::app('MANAGE', self::$app)) {
            $userid = Request::get('userid');
        }
        $userid && $where[] = array('userid', $userid);

        if ($rootid = Admincp::$APP_DATA['rootid']) {
            $apps = Apps::getData($rootid);
            $fields = Admincp::$APP_DATA['fields'];
            if ($fArray = Former::fields($fields)) {
                $column = array_column($fArray, 'type', 'id');
                $linkkey = array_search('relation:id', $column);
                if ($linkkey) {
                    $linkid = (int)Request::get($linkkey);
                    $model = Apps::model($apps);
                    $linkDatas = $model->get($linkid);
                    $linkid && $where[] = array($linkkey, $linkid);
                }
            }
        }
        $appTitle = Admincp::$APP_DATA['title'];
        // if (isset($_GET['pid']) && $pid != '-1') {
        //     $uri_array['pid'] = $pid;
        //     if (empty($_GET['pid'])) {
        //         $sql .= " AND `pid`=''";
        //     } else {
        //         iMap::init('prop', self::$appId, 'pid');
        //         $map_where += iMap::where($pid);
        //     }
        // }

        NodeAccess::where($cid, $where, $whereAccess);

        if ($keywords = Request::get('keywords')) {
            $whereKw = array('title', 'REGEXP', $keywords);
            switch (Request::sget('st')) {
                case "title":
                    break;
                case "tag":
                    $whereKw[0] = 'tags';
                    break;
                case "source":
                    $whereKw[0] = 'source';
                    break;
                case "clink":
                    $whereKw[0] = 'clink';
                    break;
                case "weight":
                    $whereKw = array('weight', $keywords);
                    break;
                case "id":
                    $keywords = explode(',', $keywords);
                    $whereKw = array('id', $keywords);
                    break;
                case "tkd":
                    $whereKw = array('CONCAT(title,keywords,description)', 'REGEXP', $keywords);
                    break;
                default:
                    $st = Request::sget('st');
                    if (Admincp::$APP_DATA['fields'][$st]) {
                        $whereKw = array($st, $keywords);
                    }
            }
            $where[] = $whereKw;
        }
        $title = Request::get('title');
        $title && $where[] = array('title', 'like', '%' . $title . '%');

        $tag = Request::get('tag');
        $tag && $where[] = array('tag', 'REGEXP', '[[:<:]]' . $tag . '[[:>:]]');

        $starttime = Request::get('starttime');
        $starttime && $where[] = array('pubdate', '>=', str2time($starttime . (strpos($starttime, ' ') !== false ? '' : " 00:00:00")));

        $endtime = Request::get('endtime');
        $endtime && $where[] = array('pubdate', '<=', str2time($endtime . (strpos($endtime, ' ') !== false ? '' : " 00:00:00")));

        $post_starttime = Request::get('post_starttime');
        $post_starttime && $where[] = array('postime', '>=', str2time($post_starttime . (strpos($starttime, ' ') !== false ? '' : " 00:00:00")));

        $post_endtime = Request::get('post_endtime');
        $post_endtime && $where[] = array('postime', '<=', str2time($post_endtime . (strpos($endtime, ' ') !== false ? '' : " 00:00:00")));

        isset($_GET['pic']) && $where[] = array('haspic', ($_GET['pic'] ? 1 : 0));
        isset($_GET['nopic']) && $where[] = array('haspic', 0);

        // $orderby = $this->get_orderby();
        $orderby = self::setOrderBy(array(
            Content::$primaryKey => "ID",
            'hits'       => "点击",
            'hits_week'  => "周点击",
            'hits_month' => "月点击",
            'good'       => "顶",
            'postime'    => "时间",
            'pubdate'    => "发布时间",
            'comment'   => "评论数",
        ));
        $result = ContentModel::where($whereAccess)->where($where)
            ->orderBy($orderby)
            ->paging();

        // iDebug::$DATA = array_column($result, 'id');
        // iDebug::$DATA = DB::getQueryTrace('query');
        // $propArray = PropWidget::get("pid", null, 'array');
        include self::view($this->VIEW_MANAGE, $this->VIEW_DIR);
    }
    public function save()
    {
        // iPHP::callback($this->callback['save:begin'], array($this));
        $data = FormerApp::save(Admincp::$APP_DATA);
        // iPHP::callback($this->callback['save:end'], array($this, $data));
        self::saved($this, __METHOD__, $data);
        $pk = AppsTable::getPrimaryKey();
        $id = $data[$pk];
        // AppsMeta::save(self::$appId, $id);
        // Archive::save(self::$appId, $id,$data, Admincp::$APP_DATA);
        // iPHP::callback(array("Spider", "callback"), array($this, $id));

        $cid = Request::post('cid');
        $node = Node::get($cid);

        $data['url'] = Route::get(Admincp::$APP, array($data, $node))->href;


        $REFERER_URL = $_POST['REFERER'];
        if (empty($REFERER_URL) || strstr($REFERER_URL, '=save')) {
            $REFERER_URL = APP_URL . '&do=manage';
        }

        $button = array(
            array("text" => "查看", "target" => '_blank', "url" => $data['url'], "close" => false),
            array("text" => "编辑", "url" => APP_URL . "&do=edit&id=" . $data['id']),
            array("text" => "继续添加", "url" => APP_URL . "&do=add&cid=" . $data['cid']),
            array("text" => "返回列表", "url" => $REFERER_URL),
            array("text" => "网站首页", "url" => iCMS_URL, "target" => '_blank')
        );
        $time = 10;
        $onClose = Request::param('modal') ? ['modal' => true] : ['url' => $REFERER_URL];
        return ["data" => compact('button', 'time', 'onClose')];

        // self::success('保存成功');
        // $url = Content::url($id);
        // $REFERER_URL = APP_URL . '&do=manage';
        // $moreBtn = array(
        //     array("text" => "查看该" . $this->app_name, "target" => '_blank', "url" => $url, "close" => false),
        //     array("text" => "编辑该" . $this->app_name, "url" => APP_URL . "&do=edit&id=" . $id),
        //     array("text" => "继续添加" . $this->app_name, "url" => APP_URL . "&do=add&cid=" . $cid),
        //     array("text" => "返回" . $this->app_name . "列表", "url" => $REFERER_URL),
        //     array("text" => "查看网站首页", "url" => iCMS_URL, "target" => '_blank')
        // );
        // Script::$dialog['modal'] = true;
        // Script::dialog('success:#:check:#:' . $this->app_name . '添加完成!<br />10秒后返回' . $this->app_name . '列表' . $msg, 'url:' . $REFERER_URL, 10, $moreBtn);
    }

    public function do_delete($id = null)
    {
        $id === null && $id = $this->id;
        return $this->remove($id);
    }

    public static function remove($id, $uid = '0', $postype = '1')
    {
        $id = (int) $id;
        $id or self::alert("请选择要删除的文章");
        $uid && $where = array('userid' => $uid, 'postype' => $postype);
        if (!AdmincpAccess::app('DELETE')) {
            $where[] = array('userid', Member::$user_id);
        }
        $art = Content::get($id, 'cid', $where);
        NodeAccess::check($art['cid'], 'cd');
        Content::delete($id);
        ContentDataModel::delete($id);
        return sprintf("ID[%s]%s删除", $id,self::$title);
    }

    public function get_orderby()
    {
        return self::setOrderBy(array(
            Content::$primaryKey => "ID",
            'hits'       => "点击",
            'hits_week'  => "周点击",
            'hits_month' => "月点击",
            'good'       => "顶",
            'postime'    => "时间",
            'pubdate'    => "发布时间",
            'comment'   => "评论数",
        ));
    }
    public static function widget_deck()
    {
        include self::view("deck", self::$VIEW_DIR);
    }
    public static function widget_count()
    {
        $total = ContentModel::count();
        $widget[] = array($total, '全部');
        foreach (Content::$statusMap as $status => $text) {
            $count = ContentModel::where('status', $status)->count();
            $widget[] = array($count, $text);
        }
        return $widget;
    }
}
