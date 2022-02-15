<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class HtmlAdmincp extends AdmincpBase
{
	public static $EXTS = array(
		"html", "htm", "shtml", "txt"
	);
	public function __construct()
	{
		parent::__construct();
		View::$gateway = "html";
		$this->page = $GLOBALS['page'];
		$this->speed = Config::get('route.speed');
		$this->alltime = Request::get('alltime', 0);
	}
	/**
	 * [全站静态]
	 * @return [type] [description]
	 */
	public function do_all()
	{
		include self::view("html.all");
	}
	/**
	 * [首页静态]
	 * @return [type] [description]
	 */
	public function do_index()
	{
		include self::view("html.index");
	}
	/**
	 * [生成首页静态]
	 * @return [type] [description]
	 */
	public function do_create_index()
	{
		$indexTPL  = Request::param('indexTPL');
		$indexName = Request::param('indexName');
		$ext = File::getExt($indexName) ?: Config::get('route.ext');
		FilesClient::checkExt($indexName . $ext, self::$EXTS) or self::alert('文件类型不合法');
		$this->createIndex($indexTPL, $indexName);
	}

	public function createIndex($indexTPL, $indexName, $p = 1, $loop = 1)
	{

		$_GET['loop']	&& $loop = 0;
		$GLOBALS['page']	= $p + $this->page;
		$query['indexTPL']	= $indexTPL;
		$query['indexName']	= $indexName;

		$view	= iCMS::run('index', iPHP_APP, array(array($indexTPL, $indexName)));
		$fpath	= Route::pageNum($view[1]['pagepath']);
		FilesClient::checkExt($fpath) or self::alert("文件类型不合法,禁止生成<hr />请更改系统设置->网站URL->文件后缀");
		File::mkdir($view[1]['dir']);
		File::put($fpath, $view[0]);
		$total	= Paging::$INSTANCE->total;
		$msg    = sprintf(
			"共<span class='label label-info'>%s</span>页,已生成<span class='label label-info'>%d</span>页,",
			$total ?: "1",
			$GLOBALS['page']
		);
		if ($loop < $this->speed && $GLOBALS['page'] < $total) {
			$loop++;
			$p++;
			$this->createIndex($indexTPL, $indexName, $p, $loop);
		}
		$looptimes = ($total - $GLOBALS['page']) / $this->speed;
		$use_time  = Helper::timerStop();
		$msg .= "用时<span class='label label-info'>{$use_time}</span>秒";
		$query["alltime"] = $this->alltime + $use_time;
		$loopurl = $this->loopurl($total, $query);
		if ($loopurl) {
			$moreBtn = array(
				array("id" => "btn_stop", "text" => "停止", "url" => APP_URL . "&do=index"),
				array("id" => "btn_next", "text" => "继续", "src" => $loopurl, "next" => true)
			);
			$dtime    = 1;
			$all_time = $looptimes * $use_time + $looptimes + 1;
			$msg .= "<hr />预计全部生成还需要<span class='label label-info'>{$all_time}</span>秒";
		} else {
			$moreBtn = array(
				array("id" => "btn_next", "text" => "完成", "url" => APP_URL . "&do=index")
			);
			$dtime = 5;
			$msg .= "<hr />已全部生成完成<hr />总共用时<span class='label label-info'>" . $query["alltime"] . "</span>秒";
		}
		$updateMsg = $this->page ? 'FRAME' : false;
		Script::dialog(
			$msg,
			$loopurl ? "src:" . $loopurl : '',
			$dtime,
			$moreBtn,
			$updateMsg
		);
	}
	/**
	 * [栏目静态]
	 * @return [type] [description]
	 */
	public function do_node()
	{
		Node::$APPID = iCMS_APP_ARTICLE;
		include self::view("html.node");
	}
	/**
	 * [生成栏目静态]
	 * @return [type] [description]
	 */
	public function do_createNode($cid = 0, $p = 1, $loop = 1)
	{
		$param = Request::param();
		$node	    = $param['cid'];
		$rootid		= $param['rootid'];
		$k			= (int)$param['k'];
		if ($k > 0 || empty($node)) {
			$node = Cache::get('html/node');
		}
		if (empty($node)) {
			self::alert('请选择需要生成静态的栏目');
		}
		$node[0] == 'all' && $node = Node::child();

		$k === 0 && Cache::set('html/node', $node, 0);

		$_GET['loop'] && $loop = 0;
		$GLOBALS['page'] = $p + $this->page;

		$len = count($node) - 1;
		$cid = $node[$k];

		try {
			$app  = iCMS::run('node', null, 'object');
			$view = $app->node($cid);
			$view or self::alert("栏目[cid:$cid] URL规则设置问题 此栏目不能生成静态");
			$fpath = Route::pageNum($view[1]['iurl']['pagepath']);
			FilesClient::checkExt($fpath) or self::alert("文件类型不合法,禁止生成<hr />请更改栏目->URL规则设置->栏目规则");
			File::mkdir($view[1]['iurl']['dir']);
			File::put($fpath, $view[0]);
			$total	= Paging::$INSTANCE->total;
			$name   = $view[1]['name'];
			$msg    = sprintf(
				"<span class='label label-success'>%s</span>栏目,
		共<span class='label label-info'>%d</span>页 
		已生成<span class='label label-info'>%d</span>页,",
				$name,
				$total ?: "1",
				$GLOBALS['page']
			);
			if ($loop < $this->speed && $GLOBALS['page'] < $total) {
				$loop++;
				$p++;
				$this->do_createNode($cid, $p, $loop);
			}
			$looptimes = ($total - $GLOBALS['page']) / $this->speed;
			$use_time  = Helper::timerStop();
			$msg .= "用时<span class='label label-info'>{$use_time}</span>秒";
			$query["alltime"] = $this->alltime + $use_time;
			$loopurl = $this->loopurl($total, $query);
		} catch (\FalseEx $ex) {
			$error = $ex->getMessage();
		}


		if ($loopurl) {
			$moreBtn = array(
				array("id" => "btn_stop", "text" => "停止", "url" => APP_URL . "&do=node"),
				array("id" => "btn_next", "text" => "继续", "src" => $loopurl, "next" => true)
			);
			$dtime    = 1;
			$all_time = $looptimes * $use_time + $looptimes + 1;
			$msg .= "<hr />
			<span class='label label-success'>{$name}</span>栏目,
			预计全部生成还需要<span class='label label-info'>{$all_time}</span>秒";
		} else {
			$moreBtn = array(
				array("id" => "btn_next", "text" => "完成", "url" => APP_URL . "&do=node")
			);
			$dtime = 3;
			$msg .= $error?:"<hr />
			<span class='label label-success'>{$name}</span>栏目,
			已全部生成完成.
			总共用时<span class='label label-info'>" . $query["alltime"] . "</span>秒";
			if ($k < $len) {
				$query["k"]       = $k + 1;
				$query["alltime"] = 0;
				$GLOBALS['page']  = 0;

				$loopurl = $this->loopurl(1, $query);
				$msg .= "<hr />准备开始生成下一个栏目";
				$moreBtn = array(
					array("id" => "btn_stop", "text" => "停止", "url" => APP_URL . "&do=node"),
					array("id" => "btn_next", "text" => "继续", "src" => $loopurl, "next" => true)
				);
				$dtime = 1;
			} elseif ($k == $len) {
				$msg .= "<hr />所有栏目生成完成";
			}
			$k > 0 && $updateMsg	= 'FRAME';
		}
		if ($k == 0) {
			$updateMsg = $this->page ? 'FRAME' : false;
		}
		Script::dialog($msg, $loopurl ? "src:" . $loopurl : "", $dtime, $moreBtn, $updateMsg);
	}
	/**
	 * [文章静态]
	 * @return [type] [description]
	 */
	public function do_article()
	{
		Node::$APPID = iCMS_APP_ARTICLE;
		$orderby = self::setOrderBy(array(
			'id'         => "ID",
			'hits'       => "点击",
			'hits_week'  => "周点击",
			'hits_month' => "月点击",
			'good'       => "顶",
			'postime'    => "时间",
			'pubdate'    => "发布时间",
			'comment'   => "评论数",
		));
		include self::view("html.article");
	}
	/**
	 * [生成文章静态]
	 * @return [type] [description]
	 */
	public function do_create_article($aid = null)
	{
		$param = Request::param();
		$node = $param['cid'];
		$startime = $param['startime'];
		$endtime  = $param['endtime'];
		$startid  = $param['startid'];
		$endid    = $param['endid'];
		$pageSize  = (int)$param['pageSize'];
		$offset   = (int)$param['offset'];
		$where['status'] = "1";
		$aid === null && $aid = $param['aid'];
		if ($aid) {
			$title	= $this->Article($aid);
			return $title . '生成完成';
			// self::success($title . '生成完成');
		}
		if ($node[0] == 'all') {
			Node::$APPID = iCMS_APP_ARTICLE;
			$node = Node::child();
		}

		if ($node) {
			$cids	= implode(',', (array)$node);
			$where['cid'] = $cids;
		}
		$startime && $where['pubdate'] = [">=", str2time($startime . ' 00:00:00')];
		$endtime && $where['pubdate'] = ["<=", str2time($endtime . ' 23:59:59')];
		$startid && $where['id'] = [">=", $startid];
		$endid   && $where['id'] = ["<=", $startid];
		$pageSize or $pageSize = $this->speed;

		// $_GET['orderby'] = $param['orderby'];
		$orderby = self::setOrderBy(array(
			'id'         => "ID",
			'hits'       => "点击",
			'hits_week'  => "周点击",
			'hits_month' => "月点击",
			'good'       => "顶",
			'postime'    => "时间",
			'pubdate'    => "发布时间",
			'comment'   => "评论数",
		));
		$model = ArticleModel::field('id')->where($where);
		$hash  = md5($model->getSql());
		$total = Paging::totalCache(
			[$model, 'count'],
			$hash,
			"G",
			Config::get('cache.page_total')
		);
		$looptimes = ceil($total / $pageSize);
		$offset    = $this->page * $pageSize;
		$rs        = $model->orderBy($orderby)->select();
		$_count    = count($rs);
		$msg       = sprintf(
			"共<span class='label label-info'>%d</span>篇文章,
		将分成<span class='label label-info'>%d</span>次完成<hr />
		开始执行第<span class='label label-info'>%d</span>次生成,
		共<span class='label label-info'>%d</span>篇<hr />",
			$total,
			$looptimes,
			($this->page + 1),
			$_count
		);
		for ($i = 0; $i < $_count; $i++) {
			$this->Article($rs[$i]['id']);
			$msg .= sprintf(
				'<span class="label label-success">%d <i class="fa fa-fw fa-check"></i></span> ',
				$rs[$i]['id']
			);
		}
		$GLOBALS['page']++;
		$use_time	= Helper::timerStop();
		$msg .= sprintf(
			"<hr />用时<span class='label label-info'>%d</span>秒",
			$use_time
		);
		$query["totalNum"]	= $total;
		$query["alltime"]	= $this->alltime + $use_time;
		$loopurl	= $this->loopurl($looptimes, $query);
		if ($loopurl) {
			$moreBtn	= array(
				array(
					"id" => "btn_stop",
					"text" => "停止",
					"url" => APP_URL . "&do=article"
				),
				array(
					"id" => "btn_next",
					"text" => "继续",
					"src" => $loopurl,
					"next" => true
				)
			);
			$dtime		= 1;
			$all_time	= $looptimes * $use_time + $looptimes + 1;
			$msg .= sprintf(
				"<hr />预计全部生成还需要<span class='label label-info'>%d</span>秒",
				$all_time
			);
		} else {
			$moreBtn	= array(
				array(
					"id" => "btn_next",
					"text" => "完成",
					"url" => APP_URL . "&do=article"
				)
			);
			$dtime		= 5;
			$msg .= sprintf(
				"<hr />已全部生成完成
				<hr />总共用时<span class='label label-info'>%d</span>秒",
				$query["alltime"]
			);
		}
		$updateMsg	= $this->page ? 'FRAME' : false;

		Script::dialog(
			$msg,
			$loopurl ? "src:" . $loopurl : '',
			$dtime,
			$moreBtn,
			$updateMsg
		);
	}
	public function Article($id)
	{
		$app   = iCMS::run('article', null, 'object');
		$view  = $app->display($id);
		$view or self::alert("文章所属栏目URL规则设置问题 此栏目下的文章不能生成静态,请修改栏目的访问模式和URL规则");
		$total = $view[1]['page']['total'];
		$title = $view[1]['title'];
		FilesClient::checkExt($view[1]['iurl']['path']) or self::alert("文件类型不合法,禁止生成<hr />请更改栏目->URL规则设置->内容规则");
		File::mkdir($view[1]['iurl']['dir']);
		File::put($view[1]['iurl']['path'], $view[0]);
		if ($total >= 2) {
			for ($ap = 2; $ap <= $total; $ap++) {
				$_GET['page'] = $ap;
				$view   = $app->display($id);
				$fpath = Route::pageNum($view[1]['iurl']['pagepath'], $ap);
				File::put($fpath, $view[0]);
			}
		}
		unset($app, $view);
		return $title;
	}
	public function loopurl($total, $_query)
	{
		if ($total > 0 && $GLOBALS['page'] < $total) {
			//$p++;
			$url  = $_SERVER["REQUEST_URI"];
			$urlA = parse_url($url);

			parse_str($urlA["query"], $query);
			$query['page']		= $GLOBALS['page'];
			$query 				= array_merge($query, (array)$_query);
			$urlA["query"]		= http_build_query($query);
			$url	= $urlA["path"] . '?' . $urlA["query"];
			return $url;
			//Helper::redirect($url);
		}
	}
	public static function isCreateHtml($value, $node)
	{
		if (($node['mode']
				&& strstr($node['rule']['article'], '{PHP}') === false
				&& $value['status'] == "1"
				&& empty($value['outurl'])
				&& Member::$DATA['role_id'] == 1) ||
			preg_match('/\[(.+)\]/', $value['clink'])
		) {
			return true;
		}
		return false;
	}
}
