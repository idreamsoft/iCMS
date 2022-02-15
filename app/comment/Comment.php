<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class Comment
{
    const APP = 'comment';
    const APPID = iCMS_APP_COMMENT;

    public static $statusMap = array(
        '0' => '草稿',
        '1' => '正常',
        '2' => '回收站',
        '3' => '待审核',
        '4' => '未通过'
    );

    public function __construct()
    {
    }
    public static function remove($id = 0, $userid = null)
    {
        $data = CommentModel::get($id);
        $where['id'] = $id;
        $userid  && $where['userid'] = $userid;
        CommentModel::where($where)->delete();
		Apps::updateDec('comment', $data['iid'], $data['appid']);
		User::updateDec('comment', $data['userid']);
    }
    public static function delete($appid, $iid)
    {
        $where['iid'] = $iid;
        $where['appid'] = $appid;
        CommentModel::where($where)->delete();
    }
    public static function updateReplyCountInc($id, $step = 1)
    {
        return CommentModel::where($id)->inc('reply_count', $step);
    }
    public static function updateReplyCountDec($id, $step = 1)
    {
        return CommentModel::where($id)->where('reply_count', '>', '0')->dec('reply_count', $step);
    }
}
