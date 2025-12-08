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
/**
 * 采集错误
 */
class SpiderErrorAdmincp extends SpiderBase
{
	use AdmincpCommonTrait;

	// public function __construct()
	// {
	// 	parent::__construct();
	// 	// DB::query("set session sql_mode='NO_ENGINE_SUBSTITUTION'");
	// }

	public function index($a = null)
	{
		$pid = Request::get('pid');
		$pid && $where['pid'] = $pid;
		$rid = Request::get('rid');
		$rid && $where['rid'] = $rid;
		$type = Request::get('type');
		$type && $where['type'] = $type;
		$days = Request::get('days',0);
		$days && $where[] = array('addtime', '>=', str2time('-' . $days . ' day'));

		return self::tableData([
			'where' => $where
		], function ($rows) {
			$rows = array_map(function ($row) {
				return $row;
			}, $rows);
			return $rows;
		});

        // $param = Request::param();

		// $model = SpiderErrorModel::where($where)->groupBy('pid DESC');
        // $total    = Paging::getTotal();
        // $total    = $total ?: $model->count();
		// $sql[] = $model->getQueryLog();
        // $pageSize = Paging::getPageSize(10);
        // $offset   = Paging::get($total, $pageSize);

		// $rows = $model->field('`pid`,`rid`,COUNT(id) AS ct,`date`')
		// ->orderBy(['ct DESC', '`date` DESC'])
		// ->limit($offset, $pageSize)
		// ->select();
		// $sql[] = $model->getQueryLog();

		// // $prefix = DB::getTablePrefix();
		// // $rs = DB::select("
		// //     SELECT
		// //       `pid`,`rid`,COUNT(id) AS ct,`date`
		// //     FROM
		// //       `{$prefix}spider_error`
		// //     {$sql}
		// //     GROUP BY pid DESC
		// //     ORDER BY ct DESC, `date` DESC
		// //     LIMIT {$pageSize}
		// // ");
        // return [
        //     "total" => Paging::$count,
        //     "rows" => $rows,
        //     "sql" => $sql,
        //     "fixedScroll" => true
        // ];
	}
	public function do_view()
	{
		$where = [['pid', Spider::$pid]];
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
	 * 删除错误信息
	 * @return void
	 */
	public function do_delete($id = null)
	{
		Spider::$pid or self::alert("请选择要删除的项目");
		SpiderErrorModel::delete(['pid' => Spider::$pid]);
	}

}
