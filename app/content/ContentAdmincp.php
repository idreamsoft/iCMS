<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');
defined('APP_URL') or define('APP_URL', '/admincp.php/content');
class ContentAdmincp extends AdmincpCommon
{
    use AdmincpCommonTrait;

    public static $app = null;
    public static $title = null;
    public $_postype = null;
    public $_status = null;

    public static $orderBy = [
        'id'         => 'ID',
        'hits'       => '点击',
        'hits_week'  => '周点击',
        'hits_month' => '月点击',
        'good'       => '顶',
        'postime'    => '时间',
        'pubdate'    => '发布时间',
        'comment'    => '评论数',
        'weight'    => '权重',
    ];

    protected $VIEW_DIR = 'content';
    protected $VIEW_ADD = 'add';
    protected $VIEW_INDEX = 'index';

    public function __construct()
    {
        parent::__construct();
        $this->inited();
    }

    public function inited()
    {
        self::$MODEL = 'ContentModel';
        self::$APPID = Admincp::$APP_ID;
        self::$app = Admincp::$APP_DATA['app'];
        self::$title = Admincp::$APP_DATA['title'];

        // $this->_postype = '1';
        // $this->_status = '1';
        Node::$APPID = self::$APPID;

        self::$MODEL = Content::model(Admincp::$APP_DATA);
        if (Admincp::$APP_DATA['config']['nodeTable']) {
            NodeModel::setTable(self::$app);
        }
    }

    /**
     * 添加内容
     *
     * @uri /content/data  获取内容正文
     */
    public function do_add()
    {
        $this->id && $data = Content::data($this->id);
        isset($data['status']) or $data['status'] = '1';
        if (empty($data['cid']) && isset($_GET['cid'])) {
            $data['cid'] = (int) $_GET['cid'];
        }
        FormerApp::add(Admincp::$APP_DATA, $data, true);
        include self::view($this->VIEW_ADD, $this->VIEW_DIR);
    }

    public function do_update_sort()
    {
        foreach ((array) $_POST['sortnum'] as $sortnum => $id) {
            Content::update(compact('sortnum'), compact('id'));
        }
    }

    public function batching()
    {
        return [
            'move' => function ($ids, $data, $batch) {
                $cid = (int) $data['nid'];
                $cid or self::alert('请选择目标栏目');
                AdmincpAuth::node($cid, 'ca');
                Content::update(compact('cid'), $ids);
            },
            'prop' => function ($ids, $data, $batch) {
                $pid = implode(',', (array) $data['pid']);
                Content::update(compact('pid'), $ids);
            },
            'keyword' => function ($ids, $data, $batch) {
                $pat = $data['pattern'];
                if ($pat == 'replace') {
                    $data = ['keywords' => Request::post('bkeyword')];
                    $data && Content::update($data, $ids);
                } elseif ($pat == 'addto') {
                    foreach ($ids as $id) {
                        $keywords = Content::value('keywords', $id);
                        $keywords = ($keywords ? $keywords . ',' : '') . Request::post('bkeyword');
                        Content::update(compact('keywords'), compact('id'));
                    }
                }
                return true;
            },
            'tag' => function ($ids, $data, $batch) {
                $pat = $data['pattern'];
                $bTagArr = explode(',', $data['tag']);
                foreach ((array)$ids as $id) {
                    $art = Content::get($id, 'tags,cid');
                    $tagArr = $art['tags'] ? explode(',', $art['tags']) : [];
                    $pieces = $bTagArr;
                    if ($pat == 'replace') {
                    } elseif ($pat == 'prepend') {
                        $pieces = array_merge($pieces, $tagArr);
                    } elseif ($pat == 'append') {
                        $pieces = array_merge($tagArr, $pieces);
                    }
                    $pieces = array_unique($pieces);
                    $pieces = array_filter($pieces);
                    if ($pieces) {
                        $tags = implode(',', $pieces);
                        Content::update(compact('tags'), compact('id'));
                    }
                }
            },
            'meta' => function ($ids, $data, $batch) {
                foreach ((array)$ids as $id) {
                    AppsMeta::saveData(self::$APPID, $id);
                }
            },
            'quick_dels' => function ($ids, $data, $batch) {
                foreach ((array)$ids as $i => $id) {
                    $msg = $this->remove($id);
                }
            },
            'dels' => function ($ids, $data, $batch) {
                foreach ((array)$ids as $i => $id) {
                    $this->remove($id);
                }
            },
        ];
    }

