<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class CommentUserApp extends UserContentApp
{
    public function API_manage()
    {
        return $this->display();
    }
    /**
     * 删除回复
     *
     * @return void
     */
    public function api_reply_delete()
    {
        $id = (int) Request::post('id');
        $where = ['id' => $id, 'userid' => User::$id];
        return (bool)CommentReplyModel::update(['status'=>CommentModel::USER_DELETE], $where);
    }
    /**
     * 删除评论
     *
     * @return void
     */
    public function api_delete()
    {
        $id = (int) Request::post('id');
        $where = ['id' => $id, 'userid' => User::$id];
        return (bool)CommentModel::update(['status'=>CommentModel::USER_DELETE], $where);
    }
}
