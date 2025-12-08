<?php
/*
 * template_lite plugin
 *
 * Type:     function
 * Name:     random 
 * Version:  0.1
 * Examples:
 */
function tpl_function_random($params, &$tpl)
{
    $string = random($params['len']?$params['len']:6, $params['type'] == 'num');
    if($params['as']){
        View::assign($params['as'], $string);
        return;
    }
    return $string;
}