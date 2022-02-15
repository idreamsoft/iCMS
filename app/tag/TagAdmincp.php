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

class TagAdmincp extends AdmincpCommon
{
    public $callback = array();
    public function __construct()
    {
        parent::__construct();
        $this->id = (int) $_GET['id'];
    }

    public function do_add()
    {
        $this->id && $rs = TagModel::get($this->id);
        if (empty($rs)) {
            $rs['status'] = '1';
        }
        self::added($this, __METHOD__, $rs);
        include self::view('tag.add');
    }

    public function do_manage()
    {
        $cid    = (int) $_GET['cid'];
        $tcid   = (int) $_GET['tcid'];
        $pid    = (int) $_GET['pid'];
        $rootid = (int) $_GET['rootid'];

        $where = array();
        Node::makeWhere($where, $cid);
        Node::makeWhere($where, $tcid, 'tcid');

        $keywords = Request::get('keywords');
        $keywords && $where[] = array('CONCAT(name,seotitle,subtitle,keywords,description)', 'REGEXP', $keywords);

        $starttime = Request::get('starttime');
        $starttime && $where[] = array('pubdate', '>=', str2time($starttime . (strpos($starttime, ' ') !== false ? '' : " 00:00:00")));

        $endtime = Request::get('endtime');
        $endtime && $where[] = array('pubdate', '<=', str2time($endtime . (strpos($endtime, ' ') !== false ? '' : " 00:00:00")));

        $post_starttime = Request::get('post_starttime');
        $post_starttime && $where[] = array('postime', '>=', str2time($post_starttime . (strpos($starttime, ' ') !== false ? '' : " 00:00:00")));

        $post_endtime = Request::get('post_endtime');
        $post_endtime && $where[] = array('postime', '<=', str2time($post_endtime . (strpos($endtime, ' ') !== false ? '' : " 00:00:00")));

        $haspic = Request::get('pic');
        isset($haspic) && $where[] = array('haspic', ($haspic ? 1 : 0));

        $field = Request::get('field');
        $field  && $where[] = array('field', $field);

        // if (isset($_GET['pid']) && $pid != '-1') {
        //     $uri_array['pid'] = $pid;
        //     if ($_GET['pid'] == 0) {
        //         $sql .= " AND `pid`=''";
        //     } else {
        //         iMap::init('prop', self::$appId, 'pid');
        //         $map_where = iMap::where($pid);
        //     }
        // }
        // if ($map_where) {
        //     $map_sql = iSQL::select_map($map_where);
        //     $sql     = ",({$map_sql}) map {$sql} AND `id` = map.`iid`";
        // }

        $orderby = self::setOrderBy(array(
            'id'         => "ID",
            'hits'       => "点击",
            'hits_week'  => "周点击",
            'hits_month' => "月点击",
            'count'      => "使用数",
            'good'       => "顶",
            'postime'    => "时间",
            'pubdate'    => "发布时间",
            'comment'   => "评论数",
        ));
        $result = TagModel::where($where)
            ->orderBy($orderby)
            ->paging();

        include self::view("tag.manage");
    }
    /**
     * [导入标签]
     * @return [type] [description]
     */
    public function do_import()
    {
        // $_POST['cid'] OR self::alert('请选择标签所属栏目');
        Files::$check_data  = false;
        FilesCloud::$enable = false;
        FilesClient::$config['allow_ext'] = 'txt';
        $file = FilesClient::upload('upfile');
        $path = FilesClient::getRoot($file['path']);

        if ($path) {
            $contents = file_get_contents($path);
            $contents = Security::encoding($contents);
            if ($contents) {
                $cid  = (int) $_POST['cid'];
                $tcid = (array) $_POST['tcid'];
                $pid  = (array) $_POST['pid'];
                $msg  = array();
                $variable = explode("\n", $contents);
                foreach ($variable as $key => $name) {
                    $name = Tag::name($name);
                    if (empty($name)) {
                        $msg['empty']++;
                        unset($variable[$key]);
                        continue;
                    }
                    if (TagModel::field('id')->where('name', $name)->value()) {
                        unset($variable[$key]);
                        $msg['has']++;
                        continue;
                    }
                    $variable[$key] = $name;
                }
                $tids = Tag::create($variable, Member::$user_id, $cid, $tcid);
                $msg['success'] = count($tids);
            }
            @unlink($path);
            return sprintf(
                '标签导入完成<br />空标签:%d个<br />已经存在标签:%d个<br />成功导入标签:%d个',
                $msg['empty'],
                $msg['has'],
                $msg['success']
            );
        }
    }
    public function save()
    {
        $data = TagModel::postData();

        $data['id'] && $row = TagModel::get($data['id']);

        empty($data['name']) && self::alert('标签名称不能为空');
        $data['title'] = $data['name'];

        $data['pubdate'] = str2time($data['pubdate']);
        empty($data['userid']) && $data['userid'] = Member::$user_id;
        empty($data['editor']) && $data['editor'] = Member::$nickname;

        if (empty($data['id'])) {
            $hasNameId = Tag::check($data['name']);
            if ($hasNameId) {
                if (isset($_POST['spider_update'])) {
                    $data['id'] = $hasNameId;
                } else {
                    self::alert('该标签已经存在请检查是否重复');
                }
            }
        }

        $node = Node::get($data['cid']);
        if (strstr($node['rule']['tag'], '{LINK}') !== false && empty($data['clink'])) {
            $data['clink'] = Pinyin::get($data['name'], $this->config['clink']);
        }

        if ($data['clink'] && Tag::check($data['clink'], $data['id'], 'clink')) {
            self::alert('该标签自定义链接已经存在请检查是否重复');
        }

        FilesClient::$force_ext = "jpg";
        FilesPic::values($data);

        $data['haspic'] = empty($data['pic']) ? 0 : 1;

        $data['tkey'] or $data['tkey'] = Tag::getTkey($data['name']);


        if (empty($data['id'])) {
            $this->makeTkey($data['tkey']);
            $data['postime']  = $data['pubdate'];
            $data['count']    = '0';
            $data['comment'] = '0';
            $data['id'] = TagModel::create($data, true);
        } else {
            $this->makeTkey($data['tkey'], $data['id']);
            TagModel::update($data, $data['id']);
        }
        self::saved($this, __METHOD__, $data);
        // self::success('保存成功');
    }
    public function makeTkey(&$tkey, $id = 0)
    {
        $where['tkey'] = $tkey;
        $id && $where['id'] = array('<>', $id);
        $hasTkey = TagModel::field('id')->where($where)->value();
        if ($hasTkey) {
            $count = TagModel::where('tkey', 'like', "{$tkey}-%")->count();
            $tkey = $tkey . '-' . ($count + 1);
        }
    }

