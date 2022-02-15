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

class SpiderUrlAdmincp extends AdmincpBase
{

	public function __construct()
	{
		SpiderAdmincp::init($this);
		$this->id = (int)Request::get('id');
	}

	public function do_batch()
	{
		$stype = AdmincpBatch::$config['stype'];
		$actions = array(
			'delurl' => array('删除', 'trash-alt', 'run' => function ($idArray, $ids, $batch) {
				SpiderUrlModel::delete($idArray);
			}),
			'default' => function ($idArray, $ids, $batch, $data = null) {
				if (strpos($batch, '#') !== false) {
					list($table, $_batch) = explode('#', $batch);
					if (in_array($table, array('url', 'post', 'project', 'rule'))) {
						if (strpos($_batch, ':') !== false) {
							$data = Request::args($_batch);
							$modelName = sprintf('Sprider%sModel', $table);
							$model = new $modelName;
							$model->update($data, $idArray);
							return true;
						}
					}
				}
				self::alert('参数错误!');
			},
		);
		return AdmincpBatch::run($actions, "标签");
	}
	/**
	 * [删除采集结果]
	 * @return [type] [description]
	 */
    public function do_delete($id = null)
	{
		$this->id or self::alert("请选择要删除的项目");
		SpiderUrlModel::delete($this->id);
	}

	public function do_delcontent()
	{
		$indexid = $_GET['indexid'];
		$indexid or self::alert("请选择要删除的项目");

		$project = SpiderProject::get($this->pid);
		$spost   = SpiderPost::get($project['poid']);
		$app     = Apps::getData($spost->app);
		$obj     = $spost->app . "Admincp";
		$acp     = new $obj;
		if (method_exists($acp, 'do_delete')) {
			$acp->do_delete($indexid, false);
			$this->do_delspider(false);
			self::success('删除完成');
		} else {
			self::success($obj . ' 中没找到 do_delete 方法');
		}
	}

	/**
	 * [采集结果管理]
	 * @return [type] [description]
	 */
	public function do_manage($doType = null)
	{
		$where = array();
		$doType == "inbox" && $where['publish'] = '0';

		$pid = Request::get('pid');
		$pid && $where['pid'] = $pid;

		$rid = Request::get('rid');
		$rid && $where['rid'] = $rid;

		$status = Request::get('status');
		isset($status) && $where['status'] = $status;

		$starttime = Request::get('starttime');
		$starttime && $where[] = array('addtime', '>=', str2time($starttime . (strpos($starttime, ' ') !== false ? '' : " 00:00:00")));

		$endtime = Request::get('endtime');
		$endtime && $where[] = array('addtime', '<=', str2time($endtime . (strpos($endtime, ' ') !== false ? '' : " 00:00:00")));

		Node::makeWhere($where, $this->cid);

		$keywords = Request::get('keywords');
		$field = Request::sget('field') ?: 'title';
		$keywords && $where[$field] = array('like', '%' . $keywords . '%');

		$orderby = self::setOrderBy();
		$result = SpiderUrlModel::where($where)
			->orderBy($orderby)
			->paging();

		include self::view("url.manage");
	}

	public function do_inbox()
	{
		$this->do_manage("inbox");
	}

	/**
	 * [移除标记]
	 * @return [type] [description]
	 */
	public function do_mark()
	{
		SpiderUrl::create([
			'cid' => $this->cid,
			'rid' => $this->rid,
			'pid' => $this->pid,
			'title' => $this->title,
			'url' => $this->url,
			'status' => '0', 'publish' => '2',
			//未采集 标记移除 
			'indexid' => '0',
			'pubdate' => '0',
		]);
	}
	/**
	 * [删除采集数据]
	 * @return [type] [description]
	 */
	public function do_dropdata()
	{
		$this->pid or self::alert("请选择要删除的项目");
		$where = ['pid' => $this->pid];
		$rs = SpiderUrlModel::field("indexid,appid,pid")->where($where)->select();
		$project = SpiderProject::get($this->pid);
		$post    = SpiderPost::get($project['poid']);
		$_count  = count($rs);
		for ($i = 0; $i < $_count; $i++) {
			$class = $post->app . 'Admincp';
			$delete = 'do_delete';
			if (@class_exists($class) && @method_exists($class, 'do_del')) {
				if ($post->app == 'content') {
					$obj = new $class($rs[$i]['appid']);
				} elseif ($post->app == 'forms') {
					$obj = new $class();
					$delete = 'do_delete';
				} else {
					$obj = new $class;
				}
				iPHP::callback(array($obj, $delete), array($rs[$i]['indexid'], false));
			} else {
				$msg = "未找到内容删除方法,请手动删除内容";
			}
		}
		$msg && self::alert($msg);
		SpiderUrlModel::where($where)->delete();
	}
	/**
	 * [删除采集结果数据]
	 * @return [type] [description]
	 */
	public function do_dropurl()
	{
		$this->pid or self::alert("请选择要删除的项目");
		$where = ['pid' => $this->pid];
		$type = $_GET['type'];
		if ($type == "0") {
			$where['publish'] = 0;
		}
		SpiderUrlModel::where($where)->delete();
	}
}
