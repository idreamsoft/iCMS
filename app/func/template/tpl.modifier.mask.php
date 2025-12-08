<?php
/*
 * template_lite plugin
 *
 * Type:     modifier
 * Name:     ifa
 * Version:  0.1
 * Examples:
 */
function tpl_modifier_mask($string, $start = 2, $end = 2, $mask = '**')
{
    return mask($string, $start, $end, $mask);
}
