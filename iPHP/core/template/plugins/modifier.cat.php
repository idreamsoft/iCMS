<?php

/**
 * Template Lite plugin converted from Smarty
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty cat modifier plugin
 *
 * Type:     modifier<br>
 * Name:     cat<br>
 * Date:     Feb 24, 2003
 * Purpose:  catenate a value to a variable
 * Input:    string to catenate
 * Example:  {$var|cat:"foo"}
 * @link http://smarty.php.net/manual/en/language.modifier.cat.php cat
 *          (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @version 1.0
 * @param string
 * @param string
 * @return string
 */
function tpl_modifier_cat($string)
{
    $arg_list = func_get_args();
    //{'字符'|cat:'字符1':'字符2':'字符3'}
    $pieces = array_slice($arg_list, 1);
    return $string . implode('', $pieces);
}
