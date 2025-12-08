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
defined('APP_URL') or define('APP_URL', '/admincp.php/article');

class ArticleAdmincp extends AdmincpCommon
{
    use AdmincpCommonTrait;

    public $callback = [];
    public $chapter = false;
    public $dataid = 0;
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
        'weight'     => '权重',
        'comment'    => '评论数',
    ];
    // public function __construct()
    // {
    //     parent::__construct();
    //     // $this->_postype  = '1';
    //     // $this->_status   = '1';
    //     // throw new Exception("Error Processing Request", 1);
    //     // trigger_error('sdfsdf',E_USER_DEPRECATED);

    // }

    /**
     * 添加文章
     *
     * @uri /article/data  获取文章正文
     */
    public function do_add()
    {
        $this->dataid = (int) Request::get('dataid');
        $rs = $artData = [];
        $this->id && list($rs, $data, $artData) = Article::data($this->id, $this->dataid);
        $artData['bodyCount'] or $artData['bodyCount'] = 1;
        // extract($artData); // chapterIds chapterTitles bodyArray bodyCount

        $rs['cid'] = $rs['cid'] ?: (int) Request::sget('cid');
        $Node = $rs['cid'] ? Node::get($rs['cid']) : [];

        if (empty($this->id)) {
            $rs['nodeAttrs'] = [];
            $rs['status'] = '1';
            $rs['postype'] = '1';
            $rs['editor'] = Admin::$nickname;
            $rs['userid'] = Admin::$user_id;
        }
        $rs['sortnum'] = $rs['sortnum'] ?: time();
        $rs['weight'] = $rs['weight'] ?: time();

        if (self::$CONFIG['editor'] == 'vditor' || $rs['markdown']) {
            $rs['markdown'] = 1;
            self::$CONFIG['editor'] = 'vditor';
        }
        if ($editor = Request::get('ui_editor')) {
            self::$CONFIG['editor'] = ($editor == 'markdown') ? 'vditor' : $editor;
            $rs['markdown'] = ($editor == 'markdown') ? 1 : 0;
        }

        include self::view();
    }

    public function done_data($data = [], $chapter = 0)
    {
        $rs = $articleData = [];
        $this->id && list($rs, $data, $articleData) = Article::data($this->id, $this->dataid);
        $isChapter = $rs['chapter'] > 0 ? true : false;
        $count = $articleData['bodyCount'] ?: 1;
        $subtitle = isset($articleData['chapterTitles']) ? $articleData['chapterTitles'][0] : '';
        $items = [];
        if ($articleData['bodyArray']) {
            foreach ($articleData['bodyArray'] as $key => $body) {
                $items[$key] = [
                    'chapterTitle' => $articleData['chapterTitles'][$key] ?: '',
                    'chapterId'    => $articleData['chapterIds'][$key] ?: '',
                    'body'         => $body,
                    'show'         => $key ? false : true,
                    'id'           => random(4),
                    'display'      => 1
                ];
            }
        } else {
            $items[0] = [
                'chapterTitle' => '',
                'chapterId'    => '',
                'body'         => '',
                'show'         => true,
                'id'           => random(4),
                'display'      => 1
            ];
        }

        return compact('rs', 'items', 'isChapter', 'count', 'subtitle');
    }

    public function do_update_sort()
    {
        foreach ((array) $_POST['sortnum'] as $sortnum => $id) {
            Article::update(compact('sortnum'), compact('id'));
        }
    }

    public function batching()
    {
        return [
            'purge' => function ($ids, $data, $batch) {
                foreach ((array)$ids as $i => $id) {
                    $this->do_purge($id, false);
                }
            },
            'thumb' => function ($ids, $data, $batch) {
                foreach ((array)$ids as $id) {
                    $body = ArticleDataModel::getBody($id);
                    $picurl = FilesPic::remote($body, 'autopic');
                    $this->setPic($picurl, $id);
                }
            },
            'move' => function ($ids, $data, $batch) {
                $cid = (int) $data['nid'];
                $cid or self::alert('请选择目标栏目');
                AdmincpAuth::node($cid, 'ca');
                Article::update(compact('cid'), $ids);
            },
            'scid' => function ($ids, $data, $batch) {
                $scid = (array) $data['scid'];
                $scid = implode(',', $scid);
                Article::update(compact('scid'), $ids);
            },
            'tag' => function ($ids, $data, $batch) {
                $pat = $data['pattern'];
                $bTagArr = explode(',', $data['tag']);
                foreach ((array)$ids as $id) {
                    $art = Article::get($id, 'tags,cid');
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
                        Article::update(compact('tags'), compact('id'));
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
        if (self::$CONFIG['repeatitle'] && Article::check($title, $id)) {
            return '该标题的文章已经存在,请检查是否重复';
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
            return Article::update($data, $id);
        }
        $id = (int) $_GET['id'];
        return Article::get($id);
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
        $content = ArticleDataModel::getBody($id);
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
        echo ArticleDataModel::getBody($this->id);
    }

    /**
     * 用户文章列表
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

    public function do_test()
    {
        $id = ArticleModel::create([
            'title' => time(),
            'stitle'=> time()
        ]);
        // ArticleModel::delete($id);
        // ArticleModel::get(4);
        DB::getQueryLog(1);
    }

    public function do_index()
    {
        if ($sdo = Request::sget('sdo')) {
            $this->_status = Article::$stypeMap[$sdo];
        }

        is_numeric($_GET['postype']) && $this->_postype = (int) $_GET['postype'];
        is_numeric($_GET['status']) && $this->_status = (int) $_GET['status'];

        if (Request::isAjax()) {
            return $this->index();
        }

        self::setContext([
            'params' => [
                'postype' => $this->_postype,
                'status'  => $this->_status,
            ],
            'tablist' => 'params',
            'subApp'  => AppsWidget::subApp(self::$APPID)
        ]);
        include self::view('index');
    }

    public function index()
    {
        if ($cid = (int) $_GET['cid']) {
            $node = Node::get($cid);
        }
        $where = [];

        is_numeric($this->_postype) && $where[] = ['postype', $this->_postype];
        is_numeric($this->_status) && $where[] = ['status', $this->_status];

        AdmincpAuth::where($where);
        AdmincpAuth::nodeWhere($cid, $where);

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
        ], function ($rows) {
            $nodeArray = Node::many($rows, 'cid');
            $scategoryArray = Node::many($rows, 'scid');

            $rows = array_map(function ($item) use ($nodeArray) {
                $C = (array)$nodeArray[$item['cid']];
                $iurl = Route::get('article', [$item, $C]);
                $item['url'] = $iurl->href;

                // self::actionBtns($item,[]);
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
        empty($data) && $data = ArticleModel::postData();

        $data['id'] && $row = Article::get($data['id']);
        $data['tags'] = Tag::name($data['tags']);

        empty($data['title']) && self::alert('标题不能为空');
        empty($data['cid']) && self::alert('请选择所属栏目');
        (empty(self::getbody()) && empty($data['url'])) && self::alert('文章内容不能为空');

        $data['pubdate'] = str2time($data['pubdate']);
        empty($data['userid']) && $data['userid'] = Admin::$user_id;
        empty($data['editor']) && $data['editor'] = Admin::$nickname;

        if (!Admin::isSuperRole()) { //非超级管理
            $data['userid'] = Admin::$user_id;
            $data['editor'] = Admin::$nickname;
            if ($row && $row['userid'] != $data['userid']) {
                // $data['userid'] = $row['userid'];
                self::alert('无权进行此项操作');
            }
        }

        !isset($_POST['nofilter']) && iPHP::callback('Filter::run', [&$data, self::$CONFIG['filter']], E_USER_ERROR);

        if (self::$CONFIG['repeatitle'] && Article::check($data['title'], $data['id'])) {
            self::alert('该标题的文章已经存在请检查是否重复');
        }

        AdmincpAuth::node($data['cid'], $row ? 'ce' : 'ca');
        $node = Node::get($data['cid']);

        if ($node['rule']['article'] && strstr($node['rule']['article'], '{LINK}') !== false && empty($data['clink'])) {
            $data['clink'] = Pinyin::get($data['title'], self::$CONFIG['clink']);
        }

        if ($data['clink'] && Article::check($data['clink'], $data['id'], 'clink')) {
            self::alert('该文章自定义链接已经存在请检查是否重复');
        }

        if (empty($data['description']) && empty($data['url'])) {
            $body = self::getbody();
            if ($data['markdown']) {
                $mdBody = PluginMarkdown::parser(implode('', $body));
                empty($mdBody) && $mdBody = $body;
                $data['description'] = $this->autodesc($mdBody);
            } else {
                $data['description'] = $this->autodesc($body);
            }
        }
        FilesPic::values($data);
        FilesPic::data($data, 'picdata');
        $data['haspic'] = empty($data['pic']) ? 0 : 1;

        $REFERER_URL = Request::post('REFERER');
        if (empty($REFERER_URL)) {
            $REFERER_URL = Admincp::url('/article/index');
        }

        if (empty($data['id'])) {
            $data['postime'] = time();
            $data['chapter'] = 0;
            $data['mobile'] = 0;
            $data['id'] = Article::create($data);
        } else {
            is_null(Request::post('isChapter')) && $data['chapter'] = 0;
            unset($data['postime']);
            Article::update($data, $data['id']);
        }
        $this->save_data($data);

        $data['url'] = Route::get('article', [$data, $node])->href;

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

    public function do_purge($id = null, $return = false)
    {
        $id === null && $id = $_GET['id'];
        $article = Article::get($id);
        // $node = NodeApp::node($article['cid'], false);
        $node = Node::get($article['cid']);
        $iurl = (array) Route::get('article', [$article, $node]);

        foreach ($iurl as $key => $value) {
            if (is_array($value) && isset($value['url'])) {
                $url = $value['url'];
                $p = parse_url($value['url']);
                $url = str_replace($p['host'], $p['host'] . '/~cc', $value['url']);
                $purl = str_replace($p['host'], $p['host'] . '/~cc', $value['pageurl']);
                $msg[] = Http::purge($url);
                $msg[] = Http::purge($url, true);
                for ($i = 2; $i < 100; $i++) {
                    $purl = str_replace('{P}', $i, $purl);
                    $msg[] = Http::purge($purl);
                    $msg[] = Http::purge($purl, true);
                }
            }
        }
        return $msg;
    }

    public static function remove($id, $uid = '0', $postype = '1')
    {
        $id = (int) $id;
        $id or self::alert('请选择要删除的文章');
        $uid && $where = ['userid' => $uid, 'postype' => $postype];
        if (!AdmincpAuth::app('delete')) {
            $where[] = ['userid', Admin::$user_id];
        }
        $art = Article::get($id, 'cid,pic,tags', $where);
        AdmincpAuth::node($art['cid'], 'cd');
        Article::delete($id);
        ArticleDataModel::delete($id);
        return sprintf('ID[%s]文章删除', $id);
    }

    public function save_data(&$data)
    {
        $article_id = $data['id'];
        $haspic = $data['haspic'];
        $chapter = $data['chapter'];

        $isChapter = Request::post('isChapter');
        $dataIdArr = Request::post('dataId');
        $bodyArray = self::getbody();

        is_array($dataIdArr) && $dataIdArr = array_filter($dataIdArr);

        $_dataIdArr = ArticleDataModel::getIds($article_id);
        $chapterIds = [];
        //章节模式
        if ($isChapter) {
            $chapterIds = (array)$dataIdArr;
            $chapterIds = array_filter($chapterIds);
            $chapterTitle = Request::post('chapterTitle');
            $chapter = Request::post('chapterNum');
            empty($chapter) && $chapter = count($bodyArray);
            foreach ($bodyArray as $idx => $body) {
                if (is_array($body)) {
                    $body['body'] && $artData = $this->body($body['body'], $body['subtitle'], $article_id, null, $haspic);
                } else {
                    $artDataId = (int)$chapterIds[$idx];
                    $subtitle = $chapterTitle[$idx];
                    $artData = $this->body($body, $subtitle, $article_id, $artDataId, $haspic);
                }
                $data['article_data'][] = $artData;
            }
        } else {
            $artDataId = $dataIdArr[0];
            $subtitle = Request::post('subtitle');
            $body = implode(iPHP_PAGEBREAK . PHP_EOL, $bodyArray);
            $artData = $this->body($body, $subtitle, $article_id, $artDataId, $haspic);
            $data['article_data'][] = $artData;
            $chapterIds = [$artData['id']];
            $chapter = 0;
        }
        if (is_array($_dataIdArr)) {
            $diff = array_diff_values($chapterIds, $_dataIdArr);
            if ($diff['-']) { //删除章节
                ArticleDataModel::delete($article_id, $diff['-'], 'id');
            }
        }
        Article::update(compact('chapter'), $article_id);
        // iPHP::callback(array("Spider", "callback"), array($this, $article_id, 'data'));
    }

    public static function getbody()
    {
        if (isset($_POST['rs'])) {
            $rs = $_POST['rs'];
            $body = isset($rs['body']) ? $rs['body'] : '';
        }
        if ($_POST['body']) {
            $body = $_POST['body'];
        }
        return (array)$body;
    }

    public function body($body, $subtitle, $article_id = 0, $id = 0, &$haspic = 0)
    {
        // $body = preg_replace(array('/<script.+?<\/script>/is','/<form.+?<\/form>/is'),'',$body);
        Request::post('dellink') && $body = preg_replace("/<a[^>].*?>(.*?)<\/a>/si", '\\1', $body);
        if ($_POST['rs']['markdown']) {
            // $body = Security::escapeStr($body);
        } else {
            Request::post('autoformat') && $body = autoformat($body);
            // self::$CONFIG['autoformat'] && $body = autoformat($body);
        }

        $fields = ArticleDataModel::getFields($id);
        $data = compact($fields);
        !isset($_POST['nofilter']) && iPHP::callback('Filter::run', [&$data, self::$CONFIG['filter']], E_USER_ERROR);

        if ($id) {
            ArticleDataModel::update($data, compact('id', 'article_id'));
        } else {
            $id = ArticleDataModel::create($data);
        }
        $data['id'] = $id;
        $data['article_id'] = $article_id;
        $this->remote($body, compact('id', 'article_id'));

        return $data;
    }

    public function remote($body, $where)
    {
        Request::post('noWatermark') && FilesMark::$enable = false;

        if (Request::post('remote')) {
            $body = FilesPic::remote($body, true);
            $body = FilesPic::remote($body, true);
            $body = FilesPic::remote($body, true);
            if ($body && $where) {
                ArticleDataModel::update(compact('body'), $where);
            }
        }
        if (Request::post('autopic')) {
            $autopic = FilesPic::remote($body, 'autopic');
            $autopic && $this->setPic($autopic, $where['article_id']);
        }
    }

    public static function autodesc($body)
    {
        $length = Config::get('article.descLen')?:100;
        $enable = Config::get('article.autodesc');
        if (!$enable) {
            return;
        }
        return cutWords($body, $length, $enable);
    }

    public function setPic($picurl, $article_id, $field = 'pic')
    {
        if (is_array($picurl)) {
            foreach (FilesPic::$FIELDS as $key => $field) {
                $picurl[$key] && $this->setPic($picurl[$key], $article_id, $field);
            }
            return;
        }
        if (stripos($picurl, iCMS_FILE_HOST) !== false) {
            $field == 'pic' && $haspic = 1;

            $check = Article::value($field, $article_id);
            if ($check) {
                return;
            }

            $pic = FilesClient::getPath($picurl, '-http');
            $picroot = FilesClient::getRoot($pic);
            if (file_exists($picroot)) {
                list($width, $height, $type, $attr) = getimagesize($picroot);
                $picdata = Article::value('picdata', $article_id);
                $picdata[$field] = ['w' => $width, 'h' => $height];
                $data = compact('haspic', 'picdata');
                $data[$field] = $pic;
                Article::update($data, ['id' => $article_id]);
            }
        }
    }
    public function getDataIds($indexid)
    {
        return ArticleDataModel::getIds($indexid);
    }
}
