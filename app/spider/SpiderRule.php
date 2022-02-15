<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class SpiderRule
{
	public static function get($id)
	{
		$key = 'spider:rule:' . $id;
		$data = $GLOBALS[$key];
		if (!isset($GLOBALS[$key])) {
			$data = SpiderRuleModel::get($id);
			$data['rule']['user_agent'] or $data['rule']['user_agent'] = "Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)";
			$GLOBALS[$key] = $data;
		}
        array_walk_recursive($data['rule'],function(&$item, $key){
            $item = htmlspecialchars_decode($item);
        });
		Spider::$useragent = $data['rule']['user_agent'];
		Spider::$encoding  = $data['rule']['curl']['encoding'];
		Spider::$referer   = $data['rule']['curl']['referer'];
		Spider::$cookie    = $data['rule']['curl']['cookie'];
		Spider::$charset   = $data['rule']['charset'];
		return $data;
	}
	public static function option($id = 0, &$output = null)
	{
		$rs = SpiderRuleModel::select();
		$opt = '';
		$output = array();
		if (is_array($rs)) foreach ((array) $rs as $rule) {
			$output[$rule['id']] = $rule['name'];
			$selected = ($id == $rule['id'] ? "selected" : '');
			$opt .= sprintf(
				'<option value="%s" %s>%s[id:="%s"]</option>',
				$rule['id'],
				$selected,
				$rule['name'],
				$rule['id']
			);
		}
		return $opt;
	}
}
