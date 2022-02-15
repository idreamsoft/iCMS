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

class CommentReplyApp
{
	public $methods = array('vote');
	public $config  = null;
	public function __construct()
	{
		$this->config = Config::get('comment');
		$this->id     = (int) $_GET['id'];
	}
	public function ACTION_vote()
	{
		$app = new AppsApp('comment');
		$app->model('CommentReplyModel');
		$app->ACTION_vote();
	}
	public function ACTION_add()
	{
		if (!$this->config['reply']['enable']) {
			iJson::error('comment:reply:close');
		}
		if ($this->config['reply']['captcha']) {
			Captcha::check() or iJson::error('iCMS:captcha:error');
		}
		UserCP::status();

		$post = Request::post();

		$post['param'] or iJson::error('iCMS:empty:param');
		$post['content'] or iJson::error('comment:reply:empty:content');

		$flag = iPHP::callback('Filter::run', [&$post['content']], false);
		$flag && iJson::error('comment:reply:filter');

		$param = is_array($post['param']) ? $post['param'] : json_decode($post['param'], true);
		is_array($post) && $post = array_merge($post, $param);

		$data = array();
		//回复评论的回复
		if ($post['reply_id'] && $post['comment_id']) {
			$data['reply_id'] = $post['reply_id']; //回复ID
			$data['comment_id'] = $post['comment_id']; //评论ID
		} else {
			//回复评论 $post['id'] 评论ID
			$data['comment_id'] = $post['id'];
		}
		$data['content']        = $post['content'];
		$data['reply_userid']   = $post['userid'];
		$data['reply_username'] = $post['username'];

		$data['create_time'] = $_SERVER['REQUEST_TIME'];
		$data['update_time'] = $_SERVER['REQUEST_TIME'];
		$data['ip'] = Request::ip();
		$data['userid'] = User::$id;
		$data['username'] = User::$nickname;
		$data['status'] = $this->config['reply']['examine'] ? '0' : '1';

		$data['id'] = CommentReplyModel::create($data, true);

		Comment::updateReplyCountInc($data['comment_id']);
		$data['reply_id'] && CommentReply::updateReplyCountInc($data['reply_id']);

		iJson::success(
			$data,
			$this->config['reply']['examine'] ?
				'comment:reply:examine' :
				'comment:reply:success'
		);
	}	
}
