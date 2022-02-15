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

class SpiderErrorAdmincp extends AdmincpBase
{
	public $ACCESC_TITLE = '采集错误';

	public function __construct()
	{
		DB::query("set session sql_mode='NO_ENGINE_SUBSTITUTION'");
		SpiderAdmincp::init($this);
	}
	/**
	 * [采集错误结果管理]
	 * @return [type] [description]
	 */
	public function do_manage()
	{
		$sql = " WHERE 1=1";
		$_GET['pid'] && $sql .= " AND `pid` ='" . (int) $_GET['pid'] . "'";
		$_GET['rid'] && $sql .= " AND `rid` ='" . (int) $_GET['rid'] . "'";
		$days = $_GET['days'] ? $_GET['days'] : 0;
		$days && $sql .= " AND `addtime`>" . strtotime('-' . $days . ' day');
		// $postArray = $this->post_opt(0, 'array');
		// $orderby = self::setOrderBy();
		$pageSize = $_GET['pageSize'] > 0 ? (int) $_GET['pageSize'] : 100;
		// $total = Paging::totalCache( "SELECT count(*) FROM `#iCMS@__spider_error` {$sql}", "G");
		// Paging::get($total, $pageSize, "个网页");
		// $rs = DB::select("SELECT * FROM `#iCMS@__spider_error` {$sql} order by {$orderby} LIMIT " . Paging::$offset . " , {$pageSize}");

		$prefix = DB::getTablePrefix();
		$rs = DB::select("
		    SELECT
		      `pid`,`rid`,COUNT(id) AS ct,`date`
		    FROM
		      `{$prefix}spider_error`
		    {$sql}
		    GROUP BY pid DESC
		    ORDER BY ct DESC, `date` DESC
		    LIMIT {$pageSize}
		");

		$_count = count($rs);
		include self::view("error.manage");
	}
	public function do_view()
	{
		$where = [['pid', $this->pid]];
		$date = Request::get('date');
		$date && $where[] = ['date', $date];

		$days = Request::get('days', 0);
		$days && $where[] = array('addtime', '>=', strtotime('-' . $days . ' day'));

		$rs = SpiderErrorModel::field([
			'*',
			'COUNT(id) AS ct',
			'group_concat(`msg`) as `msg`',
			'group_concat(`type`) as `type`'
		])
			->where($where)
			->groupBy('url')
			->orderBy('id', 'DESC')
			->select();

		include self::view("error.view");
	}
	/**
	 * [删除错误信息]
	 * @return [type] [description]
	 */
	public function do_delete($id = null)
	{
		$this->pid or self::alert("请选择要删除的项目");
		SpiderErrorModel::delete(['pid' => $this->pid]);
		self::success('删除完成');
	}
}
