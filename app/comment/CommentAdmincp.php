<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class CommentAdmincp extends AdmincpCommon
{
    use AdmincpCommonTrait;

    public function do_index()
    {
        self::setContext([
            'tablist' => 'params',
            'params'  => [
                'status' => Request::get('status'),
            ]
        ]);

        if (Request::isAjax()) {
            return $this->index();
        }

        self::setJsPath('comment/assets/js/index');
        include self::view('index', 'comment');
    }

    public function index()
    {
        $appid = (int)Request::get('appid');
        $iid = (int)Request::get('iid');
        $userid = (int)Request::get('userid');
        $ip = Request::get('ip');
        $id = (int)Request::get('id');

        $appid && $where['appid'] = $appid;
        $iid && $where['iid'] = $iid;
        $userid && $where['userid'] = $userid;
        $ip && $where['ip'] = $ip;

        $status = Request::get('status');
        is_numeric($status) && $where['status'] = $status;

        if ($cid = (int)Request::get('cid')) {
            Node::makeWhere($where, $cid);
        }

        $id && $where['id'] = $id;
        ;

        $keywords = Request::get('keywords');
        $keywords && $where['CONCAT(username,title)'] = ['REGEXP', $keywords];

        // $result = CommentModel::where($where)
        //     ->orderBy($orderby)
        //     ->paging();
        // Node::$APPID = $appid;
        // include self::view('index1');

        return  self::tableData(['where' => $where], function ($rows) {
            $rows = array_map(function ($row) {
                return $row;
            }, $rows);
            return $rows;
        });
    }

    /**
     * 查看评论回复
     * @return void
     */
    public function do_reply()
    {
        if (Request::isAjax()) {
            $idArray = (array)Request::param('ids');
            $comment_id = (int)Request::param('comment_id');
            $userid = (int)Request::param('userid');
            $status = Request::param('status');
            $ip = Request::param('ip');

            $where = [];
            $idArray && $where['comment_id'] = $idArray;
            $comment_id && $where['comment_id'] = $comment_id;
            $userid && $where['userid'] = $userid;
            $ip && $where['ip'] = $ip;
            isset($status) && $where['status'] = $status;

            return  self::tableData('CommentReplyModel', ['where' => $where], function ($rows) {
                $rows = array_map(function ($row) {
                    return $row;
                }, $rows);
                return $rows;
            });
        }

        include self::view('reply');
    }

    public function batching()
    {
        return [
            'reply_examine' => function ($ids, $data, $batch) {
                foreach ($ids as $key => $id) {
                    $flag = false;
                    $data && $flag = CommentReplyModel::update($data, $id);
                    if ($flag && isset(self::$CONFIG['reply']) && self::$CONFIG['reply']['examine']) {
                        $comment_ids = CommentReplyModel::where($ids)->pluck('comment_id');
                        if ($comment_ids) {
                            $comment_ids = array_unique($comment_ids);
                            if ($batch == 'status=1') {
                                Comment::updateReplyCountInc($comment_ids);
                            }
                            if ($batch == 'status=4') {
                                Comment::updateReplyCountDec($comment_ids);
                            }
                        }
                    }
                }
            }
        ];
    }

    public function deleting($ids = [])
    {
        if ($ids) {
            foreach ($ids as $key => $id) {
                Comment::remove($id);
            }
        }
        return true;
    }

    public static function widget_count()
    {
        $total = CommentModel::count();
        $widget[] = [$total, '全部'];
        foreach (CommentModel::$statusMap as $status => $text) {
            $count = CommentModel::where('status', $status)->count();
            $widget[] = [$count, $text];
        }
        return $widget;
    }
}
