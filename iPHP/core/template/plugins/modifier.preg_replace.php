<?php

/**
 * Template Lite plugin converted from Smarty
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty preg_replace modifier plugin
 *
 * Type:     modifier<br>
 * Name:     preg_replace<br>
 * Purpose:  regular expression search/replace
 * @link http://smarty.php.net/manual/en/language.modifier.regex.replace.php
 *          preg_replace (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param string
 * @param string|array
 * @param string|array
 * @return string
 */
function tpl_modifier_preg_replace($string, $search, $replace)
{
    if (preg_match('!([a-zA-Z\s]+)$!s', $search, $match) && (strpos($match[1], 'e') !== false)) {
        /* remove eval-modifier from $search */
        $search = substr($search, 0, -strlen($match[1])) . preg_replace('![e\s]+!', '', $match[1]);
    }
    return preg_replace($search, $replace, $string);
}
