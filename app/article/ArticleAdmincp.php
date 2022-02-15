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
defined('APP_URL') or define('APP_URL', 'admincp.php?app=article');

class ArticleAdmincp extends AdmincpCommon
{    
    public $callback = array();
    public $chapter  = false;

    public function __construct()
    {
        parent::__construct();
        $this->id        = (int) $_GET['id'];
        $this->dataid    = (int) $_GET['dataid'];
        $this->_postype  = '1';
        $this->_status   = '1';

        Node::$APPID     = self::$appId;
    }

    /**
     * [添加文章]
     * @access.menu true
     * @access.auth true
     */
    public function do_add()
    {        
        $article = $articleData = array();
        if ($this->id) {
            list($article, $data, $articleData) = Article::data($this->id, $this->dataid);
            extract($articleData);
        }
        $bodyCount or $bodyCount = 1;
        $cid        = empty($article['cid']) ? (int) Request::get('cid') : $article['cid'];
        $nodeSelect = Node::setAccess('ca')->select($cid);
        $node = $cid ? Node::get($cid) : [];
        $article['pubdate'] = get_date($article['pubdate'], 'Y-m-d H:i:s');
        $article['markdown'] && $this->config['markdown'] = "1";

        if (empty($this->id)) {
            $article['status']  = "1";
            $article['postype'] = "1";
            $article['editor']  = Member::$nickname;
            $article['userid']  = Member::$user_id;
        }

        if (isset($_GET['ui_editor'])) {
            $this->config['markdown'] = ($_GET['ui_editor'] == 'markdown') ? "1" : "0";
        }


        // AdmincpBase::$DEBUG['markdown'] = $this->config['markdown'];

        // self::runHook('onAdd', [$this, __METHOD__, $article]);
        self::added($this, __METHOD__, $article);
        include self::view("article.add");
    }

