<?php

/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
//iPages::$setting['url']="/index_";
//iPages::$setting['enable']=true;
class Pages
{
	public $page_sign  = '{P}';
	public $page_name  = "page"; //page标签，用来控制url页。比如说xxx.php?page=2中的page
	public $page_nav   = "NAV";
	public $page_style = 0;
	public $page_item  = '<li class="page-item %s">%s</li>';
	public $page_link  = '<a class="page-link" href="%s" data-pageno="%d" %s>%s</a>';
	public $is_ajax    = false; //是否支持AJAX分页模式
	public $ajax_fun   = null;   //AJAX动作名
	public $titles     = array();
	public $target     = '_self';

	public $barnum    = 8; //控制记录条的个数。
	public $total     = 0; //总页数
	public $nowindex  = 1; //当前页
	public $unit      = null; 
	public $url       = null; //url地址头
	public $offset    = 0;
	public $lang      = array(
		'index' => 'INDEX',
		'prev' => 'PREV',
		'next' => 'NEXT',
		'last' => 'LAST',
		'other' => 'Total',
		'unit' => 'Page',
		'list' => 'Articles',
		'sql' => 'Records',
		'tag' => 'Tags',
		'comment' => 'Comments',
		'message' => 'Messages'
	);
	public $class     = null;

	public static $config = array();
	public static $setting = array();
	public static $instance;

	/**
	 * constructor构造函数
	 *
	 * @param array $array['count'],$array['size'],$array['pn'],$array['unit'],$array['nowindex'],$array['url'],$array['ajax'],$array['pnName']...
	 */
	public function __construct($conf)
	{
		array_key_exists('count', $conf) or trigger_error('Pages CLASS need a param of count');

		self::$config = $conf;
		$this->count  = (int)$conf['count']; //总条数
		$this->lastId = $conf['lastId'] ? (int)$conf['lastId'] : null;
		$this->size   = $conf['size'] ? (int)$conf['size'] : 10;
		$this->total  = ceil($this->count / $this->size);
		// $this->class     = $this->lang;

		if ($this->total < 1) return false;

		$url = self::$setting['url'] ?: $_SERVER['REQUEST_URI'];
		isset($conf['url']) && $url = $conf['url'];
		self::$config['url'] = $url;

		//配置
		$conf['pnstyle']   && $this->page_style = $conf['pnstyle'];
		$conf['pagenav']   && $this->page_nav   = strtoupper($conf['pagenav']);
		$conf['name']      && $this->page_name  = $conf['name'];
		$conf['item']      && $this->page_item  = $conf['item'];
		$conf['link']      && $this->page_link  = $conf['link'];
		$conf['barnum']    && $this->barnum     = $conf['barnum'];
		$conf['target']    && $this->target     = $conf['target'];
		$conf['titles']    && $this->titles     = $conf['titles'];
		$conf['lang']      && $this->lang       = $conf['lang'];
		$conf['class']     && $this->class      = $conf['class'];


		$this->unit = $conf['unit'] ?: $this->lang['sql'];
		//设置当前页
		$this->set_nowindex(isset($conf['nowindex']) ? (int)$conf['nowindex'] : 0);
		//设置链接地址
		$this->set_url($url, $conf['totalType']);
		$this->offset = (int)($this->nowindex - 1 < 0 ? 0 : $this->nowindex - 1) * $this->size;
		//打开AJAX模式
		$conf['ajax'] && $this->ajax($conf['ajax']);
	}

	/**
	 * 打开倒AJAX模式
	 *
	 * @param string $action 默认ajax触发的动作。
	 */
	public function ajax($action)
	{
		$this->is_ajax  = true;
		$this->ajax_fun = $action;
	}

	public function vars()
	{
		return array(
			$this->page_nav  => ($this->total > 1) ? $this->show($this->page_style) : '',
			'COUNT'   => $this->count,
			'TOTAL'   => $this->total,
			'CURRENT' => $this->nowindex,
			'PN'      => $this->nowindex,
			'PREV'    => $this->prev_page([]),
			'NEXT'    => $this->next_page([]),
			'END'     => ($this->nowindex >= $this->total),
			'LIST'    => $this->list_page(),
			'FIRST'   => $this->first_page([]),
			'LAST'    => $this->last_page([]),
		);
	}

	/**
	 * 获取显示"下一页"的代码
	 *
	 * @param string $style
	 * @return string
	 */
	public function next_page($flag = false)
	{
		$p = $this->nowindex + 1;
		if ($p > $this->total) {
			$p = $this->total;
		}
		// $flag = $this->nowindex < $this->total;
		return $this->get_link($p, 'next', $flag);
	}

