<?php
// namespace iPHP\core;
/*
通用漏洞防护补丁v1.1
来源：阿里云
更新时间：2013-05-25
功能说明：防护XSS,SQL,代码执行，文件包含等多种高危漏洞
更新时间：2018-07-11 @icmsdev
*/
defined('iPHP_WAF_SKIP_POST') or define('iPHP_WAF_SKIP_POST', false); // 跳过POST检测

class Waf
{
	public static $enable = true;
	private static $urlRule = array(
		'xss' => "=\+\v(?:8|9|\+|/)|%0acontent\-(?:id|location|type|transfer\-encoding)",
	);
	private static $argsRule = array(
		'xss'   => "['\";\*<>].*\bon[a-zA-Z]{3,15}[\s\r\n\v\f]*=|\b(?:expression)\(|<script+|<!\[cdata\[|\b(?:eval|alert|prompt|msgbox)\s*\(|url\((?:\#|data|javascript)",
		'sql'   => "[^\{\s]{1}(\s|\b)+(?:select\b|update\b|insert(?:(/\*.*?\*/)|(\s)|(\+))+into\b).+?(?:from\b|set\b)|[^\{\s]{1}(\s|\b)+(?:create|delete|drop|truncate|rename|desc)(?:(/\*.*?\*/)|(\s)|(\+))+(?:table\b|from\b|database\b)|into(?:(/\*.*?\*/)|\s|\+)+(?:dump|out)file\b|\bsleep\([\s]*[\d]+[\s]*\)|benchmark\(([^\,]*)\,([^\,]*)\)|(?:declare|set|select)\b.*\@|union\b.*(?:select|all)\b|(?:select|update|insert|create|delete|drop|grant|truncate|rename|exec|desc|from|table|database|set|where)\b.*(charset|ascii|bin|char|uncompress|concat|concat_ws|conv|export_set|hex|instr|left|load_file|locate|mid|sub|substring|oct|reverse|right|unhex)\(|(?:master\.\.sysdatabases|msysaccessobjects|msysqueries|sysmodules|mysql\.db|sys\.database_name|information_schema\.|sysobjects|sp_makewebtask|xp_cmdshell|sp_oamethod|sp_addextendedproc|sp_oacreate|xp_regread|sys\.dbms_export_extension)",
		'other' => "\.\.[\/].*%00([^0-9a-fA-F]|$)|%00['\"\.]"
	);
	public static function filter()
	{
		$referer = (array)$_SERVER['HTTP_REFERER'];
		$query   = (array)$_SERVER["QUERY_STRING"];
		self::check($query, self::$urlRule);

		self::check($_GET);
		iPHP_WAF_SKIP_POST or self::check($_POST);
		self::check($_COOKIE);
		self::check($_FILES);
		self::check($referer);
	}

	public static function check($arr)
	{
		if (!self::$enable) return true;

		foreach ($arr as $key => $value) {
			if (is_array($key) || is_object($key)) {
				self::check($key);
			} else {
				self::test($key);
			}

			if (is_array($value) || is_object($value)) {
				self::check($value);
			} else {
				self::test($value);
			}
		}
	}
	public static function test($str)
	{
		foreach (self::$argsRule as $key => $value) {
			if (
				preg_match("@" . $value . "@is", $str) ||
				preg_match("@" . $value . "@is", urlencode($str)) ||
				preg_match("@" . $value . "@is", urldecode($str))
			) {
				iPHP::throwError('What the fuck! (WAF)');
			}
		}
	}
}
