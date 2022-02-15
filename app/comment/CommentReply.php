<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class CommentReply
{
    public function __construct()
    {
    }
    public static function remove($id = 0, $userid = null)
    {
        $data = CommentReplyModel::get($id);
        $where['id'] = $id;
        $userid  && $where['userid'] = $userid;
        CommentReplyModel::where($where)->delete();
		Apps::updateDec('comment', $data['iid'], iCMS_APP_COMMENT);
    }
    public static function delete($appid, $iid)
    {
        $where['iid'] = $iid;
        $where['appid'] = $appid;
        CommentReplyModel::where($where)->delete();
    }
    public static function updateReplyCountInc($id, $step = 1)
    {
        return CommentReplyModel::where($id)->inc('reply_count', $step);
    }
    public static function updateReplyCountDec($id, $step = 1)
    {
        return CommentReplyModel::where($id)->where('reply_count', '>', '0')->dec('reply_count', $step);
    }
}
