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
class CommentFunc extends AppsFuncCommon
{
	/**
	 * iCMS:comment:
	 *
	 * @param [type] $vars
	 * @return void
	 */
	public static function data($vars)
	{
		$where  = array();
		$whereNot  = array();
		$resource  = array();
		$model     = CommentModel::field('id');
		$status    = isset($vars['status']) ? (int) $vars['status'] : 1;
		$where['status'] = $status;

		isset($vars['id']) && $where['id'] = $vars['id'];
		isset($vars['userid']) && $where['userid'] = $vars['userid'];

		if (isset($vars['loop'])) {
			$resource = $model->select();
			if ($resource) {
				$resource = array_column($resource, null, 'id');
				foreach ($resource as $key => &$value) {
					$value['user'] = User::info($value['userid'], $value['username'], $vars['facesize']);;
				}
			}
		} else {
			$resource = $model->get();
			$resource['user'] = User::info($resource['userid'], $resource['username'], $vars['facesize']);
		}
		return $resource;
	}
	private static function list_display($vars)
	{
		$vars['app'] 		 = 'comment';
		$vars['do']          = 'list';
		$vars['page_ajax']   = 'comment_page_ajax';
		$vars['total_cache'] = 'G';
		isset($vars['_display']) && $vars['display'] = $vars['_display'];
		unset($vars['method'], $vars['_display']);
		View::unvars($vars);
		$vars['query'] = http_build_query($vars);
		$vars['param'] = array(
			'suid'  => (int) $vars['suid'],
			'iid'   => (int) $vars['iid'],
			'cid'   => (int) $vars['cid'],
			'appid' => (int) $vars['appid'],
			'title' => Security::escapeStr($vars['title']),
		);
		$tpl = 'list.default';
		View::assign('comment_vars', $vars);
		View::display("iCMS://comment/{$tpl}.htm");
	}

	public static function lists($vars)
	{
		if (!Config::get('comment.enable')) {
			return;
		}

		// if ($vars['display'] && empty($vars['loop'])) {
		// 	$_vars = View::app_vars(true);
		// 	$vars  = array_merge($vars, (array) $_vars);
		// 	$vars['iid']   or Script::warning('iCMS&#x3a;comment&#x3a;list 标签出错! 缺少参数"iid"或"iid"值为空.');
		// 	$vars['appid'] or Script::warning('iCMS&#x3a;comment&#x3a;list 标签出错! 缺少参数"appid"或"appid"值为空.');
		// 	return commentFunc::list_display($vars);
		// }

		$whereNot  = array();
		$resource  = array();
		$model     = CommentModel::field('id');
		$status    = isset($vars['status']) ? (int) $vars['status'] : 1;
		$where     = [['status', $status]];

		isset($vars['appid']) && $where[] = ['appid', $vars['appid']];
		isset($vars['userid']) && $where[] = ['userid', $vars['userid']];
		isset($vars['pid']) && $where[] = ['pid', $vars['pid']];
		isset($vars['iid']) && $where[] = ['iid', $vars['iid']];
		isset($vars['indexid']) && $where[] = ['iid', $vars['indexid']];
		!isset($vars['page_ajax']) && $vars['page_ajax'] = true;

		self::init($vars, $model, $where, $whereNot);
		self::setApp(Comment::APPID, Comment::APP);
		self::nodes('cid');
		self::orderby([]);
		self::where();
		return self::getResource(__METHOD__, [__CLASS__, 'resource']);
	}
	
