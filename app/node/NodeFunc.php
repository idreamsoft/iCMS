<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class NodeFunc extends AppsFuncCommon implements AppsFuncBase
{
	public static function value($vars)
	{
	}
	public static function node($vars)
	{
	}
	public static function node_array($vars)
	{
		$id = (int) $vars['id'];
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
			if (!is_numeric($vars['appid'])) {
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
		isset($vars['rootid'])  && $where[]    = ['rootid', $vars['rootid']];
		isset($vars['rootid!']) && $whereNot[] = ['rootid', '<>', $vars['rootid!']];

		if ($vars['stype'] == 'sub') {
			if (isset($vars['sub'])) {
				$vars['stype'] = 'suball';
			} elseif (isset($vars['id'])) {
				$where[] = ['rootid', $vars['id']];
				unset($vars['id']);
			}
		}
		$vars['stype'] == 'top' && $where[] = ['rootid', 0];
		$vars['stype'] == 'suball' && $where[] = ['rootid', NodeCache::getIds($vars['id'])];
		$vars['stype'] == 'self' && $where[] = ['rootid', NodeCache::get('parent', $vars['id'])];

		self::init($vars, $model, $where, $whereNot);
		self::setApp(Node::APPID, Node::APP);
		self::props();
		self::keywords();
		self::orderby(['hot' => 'count', 'new' => 'id'], 'sortnum');
		self::where();
		return self::getResource(__METHOD__, [__CLASS__, 'resource']);
	}
	public static function resource($vars, $idsArray = null)
	{
		$vars['ids'] && $idsArray = $vars['ids'];
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
			if (UserCP::checkRole($roleArray['publish'])||empty($roleArray['publish'])) {
				if ($C['status'] && empty($C['outurl'])) {
					$tag = ($level == '1' ? "" : "├ ");
					$selected = ($selid == $C['id']) ? "selected" : "";
					$text = str_repeat("│　", $level - 1) . $tag . $C['name'] . "[id:{$C['id']}]" . ($C['outurl'] ? "[∞]" : "");

					UserCP::checkRole($roleArray['examine']) && $text .= '[审核]';

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
			if ($vars['meta']) {
				$idArray = array_column($resource, 'id');
				$idArray && $meta_data = (array) AppsMeta::data(Node::APP, $idArray);
			}
			foreach ($resource as $key => $value) {
				$node = NodeCache::getId($value['id']);
				if ($vars['meta'] && $meta_data) {
					$node += (array) $meta_data[$value['id']];
				}
				$node && $resource[$key] = $node;
			}
		}
		return $resource;
	}
}
