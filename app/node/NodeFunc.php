<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class NodeFunc extends AppsFuncCommon implements AppsFuncInterface
{
	public static function value($vars)
	{
	}
	public static function node($vars)
	{
	}
	public static function url($vars)
	{
		if(isset($vars['dir'])){
			$id = NodeCache::get('dir2nid', $vars['dir']);
		}else{
			$id = (int) $vars['id'];
		}
		$node = NodeApp::node($id, false);
		echo $node['url'];
	}
	public static function data($vars)
	{
		if(isset($vars['dir'])){
			$id = NodeCache::get('dir2nid', $vars['dir']);
		}else{
			$id = (int) $vars['id'];
		}
		if (empty($id)) {
			return;
		}
		return NodeApp::node($id, false);
	}
	public static function lists($vars)
	{
		$whereNot  = array();
		$resource  = array();
		$model     = NodeModel::field('id');
		$status    = isset($vars['status']) ? (int) $vars['status'] : 1;
		$where     = [['status', $status]];

		if (is_array($vars['apps'])) {
			$vars['apps']['id'] && $where[] = ['appid', $vars['apps']['id']];
		}
		if (isset($vars['appid'])) {
			$w = ['appid', $vars['appid']];
			if (!is_numeric($vars['appid']) && is_string($vars['appid'])) {
				try {
					$apps = Apps::getData($vars['appid']);
					$apps['id'] && $w = ['appid', $apps['id']];
				} catch (\FalseEx $fex) {
				}
			}
			$where[] = $w;
		}
		//兼容v7
		isset($vars['sub'])  && $vars['stype'] = 'sub';
		isset($vars['cid'])  && $vars['id'] = $vars['cid'];
		isset($vars['cid!']) && $vars['id!'] = $vars['cid!'];

		isset($vars['mode'])    && $where[]    = ['mode', $vars['mode']];
		isset($vars['dir'])     && $where[]    = ['dir', $vars['dir']];
		if(isset($vars['rootdir'])){
			$rootid = NodeCache::get('dir2nid', $vars['rootdir']);
			$where[] = ['rootid', $rootid];
		}
		isset($vars['rootid'])  && $where[]    = ['rootid', $vars['rootid']];
		isset($vars['rootid!']) && $whereNot[] = ['rootid', '<>', $vars['rootid!']];

		$vars['stype'] == 'top' && $where[] = ['rootid', 0];
		if ($vars['stype'] == 'sub') {
			if (isset($vars['sub'])) {
				$vars['stype'] = 'suball';
			} elseif (isset($vars['id'])) {
				$where[] = ['rootid', $vars['id']];
				unset($vars['id']);
			}
		}
		if ($vars['stype'] == 'suball' && $vars['id']) {
			$where[] = ['id', 'in',NodeCache::getIds($vars['id'])?:0];
			unset($vars['id']);
		}
		if ($vars['stype'] == 'self') {
			$where[] = ['rootid', NodeCache::get('parent', $vars['id'])];
			unset($vars['id']);
		}
		self::inited($vars, $model, $where, $whereNot);

		self::props();
		self::keywords();
		self::orderby(['hot' => 'count', 'new' => 'id'], 'sortnum', 'ASC');
		self::where();
		return self::getResource(__METHOD__);
	}
	public static function resource($idsArray = null)
	{
		$vars = self::$vars;
		$resource = NodeModel::field('id')->where($idsArray)->orderBy('id', $idsArray)->select();
		$resource = self::many($vars, $resource);
		return $resource;
	}
	public static function select($vars)
	{
		$selid  = $vars['selected'];
		$id   = (int) $vars['id'];
		$level = $vars['level'];
		empty($level) && $level = 1;
		$rootid = NodeCache::get('rootid');
		$html = null;
		foreach ((array) $rootid[$id] as $root => $_nid) {
			$C = NodeCache::getId($_nid);
			// var_dump($C);
			if (isset($vars['appid']) && $C['appid'] != $vars['appid']) {
				continue;
			}
			if ($C['status'] == '2') {
				continue;
			}
			$roleArray = $C['config']['role'];
			if (UserCenter::checkRole($roleArray['publish']) || empty($roleArray['publish'])) {
				if ($C['status'] && empty($C['outurl'])) {
					$tag = ($level == '1' ? "" : "├ ");
					$selected = ($selid == $C['id']) ? "selected" : "";
					$text = str_repeat("│　", $level - 1) . $tag . $C['name'] . "[id:{$C['id']}]" . ($C['outurl'] ? "[∞]" : "");

					UserCenter::checkRole($roleArray['examine']) && $text .= '[审核]';

					$option = sprintf(
						'<option value="%s" %s>%s</option>',
						$C['id'],
						$selected,
						$text
					);
					if (isset($vars['as'])) {
						$html .= $option;
					} else {
						print $option;
					}
				}
			}

			if ($rootid[$C['id']]) {
				$option = self::select(array(
					'selected'  => $selid,
					'id'   => $C['id'],
					'level' => $level + 1,
				));
				if (isset($vars['as'])) {
					$html .= $option;
				} else {
					print $option;
				}
			}
		}
		if (!isset($vars['as'])) {
			return $html;
		}
	}
	public static function many($vars, $resource = [])
	{
		if ($resource) {
			$idArray = array_column($resource, 'id');
			if ($vars['meta'] && $idArray) {
				$metaData = [];
				AppsMeta::data(Node::APP, $idArray,$metaData);
			}
			foreach ($resource as $key => $value) {
				$node = NodeCache::getId($value['id']);
				if ($vars['meta'] && $metaData) {
					$node += (array) $metaData[$value['id']];
				}
				$node && $resource[$key] = $node;
			}
		}
		return $resource;
	}
}
