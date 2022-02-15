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

class SpiderRuleAdmincp extends AdmincpBase
{
	public $ACCESC_TITLE = '采集规则';
	public function __construct()
	{
		SpiderAdmincp::init($this);
	}
	public function do_batch()
	{
		$stype = AdmincpBatch::$config['stype'];
		$actions = array(
			'dels' => array('永久删除', 'trash-alt', 'run' => function ($idArray, $ids, $batch) {
				SpiderRuleModel::delete($idArray);
				return true;
			}),
			'default' => function ($idArray, $ids, $batch, $data = null) {
				return false;
			}
		);
		return AdmincpBatch::run($actions, "文章");
	}
	/**
	 * [测试采集规则]
	 * @return [type] [description]
	 */
	public function do_test()
	{
		Spider::$isTest = true;
		SpiderList::crawl('TEST');
	}
	/**
	 * [采集规则管理]
	 * @return [type] [description]
	 */
	public function do_manage()
	{
		$keyword = Request::get('keyword');
		$keyword && $where['CONCAT(name,rule)'] = array('REGEXP', $keyword);

		$orderby = self::setOrderBy();
		$result = SpiderRuleModel::where($where)
			->orderBy($orderby)
			->paging();


		include self::view("rule.manage");
	}

	/**
	 * [复制采集规则]
	 * @return [type] [description]
	 */
	public function do_copy()
	{
		$data = SpiderRuleModel::get($this->rid);
		unset($data['id']);
		$rid = SpiderRuleModel::create($data, true);
	}
	/**
	 * [删除采集规则]
	 * @return [type] [description]
	 */
    public function do_delete($id = null)
	{
		$this->rid or self::alert("请选择要删除的项目");
		SpiderRuleModel::delete($this->rid);
	}
    /**
     * [编辑{title}]
     */
    public function do_edit()
    {
        return $this->do_add();
    }
	/**
	 * [添加采集规则]
	 * @return [type] [description]
	 */
	public function do_add()
	{
		$rs = array();
		$this->rid && $rs = SpiderRule::get($this->rid);
		$rs['rule'] && $rule = $rs['rule'];
		if (empty($rule['data'])) {
			$rule['data'] = array(
				array(
					'name' => 'title', 'empty' => true,
					'process' => array(
						array('helper' => 'trim'),
						array('helper' => 'cleanhtml')
					)
				),
				array(
					'name' => 'body', 'empty' => true, 'page' => true, 'multi' => true,
					'process' => array(
						array('helper' => 'format'),
						array('helper' => 'trim')
					)
				),
			);
		} else {
			// //兼容旧版
			// if(is_array($rule['data']))foreach ($rule['data'] as $key => $value) {
			// 	if(isset($value['process'])){
			// 		continue;
			// 	}
			// 	$rule['data'][$key]['process'] = self::old7014($value);
			// 	unset($rule['data'][$key]['cleanbefor'],$rule['data'][$key]['helper'],$rule['data'][$key]['cleanafter']);
			// }
			$rule['fs']['encoding'] && $rule['http']['ENCODING'] = $rule['fs']['encoding'];
			$rule['fs']['referer'] && $rule['http']['REFERER'] = $rule['fs']['referer'];
		}

		$rule['sort'] or $rule['sort'] = 1;
		$rule['mode'] or $rule['mode'] = 1;
		$rule['page_no_start'] or $rule['page_no_start'] = 1;
		$rule['page_no_end'] or $rule['page_no_end'] = 5;
		$rule['page_no_step'] or $rule['page_no_step'] = 1;

		include self::view("rule.add");
	}
	/**
	 * [保存采集规则]
	 * @return [type] [description]
	 */
	public function save()
	{
		$data = Request::post();
		empty($data['name']) && self::alert('规则名称不能为空！');
		//empty($rule['list_area_rule']) 	&& self::alert('列表区域规则不能为空！');
		if ($data['rule']['mode'] != '2') {
			empty($data['rule']['list_url_rule']) && self::alert('列表链接规则不能为空！');
		}

		if ($data['id']) {
			SpiderRuleModel::update($data, $data['id']);
		} else {
			$data['id'] = SpiderRuleModel::create($data, true);
		}
		return ['id'=>$data['id']];
	}
	/**
	 * [导出采集规则]
	 * @return [type] [description]
	 */
	public function do_export()
	{
		$row = SpiderRuleModel::get($this->rid);
		unset($row['id']);
		$data = base64_encode(json_encode($row));
		Header("Content-type: application/octet-stream");
		Header("Content-Disposition: attachment; filename=spider.rule." . $row['name'] . '.txt');
		exit($data);
	}

	/**
	 * [导入采集规则]
	 * @return [type] [description]
	 */
	public function do_import()
	{
		Files::$check_data = false;
		FilesCloud::$enable = false;
		FilesClient::$config['allow_ext'] = 'txt';
		$file = FilesClient::upload('upfile', 'spider');
		$path = FilesClient::getRoot($file['path']);
		if ($path) {
			$data = file_get_contents($path);
			@unlink($path);
			if ($data) {
				$data = base64_decode($data);
				$data = json_decode($data, true);
				if ($data['rule']) {
					SpiderRuleModel::create($data, true);
					return;
				}
			}
		}
		self::alert('导入规则出现错误');
	}
}
