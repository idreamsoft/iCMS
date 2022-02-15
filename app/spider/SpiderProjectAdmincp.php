<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 * @title 
 */
defined('iPHP') or exit('What are you doing?');

class SpiderProjectAdmincp extends AdmincpCommon
{
	public $ACCESC_TITLE = '采集方案';
	public function __construct()
	{
		SpiderAdmincp::init($this);
	}
	/**
	 * [测试采集方案]
	 * @return [type] [description]
	 */
	public function do_test()
	{
		Spider::$isTest = true;
		try {
			SpiderData::crawl();
		} catch (\sException $ex) {
			throw $ex;
		}
	}
	/**
	 * [复制采集方案]
	 * @return [type] [description]
	 */
	public function do_copy()
	{
		$data = SpiderProjectModel::get($this->pid);
		unset($data['id']);
		$pid = SpiderProjectModel::create($data, true);
	}
	public function do_batch()
	{
		$stype = AdmincpBatch::$config['stype'];
		$actions = array(
			'auto;1' => array('标识自动采集', 'check-square', 'run' => 'default'),
			'auto;0' => array('取消自动采集', 'circle-o', 'run' => 'default'),
			'lastupdate;0' => array('重置最后采集时间', 'calendar', 'run' => 'default'),
			'poid' => array('设置发布规则', 'magnet', 'run' => function ($idArray, $ids, $batch) {
				$poid = (int)$_POST['poid'];
				SpiderProjectModel::update(compact('poid'), ['id' => $idArray]);
				return true;
			}),
			'rid' => array('设置采集规则', 'magnet', 'run' => function ($idArray, $ids, $batch) {
				$rid = (int)$_POST['rid'];
				SpiderProjectModel::update(compact('rid'), ['id' => $idArray]);
				return true;
			}),
			'move' => array('设置发布栏目', 'fighter-jet', 'run' => function ($idArray, $ids, $batch) {
				$cid = (int)$_POST['cid'];
				SpiderProjectModel::update(compact('cid'), ['id' => $idArray]);
				return true;
			}),
			'dels' => array('永久删除', 'trash-alt', 'run' => function ($idArray, $ids, $batch) {
				SpiderProjectModel::delete($idArray);
				return true;
			}),
			'default' => function ($idArray, $ids, $batch, $data = null) {
				if (strpos($batch, ':') !== false) {
					$data = Request::args($batch);
					$data && SpiderProjectModel::update($data, ['id' => $idArray]);
					return true;
				}
			}
		);
		return AdmincpBatch::run($actions, "方案");
	}
	/**
	 * [采集方案管理]
	 * @return [type] [description]
	 */
	public function do_manage($a = null)
	{


		$starttime = Request::get('starttime');
		$starttime && $where[] = array('lastupdate', '>=', str2time($starttime . (strpos($starttime, ' ') !== false ? '' : " 00:00:00")));

		$endtime = Request::get('endtime');
		$endtime && $where[] = array('lastupdate', '<=', str2time($endtime . (strpos($endtime, ' ') !== false ? '' : " 00:00:00")));

		Node::makeWhere($where, $this->cid);
		$rid = Request::get('rid');
		$rid && $where['rid'] = $rid;

		$auto = Request::get('auto');
		$auto && $where['auto'] = $auto;

		$poid = Request::get('poid');
		$poid && $where['poid'] = $poid;


		$keyword = Request::get('keyword');
		$keyword && $where['CONCAT(name)'] = array('REGEXP', $keyword);

		$orderby = self::setOrderBy();
		$result = SpiderProjectModel::where($where)
			->orderBy($orderby)
			->paging();


		include self::view("project.manage");
	}
	/**
	 * [删除采集方案]
	 * @return [type] [description]
	 */
    public function do_delete($id = null)
	{
		$this->pid or self::alert("请选择要删除的项目");
		SpiderProjectModel::delete($this->pid);
	}
	/**
	 * [添加采集方案]
	 * @return [type] [description]
	 */
	public function do_add()
	{
		$rs = array();
		$this->pid && $rs = SpiderProject::get($this->pid);
		$cid = empty($rs['cid']) ? $this->cid : $rs['cid'];

		$cata_option = Node::select($cid);
		//$rs['sleep'] OR $rs['sleep'] = 30;
		include self::view("project.add");
	}
	/**
	 * [保存采集方案]
	 * @return [type] [description]
	 */
	public function save()
	{
		$data = Request::post();

		$data['lastupdate'] = $data['lastupdate'] ? str2time($data['lastupdate']) : '0';

		empty($data['name']) && self::alert('名称不能为空！');
		empty($data['cid']) && self::alert('请选择绑定的栏目');
		empty($data['rid']) && self::alert('请选择采集规则');
		if ($data['id']) {
			SpiderProjectModel::update($data, $data['id']);
		} else {
			SpiderProjectModel::create($data, true);
		}
	}
	/**
	 * [导入采集方案]
	 * @return [type] [description]
	 */
	public function do_import()
	{
		Files::$check_data        = false;
		FilesCloud::$enable      = false;
		FilesClient::$config['allow_ext'] = 'txt';
		$file = FilesClient::upload('upfile', 'spider');
		$path = FilesClient::getRoot($file['path']);
		if ($path) {
			$data = file_get_contents($path);
			@unlink($path);
			if ($data) {
				$data = base64_decode($data);
				$data = json_decode($data, true);
				if (is_array($data)) {
					foreach ($data as $key => $value) {
						SpiderProjectModel::create($value, true);
					}
					return;
				}
			}
		}
		self::alert('导入方案出现错误');
	}
	/**
	 * [导出采集方案]
	 * @return [type] [description]
	 */
	public function do_export()
	{

		$data = SpiderProjectModel::withoutField('id')->get(['rid' => $this->rid]);
		if (empty($data)) {
			self::alert('规则暂无方案');
		}
		$data = base64_encode(json_encode($data));
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=spider.rule." . $this->rid . '.project.txt');
		exit($data);
	}
	public function do_update_lastupdate()
	{
		$id = Request::get('id');
		SpiderProjectModel::update(
			array('lastupdate' => time()),
			$id
		);
	}
}