	/**
	 * 获取显示“上一页”的代码
	 *
	 * @param string $style
	 * @return string
	 */
	public function prev_page($flag = false)
	{
		$p = $this->nowindex - 1;
		$p < 2 && $p = 1;
		// $flag = ($this->nowindex > 1);
		return $this->get_link($p, 'prev', $flag);
	}

	/**
	 * 获取显示“首页”的代码
	 *
	 * @return string
	 */
	public function first_page($flag = false)
	{
		return $this->get_link(1, 'index', $flag);
	}

	/**
	 * 获取显示“尾页”的代码
	 *
	 * @return string
	 */
	public function last_page($flag = false)
	{
		return $this->get_link($this->total, 'last', $flag);
	}
	public function last_text()
	{
		$text = $this->lang['other'] . $this->total . $this->lang['unit'];
		return $this->get_link($this->total, $text, null);
	}
	public function current_page()
	{
		$pnt = $this->get_title($this->nowindex);
		$title = $this->titles ? $pnt : ($this->lang['di'] . $pnt . $this->lang['unit']);
		return $this->get_link($this->nowindex, $title, 'active');
	}
	//文字说明
	public function mark_page()
	{
		$text = sprintf(
			'<span class="page_mark">%s，%s</span>',
			$this->total . $this->unit,
			$this->lang['other'] . $this->total . $this->lang['unit']
		);
		return $this->get_item($text, 'page_text');
	}
	public function nowbar()
	{
		$plus   = ceil($this->barnum / 2);
		$before = $this->nowindex - $plus;
		$after  = $this->nowindex + $plus - 1;
		if ($before < 1) {
			$after  = $this->barnum;
			$before = 1;
		}
		if ($after > $this->total) {
			$after = $this->total;
			$before = $this->total - $this->barnum;
			$before < 1 && $before = 1;
		}

		for ($i = $before; $i <= $after; $i++) {
			$active = ($i == $this->nowindex) ? 'active' : '';
			$pieces[] = $this->get_link($i, $i, $active);
		}
		return implode('', $pieces);
	}
	public function list_page()
	{
		$pieces = array();
		for ($i = 1; $i <= $this->total; $i++) {
			$pieces[] = $this->get_array($i);
		}
		return $pieces;
	}
	/**
	 * 获取显示跳转按钮的代码
	 *
	 * @return string
	 */
	public function select()
	{
		$format = '<option value="%s" %s>%s</option>';
		$option = [];
		for ($i = 1; $i <= $this->total; $i++) {
			$url = $this->get_url($i);
			$pnt = $this->get_title($i);
			$selected = $i == $this->nowindex ? 'selected' : '';
			$option[] = sprintf($format, $url, $selected, $pnt);
		}
		$format = '<select class="js-chosen-disable" onchange="window.location.href=this.value">%s</select>';
		$return = sprintf($format, implode('', $option));
		return $return;
	}
	public function select_wrap($style = 'page_select')
	{
		$text = $this->lang['di'] . $this->select() . $this->lang['unit'];
		return $this->get_item($text, $style);
	}
	/**
	 * 获取mysql 语句中limit需要的值
	 *
	 * @return string
	 */
	public function offset()
	{
		return $this->offset;
	}

	/**
	 * 控制分页显示风格（你可以增加相应的风格）
	 *
	 * @param int $mode
	 * @return string
	 */
	public function show($mode = 0)
	{
		if ($this->total < 2) {
			return '';
		}
		switch ($mode) {
			case '1':
				return $this->prev_page() . $this->nowbar() . $this->next_page() . $this->select_wrap();
				break;
			case '2':
				return $this->first_page() . $this->prev_page() . $this->nowbar() . $this->next_page() . $this->last_page() . $this->select_wrap();
				break;
			case '3':
				return $this->first_page() . $this->prev_page() . $this->nowbar() . $this->next_page() . $this->last_page();
				break;
			case '4':
				return $this->prev_page() . $this->nowbar() . $this->next_page();
				break;
			case '5':
				return $this->nowbar();
				break;
			case '6':
				return $this->prev_page() . $this->next_page();
				break;
			case '7':
				return $this->first_page() . $this->prev_page() . $this->current_page() . $this->next_page() . $this->last_page() . $this->mark_page();
				break;
			case '8':
				return $this->first_page() . $this->prev_page() . $this->current_page() . $this->next_page() . $this->last_page();
				break;
			case '9':
				return $this->first_page() . $this->prev_page() . $this->next_page() . $this->last_page();
				break;
			case '10':
				return $this->first_page() . $this->prev_page() . $this->current_page() . $this->next_page() . $this->last_text();
				break;
			default:
				return $this->first_page() . $this->prev_page() . $this->nowbar() . $this->next_page() . $this->last_text();
				break;
		}
	}

