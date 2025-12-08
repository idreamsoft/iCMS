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
class CommentReplyFunc extends AppsFuncCommon
{
    protected static $app = Comment::APP;
    protected static $appid = Comment::APPID;

    public static function lists($vars)
    {
        if (!Config::get('comment.reply.enable')) {
            return;
        }

        $whereNot = [];
        $resource = [];
        $model = CommentReplyModel::field('id');
        $status = isset($vars['status']) ? $vars['status'] : 1;
        $where = [['status', $status]];

        isset($vars['comment_id']) && $where[] = ['comment_id', $vars['comment_id']];
        isset($vars['userid']) && $where[] = ['userid', $vars['userid']];
        
        empty($vars['by']) && $vars['by'] = 'ASC';
        self::inited($vars, $model, $where, $whereNot);
        self::orderby([]);
        self::where();
        $hash = md5($model->getSql());
        self::paging($hash, __METHOD__);
        $total = self::$pageData['total'];
        $pageSize = self::$pageData['pageSize'];
        $pageNow = is_object(self::$PAGES) ? self::$PAGES->nowindex : 0;

        $resource = self::getCache(self::$cacheName);
        if (empty($resource)) {
            $idsArray = self::getIds();
            if ($idsArray) {
                $resource = CommentReplyModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
                $ln = ($pageNow - 1) < 0 ? 0 : $pageNow - 1;
                if ($resource) {
                    $userInfo = User::column($resource, 'userid', 'username');
                    $replyUserInfo = User::column($resource, 'reply_userid', 'reply_username');
                    
                    $events = ['reply_up'=>'up', 'reply_down'=>'down'];
                    $UserTimeline = UserTimeline::user_events($events,Comment::APPID);

                    foreach ($resource as $key => $value) {
                        $value['status_text'] = CommentReplyModel::$statusMap[$value['status']];
                        // $value = CommentApp::value($value, $vars);
                        if(!$vars['isRaw']){
                            $value['content'] = nl2br($value['content']);
                        }
                        $value['user'] = $userInfo[$value['userid']];
                        $value['reply_user'] = $value['reply_userid']?$replyUserInfo[$value['reply_userid']]:[];
                        $value['params'] = [
                            'app'        => Comment::APP,
                            'appid'      => Comment::APPID,
                            'id'         => (int) $value['id'],
                            'reply_id'   => (int) $value['id'],
                            'comment_id' => (int) $value['comment_id'],
                            'userid'     => (int) $value['userid'],
                            'username'   => $value['username'],
                        ];
                        $value['is_up'] = false;
                        $value['is_down'] = false;
                        if (isset($UserTimeline[$value['id']])) {
                            $value = array_merge($value,$UserTimeline[$value['id']]);
                        }
                        if ($vars['by'] == 'ASC') {
                            $value['lou'] = $key + $ln * $pageSize + 1;
                        } else {
                            $value['lou'] = $total - ($key + $ln * $pageSize);
                        }
                        $value['total'] = $total;
                        $resource[$key] = $value;
                    }
                }
            }
            $cacheTime = isset($vars['time']) ? (int) $vars['time'] : -1;
            $vars['cache'] && Cache::set(self::$cacheName, $resource, $cacheTime);
        }
        return (array)$resource;
    }

    public static function form($vars)
    {
        
    }
}