    public function do_update_sort()
    {
        foreach ((array) $_POST['sortnum'] as $sortnum => $id) {
            Article::update(compact('sortnum'), compact('id'));
        }
    }
    public function do_batch()
    {
        $stype = AdmincpBatch::$config['stype'];
        $actions = array(
            'purge' => function ($idArray, $ids, $batch) {
                $_count = count($_POST['id']);
                foreach ((array) $_POST['id'] as $i => $id) {
                    $this->do_purge($id, false);
                }
            },
            'thumb' => function ($idArray, $ids, $batch) {
                foreach ((array) $_POST['id'] as $id) {
                    $body   = ArticleDataModel::getBody($id);
                    $picurl = FilesPic::remote($body, 'autopic');
                    $this->setPic($picurl, $id);
                }
            },
            'move' => function ($idArray, $ids, $batch) {
                $cid = (int) $_POST['cid'];
                $cid or self::alert("请选择目标栏目");
                NodeAccess::check($cid, 'ca');
                Article::update(compact('cid'), $idArray);
            },
            'scid' => function ($idArray, $ids, $batch) {
                $scid = (array) $_POST['bscid'];
                Article::update(compact('scid'), $idArray);
            },
            'prop' => function ($idArray, $ids, $batch) {
                $pid = (array) $_POST['pid'];
                Article::update(compact('pid'), $idArray);
            },
            'keyword' => function ($idArray, $ids, $batch) {
                $pattern = Request::post('pattern');
                if ($pattern == 'replace') {
                    $data = array('keywords' => Request::post('bkeyword'));
                    $data && Article::update($data, $idArray);
                } elseif ($pattern == 'addto') {
                    foreach ($idArray as $id) {
                        $keywords = Article::value('keywords', $id);
                        $keywords = ($keywords ? $keywords . ',' : '') . Request::post('bkeyword');
                        Article::update(compact('keywords'), compact('id'));
                    }
                }
            },
            'tag' => function ($idArray, $ids, $batch) {
                $pattern = Request::post('pattern');
                $bTagArr = explode(',', Request::post('btag'));
                foreach ($idArray as $id) {
                    $art    = Article::get($id, 'tags,cid');
                    $tagArr = $art['tags'] ? explode(',', $art['tags']) : array();
                    $pieces = $bTagArr;
                    if ($pattern == 'replace') {
                    } elseif ($pattern == 'prepend') {
                        $pieces  = array_merge($pieces, $tagArr);
                    } elseif ($pattern == 'append') {
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
            'meta' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    AppsMeta::save(self::$appId, $id);
                }
            },
            'quick_dels' => function ($idArray, $ids, $batch) {
                $_count = count($idArray);
                foreach ($idArray as $i => $id) {
                    $msg = $this->remove($id);
                }
            },
            'dels' => function ($idArray, $ids, $batch) {
                $_count = count($idArray);
                foreach ((array) $idArray as $i => $id) {
                    $this->remove($id);
                }
            },
            'default' => function ($idArray, $ids, $batch, $data = null) {
                $data === null && $data = Request::args($batch);
                $data && Article::update($data, $idArray);
            },
        );
        return AdmincpBatch::run($actions, "文章");
    }

    public function do_check()
    {
        $id    = (int) Request::get('id');
        $title = Request::get('title');
        if ($this->config['repeatitle'] && Article::check($title, $id)) {
            return '该标题的文章已经存在,请检查是否重复';
        } else {
            return true;
            // self::success();
        }
    }

    /**
     * [简易编辑]
     * @return [type] [description]
     */
    public function do_simpleEdit()
    {
        if(Request::isPost()){
            $data = Request::post();
            $id = $data['id'];
            return Article::update($data, $id);
        }
        $id = (int) $_GET['id'];
        return Article::get($id);
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
        $content  = ArticleDataModel::getBody($id);
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
        echo ArticleDataModel::getBody($this->id);
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
    public function do_normal()
    {
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
        $stype && $this->_status = Article::$stypeMap[$stype];
        $where = array();
        is_numeric($_GET['postype']) && $this->_postype = (int) $_GET['postype'];
        is_numeric($_GET['status']) && $this->_status = (int) $_GET['status'];
        is_numeric($this->_postype) && $where[] = array('postype', $this->_postype);
        is_numeric($this->_status) && $where[] = array('status', $this->_status);

        $userid = Member::$user_id;
        if (AdmincpAccess::app('MANAGE')) {
            $userid = Request::get('userid');
        }
        $userid && $where[] = array('userid', $userid);


        // if (is_numeric($_GET['pid'])) {
        //     $uri_array['pid'] = $pid;
        //     iMap::init('prop', self::$appId, 'pid');
        //     $map_where += iMap::where($_GET['pid']);
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
                    $whereKw = array('CONCAT(title,stitle,keywords,description)', 'REGEXP', $keywords);
                    break;
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


        $orderby = self::setOrderBy(array(
            'id'         => "ID",
            'hits'       => "点击",
            'hits_week'  => "周点击",
            'hits_month' => "月点击",
            'good'       => "顶",
            'postime'    => "时间",
            'pubdate'    => "发布时间",
            'comment'   => "评论数",
        ));

        $result = ArticleModel::where($whereAccess)->where($where)
            ->orderBy($orderby)
            ->paging();
        include self::view("article.manage");
    }
    public function save()
    {

        $data = ArticleModel::postData();

        $data['id'] && $row = Article::get($data['id']);

        $data['body'] = (array) $_POST['body'];

        $data['tags'] = Tag::name($data['tags']);

        empty($data['title']) && self::alert('标题不能为空');
        empty($data['cid']) && self::alert('请选择所属栏目');
        (empty($data['body']) && empty($data['url']))  && self::alert('文章内容不能为空');

        $data['pubdate'] = str2time($data['pubdate']);
        empty($data['userid']) && $data['userid'] = Member::$user_id;
        empty($data['editor']) && $data['editor'] = Member::$nickname;

        if (!Member::isSuperRole()) {
            $data['userid'] = $row['userid']; //非管理员禁止更新文章所属用户ID
        }

        if ($this->config['filter'] && is_array($this->config['filter']) && !isset($_POST['nofilter'])) {
            foreach ($this->config['filter'] as $fkey => $fvalue) {
                list($field, $text) = explode(':', $fvalue);
                if ($fwd = iPHP::callback('Filter::run', array(&$data[$field]), false)) {
                    self::alert($text . '中包含【' . $fwd . '】被系统屏蔽的字符，请重新填写。');
                }
            }
        }

        if ($this->config['repeatitle'] && Article::check($data['title'], $data['id'])) {
            self::alert('该标题的文章已经存在请检查是否重复');
        }

        $node = Node::get($data['cid']);
        if (strstr($node['rule']['article'], '{LINK}') !== false && empty($data['clink'])) {
            $data['clink'] = Pinyin::get($data['title'], $this->config['clink']);
        }

        if ($data['clink'] && Article::check($data['clink'], $data['id'], 'clink')) {
            self::alert('该文章自定义链接已经存在请检查是否重复');
        }

        if (empty($data['description']) && empty($data['url'])) {
            if ($_POST['markdown']) {
                $mdBody = PluginMarkdown::parser(implode('', $data['body']));
                empty($mdBody) && $mdBody = $data['body'];
                $data['description'] = $this->autodesc($mdBody);
            } else {
                $data['description'] = $this->autodesc($data['body']);
            }
        }
        FilesPic::values($data);
        $data['picdata'] = FilesPic::data($data);
        $data['haspic'] = empty($data['pic']) ? 0 : 1;
        

        $REFERER_URL = $_POST['REFERER'];
        if (empty($REFERER_URL) || strstr($REFERER_URL, '=save')) {
            $REFERER_URL = APP_URL . '&do=manage';
        }

        $body = $data['body'];
        unset($data['body']);

        if (empty($data['id'])) {
            $data['postime'] = time();
            $data['chapter'] = 0;
            $data['mobile']  = 0;

            $data['id']  = Article::create($data);

            // if ($data['status'] && Config::get('plugin.baidu.sitemap.sync')) {
            //     $msg = $this->do_baiduping($article_id, false);
            // }

            // if ($this->callback['return']) {
            //     return $this->callback['return'];
            // }
            // if ($this->callback['save:return']) {
            //     $this->callback['indexid'] = $article_id;
            //     return $this->callback['save:return'];
            // }
            // if ($_GET['callback'] == 'json') {
            //     echo self::success(array(
            //         "code"    => '1001',
            //         'indexid' => $article_id
            //     ));
            //     return;
            // }

            // if (isset($_GET['keyCode'])) {
            //     self::success('文章保存成功', 'url:' . APP_URL . "&do=edit&id=" . $data['id']);
            // }
        } else {
            is_null(Request::post('isChapter')) && $data['chapter'] = 0;
            unset($data['postime']);
            Article::update($data, $data['id']);

            // $data['url'] or $this->article_data($body, $data);

            // if ($this->callback['return']) {
            //     return $this->callback['return'];
            // }

            // if ($this->callback['save:return']) {
            //     $this->callback['indexid'] = $article_id;
            //     return $this->callback['save:return'];
            // }
            // if (isset($_GET['keyCode'])) {
            //     self::success('文章保存成功');
            // }
            // self::success('文章编辑完成<br />3秒后返回文章列表', 'url:' . $REFERER_URL);
        }

        $this->article_data($body, $data);
        $data['url'] = Route::get('article', array($data, $node))->href;

        self::saved($this, __METHOD__, $data);

        $button = array(
            array("text" => "查看", "target" => '_blank', "url" => $data['url'], "close" => false),
            array("text" => "编辑", "url" => APP_URL . "&do=edit&id=" . $data['id']),
            array("text" => "继续添加", "url" => APP_URL . "&do=add&cid=" . $data['cid']),
            array("text" => "返回列表", "url" => $REFERER_URL),
            array("text" => "网站首页", "url" => iCMS_URL, "target" => '_blank')
        );
        $time = 10;
        $onClose = ['url' => $REFERER_URL];
        return ["data" => compact('button', 'time', 'onClose')];

        // return self::success(compact('data', 'btns', 'time'), '保存成功', $REFERER_URL);

        // if (isset($_GET['keyCode'])) {
        //     self::success('文章保存成功', 'url:' . APP_URL . "&do=edit&id=" . $data['id']);
        // }

        // $moreBtn = array(
        //     array("text" => "查看该文章", "target" => '_blank', "url" => $url, "close" => false),
        //     array("text" => "编辑该文章", "url" => APP_URL . "&do=edit&id=" . $data['id']),
        //     array("text" => "继续添加文章", "url" => APP_URL . "&do=add&cid=" . $data['cid']),
        //     array("text" => "返回文章列表", "url" => $REFERER_URL),
        //     array("text" => "查看网站首页", "url" => iCMS_URL, "target" => '_blank')
        // );
        // Script::$dialog['modal'] = true;
        // Script::dialog('success:#:check:#:文章添加完成!<br />10秒后返回文章列表' . $msg, 'url:' . $REFERER_URL, 10, $moreBtn);
    }
    public function do_delete($id = null)
    {
        $id === null && $id = $this->id;
        $this->remove($id);
        return ['action' => 'delete', 'id' => $id];
    }
    public function do_purge($id = null, $return = false)
    {
        $id === null && $id = $_GET['id'];
        $article = Article::get($id);
        // $node = NodeApp::node($article['cid'], false);
        $node = Node::get($article['cid']);
        $iurl = (array) Route::get('article', array($article, $node));

        foreach ($iurl as $key => $value) {
            if (is_array($value) && isset($value['url'])) {
                $url  = $value['url'];
                $p    = parse_url($value['url']);
                $url  = str_replace($p['host'], $p['host'] . '/~cc', $value['url']);
                $purl = str_replace($p['host'], $p['host'] . '/~cc', $value['pageurl']);
                $msg[] = Http::purge($url);
                $msg[] = Http::purge($url, true);
                for ($i = 2; $i < 100; $i++) {
                    $purl  = str_replace('{P}', $i, $purl);
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
        $id or self::alert("请选择要删除的文章");
        $uid && $where = array('userid' => $uid, 'postype' => $postype);
        if (!AdmincpAccess::app('DELETE')) {
            $where[] = array('userid', Member::$user_id);
        }
        $art = Article::get($id, 'cid,pic,tags', $where);
        NodeAccess::check($art['cid'], 'cd');
        Article::delete($id);
        ArticleDataModel::delete($id);
        return sprintf("ID[%s]文章删除", $id);
    }

    public function article_data($bodyArray, $data)
    {
        $article_id = $data['id'];
        $haspic = $data['haspic'];
        $chapter = $data['chapter'];

        $isChapter = Request::post('isChapter');
        $data_id   = Request::post('data_id');
        is_array($data_id) && $data_id = array_filter($data_id);

        $_data_id = ArticleDataModel::get_ids($article_id);
        $chapterIds = [];
        //章节模式
        if ($isChapter) {
            $chapterIds   = (array)$data_id;
            $chapterIds   = array_filter($chapterIds);
            $chapterTitle = Request::post('chapterTitle');
            $chapter      = Request::post('chapterNum');
            empty($chapter) && $chapter = count($bodyArray);
            foreach ($bodyArray as $idx => $body) {
                if (is_array($body)) {
                    $body['body'] && $this->body($body['body'], $body['subtitle'], $article_id, null, $haspic);
                } else {
                    $artDataId = (int)$chapterIds[$idx];
                    $subtitle = $chapterTitle[$idx];
                    $this->body($body, $subtitle, $article_id, $artDataId, $haspic);
                }
            }
        } else {
            $artDataId = $data_id[0];
            $subtitle = Request::post('subtitle');
            $body = implode(iPHP_PAGEBREAK.PHP_EOL.PHP_EOL, $bodyArray);
            $artDataId = $this->body($body, $subtitle, $article_id, $artDataId, $haspic);
            $chapterIds = [$artDataId];
            $chapter = 0;
        }
        if (is_array($_data_id)) {
            $diff = array_diff_values($chapterIds, $_data_id);
            if ($diff['-']) { //删除章节
                ArticleDataModel::delete($article_id, $diff['-'], 'id');
            }
        }
        Article::update(compact('chapter'), $article_id);
        // iPHP::callback(array("Spider", "callback"), array($this, $article_id, 'data'));
    }
    public function body($body, $subtitle, $article_id = 0, $id = 0, &$haspic = 0)
    {
        // $body = preg_replace(array('/<script.+?<\/script>/is','/<form.+?<\/form>/is'),'',$body);

        Request::post('dellink') && $body = preg_replace("/<a[^>].*?>(.*?)<\/a>/si", "\\1", $body);

        if ($_POST['markdown']) {
            $body = Security::escapeStr($body);
        } else {
            Request::post('autoformat') && $body = autoformat($body);
            // $this->config['autoformat'] && $body = autoformat($body);
        }
        if ($this->config['emoji'] == 'unicode') {
            $body = preg_replace('/\\\ud([8-9a-f][0-9a-z]{2})/i', '\\\\\ud$1', json_encode($body));
            $body = json_decode($body);
            $body = preg_replace('/\\\ud([8-9a-f][0-9a-z]{2})/i', '\\\\\ud$1', $body);
        } elseif ($this->config['emoji'] == 'clean') {
            $body = preg_replace('/\\\ud([8-9a-f][0-9a-z]{2})/i', '', json_encode($body));
            $body = json_decode($body);
        }
        $fields = ArticleDataModel::getFields($id);
        $data   = compact($fields);

        if ($id) {
            ArticleDataModel::update($data, compact('id', 'article_id'));
        } else {
            $id = ArticleDataModel::create($data);
        }
        $this->remote($body, compact('id', 'article_id'));

        return $id;
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
        $length = Config::get('article.descLen');
        $$enable = Config::get('article.autodesc');
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
        if (stripos($picurl, iCMS_FS_HOST) !== false) {
            $field == 'pic' && $haspic = 1;

            $check  = Article::value($field, $article_id);
            if ($check) {
                return;
            }

            $pic = FilesClient::getPath($picurl, '-http');
            list($width, $height, $type, $attr) = @getimagesize(FilesClient::getRoot($pic));

            $picdata  = Article::value('picdata', $article_id);
            $picdata[$field] = ['w' => $width, 'h' => $height];
            $data = compact('haspic', 'picdata');
            $data[$field] = $pic;
            Article::update($data, array('id' => $article_id));
        }
    }
}