	/**
	 * 设置url头地址
	 * @param: String $url
	 * @return boolean
	 */
	public function set_url($url = "", $totalType = null)
	{
		if (self::$setting['enable']) {
			$this->url	= $url;
		} else {
			$query = array();
			$totalType === "G" && $query['pageTotal'] = $this->count;
			if ($this->lastId) {
				$query['lastId'] = $this->lastId;
				$query['lastPn'] = $this->nowindex;
			}
			$query[$this->page_name] = "---PN---";
			$this->url = Route::make($query, $url);
			$this->url = str_replace('---PN---', $this->page_sign, $this->url);
		}
	}

	/**
	 * 设置当前页面
	 *
	 */
	public function set_nowindex($pn)
	{
		if (empty($pn) && isset($_GET[$this->page_name])) { //系统获取
			$pn = $_GET[$this->page_name];
		}
		$this->nowindex = intval($pn);
		if ($this->nowindex > $this->total) {
			$this->nowindex = $this->total;
		}
		$this->nowindex < 1 && $this->nowindex = 1;
	}
	public function get_title($pn = 0, $key = null)
	{
		$title = $pn;
		if ($key && $_title = $this->lang[$key]) {
			$title = $_title;
		}
		if ($this->titles && $_title = $this->titles[$pn]) {
			$title = $_title;
		}
		return $title;
	}

	/**
	 * 为指定的页面返回地址值
	 *
	 * @param int $pageno
	 * @return string $url
	 */
	public function get_url($pageno = 1)
	{
		// if ($this->is_ajax) return (int)$pageno;
		if ($pageno < 2) {
			if (self::$setting['index']) {
				return self::$setting['index'];
			}
			$url = $this->url;
			if (!self::$setting['enable']) {
				$ss = sprintf("%s=%s", $this->page_name, $this->page_sign);
				$url = str_replace(['?' . $ss, '&' . $ss], '', $this->url);
			}

			$url = preg_replace([
				'@&pageTotal=\d+@is',
				'@&lastId=\d+@is',
				'@&lastPn=\d+@is'
			], '', $url);
			return str_replace(array('_' . $this->page_sign, $this->page_sign), array('', 1), $url);
		}
		return str_replace($this->page_sign, $pageno, $this->url);
	}

	/**
	 * 获取分页显示文字，比如说默认情况下get_text('<a href="">1</a>')将返回[<a href="">1</a>]
	 *
	 * @param String <li>%s</ii>
	 * @return string $url
	 */
	public function get_item($text, $style = null)
	{
		if ($this->page_item) {
			$count = substr_count($this->page_item, '%s');
			if ($count == 1) {
				$text = sprintf($this->page_item, $text);
			} elseif ($count == 2) {
				$text = sprintf($this->page_item, $style, $text);
			}
		}
		return $text;
	}


	/**
	 * 获取链接地址
	 */
	public function get_link($p, $key, $flag = false)
	{
		$text = $key;
		if ($flag === []) {
			return $this->get_array($p, $key);
		}
		if (is_bool($flag)) {
			$text = $this->get_title($p, $key);
			is_numeric($key) && $key = 'p' . $key;
			$style = 'page_' . $key;
		}

		if ($_style = $this->class[$key]) {
			$style = $_style;
		}
		if ($flag === 'active') {
			$style = 'active';
		}
		$url = $this->get_url($p);
		//AJAX模式
		$attr = sprintf('target="%s"', $this->target);
		if ($this->is_ajax) {

			is_string($this->ajax_fun) && $attr = sprintf('onclick="%s(%d,this)"', $this->ajax_fun, $p);
		}
		//'<a class="page-link" href="%s" data-PageNo="%d" %s>%s</a>'
		$item = sprintf($this->page_link, $url, $p, $attr, $text);
		return $this->get_item($item, $style);
	}
	public function get_array($i, $key = null)
	{
		$title = $this->get_title($i, $key);
		return array(
			'pn'    => $i,
			'url'   => $this->get_url($i),
			'title' => $title,
			'link'  => $this->get_link($i, $title, null),
		);
	}
}