	public static function resource($vars, $idsArray = null, $paging = null)
	{
		$vars['ids'] && $idsArray = $vars['ids'];
		
		list($total, $offset, $pageSize, $pageNow) = $paging;

		$resource = CommentModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
		$ln = ($pageNow - 1) < 0 ? 0 : $pageNow - 1;
		if ($resource) foreach ($resource as $key => $value) {
			$value = CommentApp::value($value, $vars);
			$value['apps'] = Apps::getDataLite($value['appid']);
			if ($vars['by'] == 'ASC') {
				$value['lou'] = $key + $ln * $pageSize + 1;
			} else {
				$value['lou'] = $total - ($key + $ln * $pageSize);
			}
			$value['total'] = $total;
			$resource[$key] = $value;
		}

		return $resource;
	}
	public static function reply($vars)
	{
		if (!Config::get('comment.reply.enable')) {
			return;
		}

		$whereNot  = array();
		$resource  = array();
		$model     = CommentReplyModel::field('id');
		$status    = isset($vars['status']) ? (int) $vars['status'] : 1;
		$where     = [['status', $status]];

		isset($vars['comment_id']) && $where[] = ['comment_id', $vars['comment_id']];
		isset($vars['userid']) && $where[] = ['userid', $vars['userid']];

		empty($vars['by']) && $vars['by'] = 'ASC';
		self::init($vars, $model, $where, $whereNot);
		self::orderby([]);
		self::where();

		$vars['distinct'] && $distinct = $vars['distinct'];

		$whereNot && $model->where($whereNot);
		$where && $model->where($where);
		$distinct && $model->distinct($distinct);
		$hash = md5($model->getSql());

		if (!isset($vars['page_ajax'])) {
			$vars['page_ajax'] = true;
		}

		$paging = self::paging($hash, __METHOD__);
		list($total, $offset, $pageSize, $pageTotal, $PAGES) = $paging;
        $pageNow = $PAGES?$PAGES->nowindex:0;

		$cacheName = sprintf('%s/%s/%s/%d_%d', iPHP_DEVICE, Comment::APP, $hash, $offset, $pageSize);
		$resource  = self::getCache($cacheName);
		if (empty($resource)) {
			$idsArray = self::getIds($paging);
			if ($idsArray) {
				$resource = CommentReplyModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
				$ln = ($pageNow - 1) < 0 ? 0 : $pageNow - 1;
				if ($resource) foreach ($resource as $key => $value) {
					// $value = CommentApp::value($value, $vars);
					$value['content'] = nl2br($value['content']);
					$value['user']    = User::info($value['userid'], $value['username'], $vars['facesize']);
					$value['reply_user']    = User::info($value['reply_userid'], $value['reply_username'], $vars['facesize']);
					$value['param'] = array(
						"app" => Comment::APP,
						"appid" =>  Comment::APPID,	
						"id" => (int) $value['id'],
						"reply_id" => (int) $value['id'],
						"comment_id" => (int) $value['comment_id'],
						"userid"   => (int) $value['userid'],
						"username" => $value['username'],
					);
					if ($vars['by'] == 'ASC') {
						$value['lou'] = $key + $ln * $pageSize + 1;
					} else {
						$value['lou'] = $total - ($key + $ln * $pageSize);
					}
					$value['total'] = $total;
					$resource[$key] = $value;
				}
			}
			$cacheTime = isset($vars['time']) ? (int) $vars['time'] : -1;
			$vars['cache'] && Cache::set($cacheName, $resource, $cacheTime);
		}
		return $resource;
	}
	public static function form($vars)
	{
		if (!Config::get('comment.enable')) {
			return;
		}

		if (isset($vars['param'])) {
			$vars  = array_merge($vars, $vars['param']);
		} elseif (!isset($vars['ref'])) {
			$_vars = View::appVars(true);
			$_vars && $vars  = array_merge($vars, $_vars);
			unset($vars['ref'], $_vars);
		}

		if ($vars['throw'] !== false) {
			$vars['iid']   or Script::warning('iCMS&#x3a;comment&#x3a;form 标签出错! 缺少参数"iid"或"iid"值为空.');
			// $vars['cid']   or Script::warning('iCMS&#x3a;comment&#x3a;form 标签出错! 缺少参数"cid"或"cid"值为空.');
			$vars['appid'] or Script::warning('iCMS&#x3a;comment&#x3a;form 标签出错! 缺少参数"appid"或"appid"值为空.');
			$vars['title'] or Script::warning('iCMS&#x3a;comment&#x3a;form 标签出错! 缺少参数"title"或"title"值为空.');
		}

		switch ($vars['display']) {
			case 'iframe':
				$tpl        = 'form.iframe';
				$vars['do'] = 'form';
				break;
			default:
				isset($vars['_display']) && $vars['display'] = $vars['_display'];
				$vars['param'] = array(
					'iid'   => (int) $vars['iid'],
					'cid'   => (int) $vars['cid'],
					'appid' => (int) $vars['appid'],
					'userid'    => (int) $vars['userid'],
					'username'  => Security::escapeStr($vars['username']),
					'title' => Security::escapeStr($vars['title']),
				);
				$tpl = 'form';
				break;
		}
		$vars['hidden'] && $vars['class'] .= " hide";
		View::assign('comment_vars', $vars);
		View::display('iCMS://comment/' . $tpl . '.htm');
	}
}