    public function check_spider_data(&$data, $old, $key, $value)
    {
        if ($old[$key]) {
            if ($value) {
                $data[$key] = $value;
            } else {
                unset($data[$key]);
            }
        }
    }

    public function do_delete($id = null)
    {
        $id === null && $id = $this->id;
        Tag::delete($id, 'id');
        // $dialog && self::success("标签删除成功");
    }
    public function do_batch()
    {
        $stype = AdmincpBatch::$config['stype'];
        $actions = array(
            'move' => function ($idArray, $ids, $batch) {
                $cid = (int) $_POST['cid'];
                $cid or self::alert("请选择目标栏目");
                $ocids = TagModel::field('cid')->where($idArray)->pluck();
                TagModel::update(compact('cid'), $idArray);
            },
            'mvtcid' => function ($idArray, $ids, $batch) {
                $tcid = (int) $_POST['tcid'];
                $tcid or self::alert("请选择目标分类");
                $ocids = TagModel::field('tcid')->where($idArray)->pluck();
                TagModel::update(compact('tcid'), $idArray);
            },
            'prop' => function ($idArray, $ids, $batch) {
                $pid = (array) $_POST['pid'];
                $opids = TagModel::field('pid')->where($idArray)->pluck();
                TagModel::update(compact('pid'), $idArray);
            },
            'tkey' => function ($idArray, $ids, $batch) {
                $rs = TagModel::field('id,name')->select($idArray);
                foreach ($rs as $tag) {
                    $id = $tag['id'];
                    $tkey = Tag::getTkey($tag['name']);
                    TagModel::update(compact('tkey'), $id);
                }
            },
            'keyword' => function ($idArray, $ids, $batch) {
                $keywords = Request::post('mkeyword');
                $pattern = Request::post('pattern');
                if ($pattern == 'replace') {
                    TagModel::update(compact('keywords'), $idArray);
                    // $sql    = "`keywords` = '" . Request::post('mkeyword') . "'";
                } elseif ($pattern == 'addto') {
                    $kwArray = TagModel::field('id,keywords')->where($idArray)->pluck('id', 'keywords');
                    foreach ($kwArray as $id => $kw) {
                        $kw && $kw .= ',';
                        $kw .= $keywords;
                        $kw && TagModel::update(array('keywords' => trim($kw)), $id);
                    }
                }
            },
            'tag' => function ($idArray, $ids, $batch) {
                $related = Request::post('mtag');
                $pattern = Request::post('pattern');
                if ($pattern == 'replace') {
                    TagModel::update(compact('related'), $idArray);
                    // $sql    = "`related` = '" . Request::post('mtag') . "'";
                } elseif ($pattern == 'addto') {
                    $kwArray = TagModel::field('id,related')->where($idArray)->pluck('id', 'related');
                    foreach ($kwArray as $id => $kw) {
                        $kw && $kw .= ',';
                        $kw .= $related;
                        $kw && TagModel::update(array('related' => trim($kw)), $id);
                    }
                }
            },
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    $this->do_delete($id);
                }
            },
            'default' => function ($idArray, $ids, $batch, $data = null) {
                $data === null && $data = Request::args($batch);
                $data && TagModel::update($data, $idArray);
                return true;
            },
        );
        return AdmincpBatch::run($actions, "标签");
    }
    public function do_api_extract()
    {
        $title   = html2text($_POST['title']);
        $content = html2text($_POST['content']);
        $words   = self::api_extract($title, $content);
        echo $words;
    }
    public static function api_extract($title = null, $content = null)
    {
        $array    = compact('title', 'content');
        $response = DeveloperApi::post('tag.extract', $array);
        return $response;
    }

    public static function widget_count()
    {
        $total = TagModel::count();
        $widget[] = array($total, '全部');
        foreach (Tag::$statusMap as $status => $text) {
            $count = TagModel::where('status', $status)->count();
            $widget[] = array($count, $text);
        }
        return $widget;
    }
}