    public function do_check()
    {
        $id = (int) Request::get('id');
        $title = Request::get('title');
        if (self::$CONFIG['repeatitle'] && Content::check($title, $id)) {
            return '该标题的文章已经存在请检查是否重复';
        } else {
            return true;
            // self::success();
        }
    }

    /**
     * 简易编辑
     * @return void
     */
    public function do_simpleEdit()
    {
        if (Request::isPost()) {
            $data = Request::post();
            $id = $data['id'];
            return Content::update($data, $id);
        }
        $id = (int) $_GET['id'];
        return Content::get($id);
    }

    /**
     * 查找正文图片
     * @return void
     */
    public function do_findpic($id = null)
    {
        $id === null && $id = $this->id;
        $rs = $this->getContentPics($id);
        $_count = count($rs);
        include self::view('manage', 'files');
    }

    public static function getContentPics($id = null)
    {
        $content = ContentDataModel::getBody($id);
        $picArray = [];
        if ($content) {
            $content = stripslashes($content);
            $array = FilesPic::findImg($content);
            $fArray = [];
            foreach ($array as $key => $value) {
                $value = trim($value);
                // echo $value.PHP_EOL;
                if (stripos($value, iCMS_FILE_HOST) !== false) {
                    $filepath = FilesClient::getPath($value, '-http');
                    $rpath = FilesClient::getPath($value, 'http2root');
                    if ($filepath) {
                        $pf = pathinfo($filepath);
                        $picArray[] = [
                            'id'       => 'path@' . $filepath,
                            'rootpath' => $rpath,
                            'path'     => rtrim($pf['dirname'], '/') . '/',
                            'filename' => $pf['filename'],
                            'size'     => @filesize($rpath),
                            'time'     => @filectime($rpath),
                            'ext'      => $pf['extension']
                        ];
                    }
                }
            }
        }
        return $picArray;
    }

    /**
     * 正文预览
     * @return void
     */
    public function do_detail()
    {
        echo ContentDataModel::getBody($this->id);
    }

    /**
     * 用户内容列表
     *
     * @uri index 管理列表
     * @uri examine 等待审核
     * @uri off  未通过
     * @uri trash 回收站
     *
     * @return void
     */
    public function do_user()
    {
        $this->_postype = 0;
        return $this->do_index();
    }

    public function do_index()
    {
        if ($sdo = Request::sget('sdo')) {
            $this->_status = Content::$stypeMap[$sdo];
        }

        is_numeric($_GET['postype']) && $this->_postype = (int) $_GET['postype'];
        is_numeric($_GET['status']) && $this->_status = (int) $_GET['status'];

        if (Request::isAjax()) {
            return $this->index();
        }
        $params = [
            'postype' => $this->_postype,
            'status'  => $this->_status,
        ];
        $get = Request::get();
        $relationIds = AppsTable::getRelationId(Admincp::$APP_DATA['fields']);
        if ($relationIds && $get) {
            $relation = array_filter_keys($get, $relationIds);
            $relation && $params = array_merge($params, $relation);
        }

        self::setContext([
            'params'  => $params,
            'tablist' => 'params',
            'subApp'  => AppsWidget::subApp(self::$APPID)
        ]);
        self::setJsPath('content/assets/js/index');
        include self::view('index');
    }

