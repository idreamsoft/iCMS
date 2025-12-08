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

class SpiderUrlAdmincp extends SpiderBase
{
    use AdmincpCommonTrait;

    public function do_delcontent()
    {
        $indexid = Request::sget('indexid');
        $indexid or self::alert('请选择要删除的项目');

        $project = SpiderProject::get(Spider::$pid);
        $spost = SpiderPost::get($project['poid']);
        $app = Apps::getData($spost->app);
        $obj = $spost->app . 'Admincp';
        $acp = new $obj();
        if (method_exists($acp, 'do_delete')) {
            $acp->do_delete($indexid, false);
        // $this->do_delspider(false);
        // self::success('删除完成');
        }
    }

    /**
     * 采集结果管理
     */
    public function do_index()
    {
        $where = [];
        if ($sdo = Request::sget('sdo')) {
            if ($sdo == 'inbox') {
                $where['publish'] = '0';
            }
        }

        $pid = Request::get('pid');
        $pid && $where['pid'] = $pid;

        $rid = Request::get('rid');
        $rid && $where['rid'] = $rid;

        $status = Request::get('status');
        isset($status) && $where['status'] = $status;
        $publish = Request::get('publish');
        isset($publish) && $where['publish'] = $publish;

        $starttime = Request::get('starttime');
        $starttime && $where[] = ['addtime', '>=', str2time($starttime . (strpos($starttime, ' ') !== false ? '' : ' 00:00:00'))];

        $endtime = Request::get('endtime');
        $endtime && $where[] = ['addtime', '<=', str2time($endtime . (strpos($endtime, ' ') !== false ? '' : ' 00:00:00'))];

        Node::makeWhere($where, Spider::$cid);

        $keywords = Request::get('keywords');
        $field = Request::sget('field') ?: 'title';
        $keywords && $where[$field] = ['like', '%' . $keywords . '%'];
        if (Request::isAjax()) {
            return self::tableData('SpiderUrlModel', [
                'where' => $where
            ], function ($rows) {
                $rows = array_map(function ($row) {
                    return $row;
                }, $rows);
                return $rows;
            });
        }
        self::setContext([
            'params' => [
                'rid'     => $where['rid'],
                'status'  => $where['status'],
                'publish' => $where['publish'],
            ],
            'tablist' => 'params'
        ]);
        include self::view();
    }

    /**
     * 移除标记
     * @return void
     */
    public function do_mark()
    {
        SpiderUrl::create([
            'cid'     => Spider::$cid,
            'rid'     => Spider::$rid,
            'pid'     => Spider::$pid,
            'title'   => Spider::$title,
            'url'     => Spider::$url,
            'status'  => '0', //未采集
            'publish' => '2', //标记移除
            'indexid' => '0',
            'pubdate' => '0',
        ]);
    }

    /**
     * 删除采集数据
     * @return void
     */
    public function do_dropdata()
    {
        Spider::$pid or self::alert('请选择要删除的项目');
        $where = ['pid' => Spider::$pid];
        $rs = SpiderUrlModel::field('indexid,appid,pid')->where($where)->select();
        $project = SpiderProject::get(Spider::$pid);
        $post = SpiderPost::get($project['poid']);
        $_count = count($rs);
        for ($i = 0; $i < $_count; $i++) {
            $class = $post->app . 'Admincp';
            $delete = 'do_delete';
            if (@class_exists($class) && @method_exists($class, 'do_del')) {
                if ($post->app == 'content') {
                    $obj = new $class($rs[$i]['appid']);
                } elseif ($post->app == 'forms') {
                    $obj = new $class();
                    $delete = 'do_delete';
                } else {
                    $obj = new $class();
                }
                iPHP::callback([$obj, $delete], [$rs[$i]['indexid'], false]);
            } else {
                $msg = '未找到内容删除方法,请手动删除内容';
            }
        }
        $msg && self::alert($msg);
        SpiderUrlModel::where($where)->delete();
    }

    /**
     * 删除采集结果数据
     * @return void
     */
    public function do_dropurl()
    {
        Spider::$pid or self::alert('请选择要删除的项目');
        $where = ['pid' => Spider::$pid];
        $type = Request::sget('type');
        if ($type == '0') {
            $where['publish'] = 0;
        }
        SpiderUrlModel::where($where)->delete();
    }
}
