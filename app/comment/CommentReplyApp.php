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

class CommentReplyApp extends AppsBase
{
    public $config = null;
    public static $LOGIN = ['vote','add'];

    public function __construct()
    {
        $this->config = Config::get('comment');
    }

    public function do_vlist()
    {
        return CommentReply::getList([
            'comment_id'=> 1,
            'row'       => 5
        ]);
    }

    public function api_vlist()
    {
        $params = Request::post('params');
        $id = (int)Request::post('id');
        $row = (int)Request::post('row',5);
        $comment_id = (int)$params['id'];
        $vars = [
            'comment_id'=> $comment_id,
            'row'       => $row
        ];
        $id && $vars['id'] = $id;
        return CommentReply::getList($vars);
    }

    public function api_vote()
    {
        $params = Request::post('params');
        $params or self::alert('errors:empty:params');
        $event = Request::spost('event');
        $id = (int) $params['id'];
        $appid = (int) $params['appid'];
        $userid = (int) User::$id;

        $UserTimeline = UserTimeline::user_iid_events(
            ['reply_up', 'reply_down'],
            $appid,
            $userid,
            $id
        );
        //赞同时删除 反对 记录
        $model = CommentReplyModel::where($id);
        $result = [
            'is_up'  => false,
            'is_down'=> false,
        ];
        if ($event == 'reply_down') {
            $result['is_down'] = true;
            //反对时 赞同数-1并删除赞同记录
            if ($utlId = $UserTimeline['reply_up']) {
                $model->where('up', '>', 0)->dec('up');
            } elseif ($utlId = $UserTimeline['reply_down']) {
                //已有反对记录 反对数-1并删除反对记录  (取消反对操作)
                $model->where('down', '>', 0)->dec('down');
                $result['is_down'] = false;
            }
            $utlId && UserTimeline::delete($utlId);
            if ($result['is_down']) {
                CommentReplyModel::where($id)->inc('down');
                UserTimeline::add($appid, $id, $event, $userid);
            }
            $result['is_up'] = false;
        } elseif ($event == 'reply_up') {
            $result['is_up'] = true;
            //赞同时 反对数-1并删除记录
            if ($utlId = $UserTimeline['reply_down']) {
                $model->where('down', '>', 0)->dec('down');
            } elseif ($utlId = $UserTimeline['reply_up']) {
                //已有赞同记录 赞同数-1并删除赞同  (取消赞同操作)
                $model->where('up', '>', 0)->dec('up');
                $result['is_up'] = false;
            }
            $utlId && UserTimeline::delete($utlId);
            if ($result['is_up']) {
                CommentReplyModel::where($id)->inc('up');
                UserTimeline::add($appid, $id, $event, $userid);
            }
            $result['is_down'] = false;
        }
        $result += CommentReplyModel::where($id)->field('up,down')->get();
        $result['message'] = sprintf('comment:reply:vote:%s:success', $event);
        return $result;
    }

    public function done_add()
    {        
        if (!$this->config['reply']['enable']) {
            self::alert('comment:reply:close');
        }
        $post = Request::post();
        if ($this->config['reply']['captcha']) {
            Captcha::check($post['captcha'], 'reply');
        }
        $post['content'] or self::alert('comment:reply:empty:content');
        $params = $post['params'];
        if (!is_array($post['params'])) {
            $params = json_decode($post['params'], true);
        }
        empty($params) && self::alert('errors:empty:param');

        $flag = iPHP::callback('Filter::run', [&$post, 'content'], false);
        $flag && self::alert('comment:reply:filter');

        $data = [];
        //回复评论的回复
        if ($params['reply_id'] && $params['comment_id']) {
            $data['reply_id'] = $params['reply_id']; //回复ID
            $data['comment_id'] = $params['comment_id']; //评论ID
            $data['reply_userid'] = $params['userid'];
            $data['reply_username'] = $params['username'];
        } else {
            //回复评论 $post['id'] 评论ID
            $data['comment_id'] = $post['id'];
        }
        empty($data['comment_id']) && self::alert('comment:reply:empty:commentId');

        $data['content'] = $post['content'];

        $data['create_time'] = $_SERVER['REQUEST_TIME'];
        $data['update_time'] = $_SERVER['REQUEST_TIME'];
        $data['ip'] = Request::ip();
        $data['userid'] = User::$id;
        $data['username'] = User::$nickname;
        $data['status'] = $this->config['reply']['examine'] ? CommentModel::EXAMINE : '1';

        $data['id'] = CommentReplyModel::create($data, true);

        $this->config['reply']['examine'] OR Comment::updateReplyCountInc($data['comment_id']);
        $data['reply_id'] && CommentReply::updateReplyCountInc($data['reply_id']);
        
        return [
            'data'   => $data,
            'message'=> $this->config['reply']['examine'] ?
                        'comment:reply:examine' :
                        'comment:reply:success'
        ];
    }
}