    public function index()
    {
        if ($cid = (int) $_GET['cid']) {
            $node = Node::get($cid);
        }
        $get = Request::get();
        $where = [];

        is_numeric($this->_postype) && $where[] = ['postype', $this->_postype];
        is_numeric($this->_status) && $where[] = ['status', $this->_status];

        $relationIds = AppsTable::getRelationId(Admincp::$APP_DATA['fields']);
        foreach ($relationIds as $key) {
            isset($get[$key]) && $where[] = [$key, $get[$key]];
        }

        AdmincpAuth::where($where);
        AdmincpAuth::nodeWhere($cid, $whereAccess);

        if ($keywords = Request::get('keywords')) {
            $whereKw = ['title', 'REGEXP', $keywords];
            switch (Request::sget('st')) {
                case 'title':
                    break;
                case 'tag':
                    $whereKw[0] = 'tags';
                    break;
                case 'source':
                    $whereKw[0] = 'source';
                    break;
                case 'clink':
                    $whereKw[0] = 'clink';
                    break;
                case 'weight':
                    $whereKw = ['weight', $keywords];
                    break;
                case 'id':
                    $keywords = explode(',', $keywords);
                    $whereKw = ['id', $keywords];
                    break;
                case 'tkd':
                    $whereKw = ['CONCAT(title,stitle,keywords,description)', 'REGEXP', $keywords];
                    break;
            }
            $where[] = $whereKw;
        }
        $pid = Request::get('pid');
        // isset($pid) && $where[] = DB::raw("FIND_IN_SET('{$pid}',pid)");
        isset($pid) && $where[] = ['pid', 'FIND_IN_SET', $pid];

        $title = Request::get('title');
        $title && $where[] = ['title', 'like', '%' . $title . '%'];

        $tag = Request::get('tag');
        $tag && $where[] = ['tag', 'REGEXP', '[[:<:]]' . $tag . '[[:>:]]'];

        $starttime = Request::get('starttime');
        $starttime && $where[] = ['pubdate', '>=', str2time($starttime . (strpos($starttime, ' ') !== false ? '' : ' 00:00:00'))];

        $endtime = Request::get('endtime');
        $endtime && $where[] = ['pubdate', '<=', str2time($endtime . (strpos($endtime, ' ') !== false ? '' : ' 00:00:00'))];

        $post_starttime = Request::get('post_starttime');
        $post_starttime && $where[] = ['postime', '>=', str2time($post_starttime . (strpos($starttime, ' ') !== false ? '' : ' 00:00:00'))];

        $post_endtime = Request::get('post_endtime');
        $post_endtime && $where[] = ['postime', '<=', str2time($post_endtime . (strpos($endtime, ' ') !== false ? '' : ' 00:00:00'))];

        isset($_GET['pic']) && $where[] = ['haspic', ($_GET['pic'] ? 1 : 0)];

        return self::tableData([
            'where' => $where,
            'with'  => $whereAccess
        ], function ($rows) {
            $nodeArray = Node::many($rows, 'cid');
            $appids = array_column($nodeArray, 'appid');
            $appArray = Apps::get($appids);

            $scategoryArray = Node::many($rows, 'scid');
            $rows = array_map(function ($item) use ($nodeArray, $appArray) {
                $C = (array)$nodeArray[$item['cid']];
                $apps = $appArray[$C['appid']];
                $route = $apps['app'] ?: 'content';
                $iurl = Route::get($route, [$item, $C]);
                $item['url'] = $iurl->href;
                // var_dump($item['picture']);
                // $item['picture'] = $item['picture'].'_200x200.jpg';
                // $item['test'] = ['1', '2'];
                return $item;
            }, $rows);
            return $rows;
        });
    }

    public function save(&$data = [])
    {
        // iPHP::callback($this->callback['save:begin'], array($this));
        $data = FormerApp::save(Admincp::$APP_DATA);
        $id = $data['id'];
        $cid = $data['cid'];
        $node = Node::get($cid);

        $data['url'] = Route::get(Admincp::$APP, [$data, $node])->href;

        $REFERER_URL = $_POST['REFERER'];
        if (empty($REFERER_URL) || strstr($REFERER_URL, '=save')) {
            $REFERER_URL = APP_URL . '&do=manage';
        }

        $btn = [
            ['text' => '查看', 'target' => '_blank', 'url' => $data['url'], 'close' => false],
            ['text' => '编辑', 'url' => APP_URL . '/edit/id/' . $data['id']],
            ['text' => '继续添加', 'url' => APP_URL . '/add/cid/' . $data['cid']],
            ['text' => '返回列表', 'url' => $REFERER_URL],
            ['text' => '网站首页', 'url' => iCMS_URL, 'target' => '_blank']
        ];
        $time = 3000;
        $onClose = ['url' => $REFERER_URL];
        return ['data' => compact('btn', 'time', 'height', 'onClose')];
    }

    public function deleting($ids = [])
    {
        if ($ids) {
            foreach ($ids as $key => $id) {
                $this->remove($id);
            }
        }
        return true;
    }

    public static function remove($id, $uid = '0', $postype = '1')
    {
        $id = (int) $id;
        $id or self::alert('请选择要删除的项目');
        $uid && $where = ['userid' => $uid, 'postype' => $postype];
        if (!AdmincpAuth::app('delete')) {
            $where[] = ['userid', Admin::$user_id];
        }
        $art = Content::get($id, 'cid', $where);
        AdmincpAuth::node($art['cid'], 'cd');
        Content::delete($id);
        ContentDataModel::delete($id);
        return sprintf('ID[%s]%s删除', $id, self::$title);
    }

    public static function widget_deck()
    {
        include self::view('deck', self::$VIEW_DIR);
    }

    public static function widget_count()
    {
        $total = ContentModel::count();
        $widget[] = [$total, '全部'];
        foreach (ContentModel::$statusMap as $status => $text) {
            $count = ContentModel::where('status', $status)->count();
            $widget[] = [$count, $text];
        }
        return $widget;
    }
}
