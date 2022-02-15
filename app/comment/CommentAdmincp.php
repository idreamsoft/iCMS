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
    public function __construct()
    {
        parent::__construct();
        $this->id = (int) $_GET['id'];
    }

    public function do_manage($appid = 0)
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
            Node::makeWhere($where,$cid);
        }

        $id && $where['id'] = $id;;

        $keywords = Request::get('keywords');
        $keywords && $where['CONCAT(username,title)'] = array('REGEXP', $keywords);

        $orderby = self::setOrderBy();
        $result = CommentModel::where($where)
            ->orderBy($orderby)
            ->paging();

        Node::$APPID = $appid;

        include self::view("comment.manage");
    }

    /**
     * [查看评论回复]
     * @return [type] [description]
     */
    public function do_getReply()
    {
        $idArray = (array)Request::post('ids');
        empty($idArray) && self::alert('请选择要回复的评论');
        $idArray = array_map('intval', $idArray);
        $comment = CommentModel::field('id,userid,username,content,addtime,up')->where($idArray)->select();
        foreach ($comment as $key => &$value) {
            $value['addtime'] = get_date($value['addtime'], 'Y-m-d H:i:s');
        }
        return $comment;
        // self::success($comment);
    }
    public function do_delete($id = null)
    {
        $id === null && $id = $this->id;
        $id or self::alert('请选择要删除的评论');
        Comment::remove($id);
        // $dialog && self::success('评论删除完成');
    }

    public function do_batch()
    {
        $stype = AdmincpBatch::$config['stype'];
        $actions = array(
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    Comment::remove($id);
                }
                // self::success('删除完成');
            },
            'default' => function ($idArray, $ids, $batch, $data = null) {
                $data === null && $data = Request::args($batch);
                $data && CommentModel::update($data, $idArray);
                // return true;
            },
        );
        return AdmincpBatch::run($actions, "评论");
    }

    public static function widget_count()
    {
        $total = CommentModel::count();
        $widget[] = array($total, '全部');
        foreach (Comment::$statusMap as $status => $text) {
            $count = CommentModel::where('status', $status)->count();
            $widget[] = array($count, $text);
        }
        return $widget;
    }
}
