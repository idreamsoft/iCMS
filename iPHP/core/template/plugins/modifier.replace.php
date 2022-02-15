<?php

/**
 * template_lite replace modifier plugin
 *
 * Type:     modifier
 * Name:     replace
 * Purpose:  Wrapper for the PHP 'str_replace' function
 * Credit:   Taken from the original Smarty
 *           http://smarty.php.net
 * ADDED: { $text|replace:",,,,":",,,," }
 */
function tpl_modifier_replace($string, $search, $replace)
{

	if (!is_array($search) && strpos($search, ',')) {
		$s = explode(',', $search);
		$r = explode(',', $replace);
		return str_replace($s, $r, $string);
	} else {
		$search  = str_replace('@me', $string, $search);
		$replace = str_replace('@me', $string, $replace);
		return str_replace($search, $replace, $string);
	}
}
