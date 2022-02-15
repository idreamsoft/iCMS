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

class SpiderPostAdmincp extends AdmincpBase
{
	public $ACCESC_TITLE = '采集发布';

	public function __construct()
	{
		SpiderAdmincp::init($this);
	}
	public function do_batch()
	{
		$stype = AdmincpBatch::$config['stype'];
		$actions = array(
			'dels' => array('删除', 'trash-alt', 'run' => function ($idArray, $ids, $batch) {
				SpiderPostModel::delete($idArray);
				return true;
			})
		);
		return AdmincpBatch::run($actions, "属性");
	}
	/**
	 * [发布模块管理]
	 * @return [type] [description]
	 */
	public function do_manage()
	{
		$keyword = Request::get('keyword');
		$keyword && $where['CONCAT(name,app,post)'] = array('REGEXP', $keyword);
		$cid = Request::get('cid');
		$cid && $where['cid'] = $cid;

		$orderby = self::setOrderBy();
		$result = SpiderPostModel::where($where)
			->orderBy($orderby)
			->paging();

		include self::view("post.manage");
	}
	/**
	 * [复制发布模块]
	 * @return [type] [description]
	 */
	public function do_copy()
	{
		$data = SpiderPostModel::withoutField('id')->get($this->poid);
		$poid = SpiderPostModel::create($data);
	}
	/**
	 * [删除发布模块]
	 * @return [type] [description]
	 */
	public function do_delete($id = null)
	{
		$this->poid or self::alert("请选择要删除的项目");
		SpiderPostModel::delete($this->poid);
	}
	/**
	 * [添加发布模块]
	 * @return [type] [description]
	 */
	public function do_add()
	{
		$this->poid && $rs = SpiderPostModel::get($this->poid);
		include self::view("post.add");
	}
	/**
	 * [保存发布模块]
	 * @return [type] [description]
	 */
	public function save()
	{
		$data = Request::post();
		$data['app'] = $data['_app'];
		unset($data['_app']);
		if ($data['id']) {
			SpiderPostModel::update($data, $data['id']);
		} else {
			SpiderPostModel::create($data, true);
		}
	}
}
