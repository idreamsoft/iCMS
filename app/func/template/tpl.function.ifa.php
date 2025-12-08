<?php
/*
 * template_lite plugin
 *
 * Type:     function
 * Name:     ifa
 * Version:  0.1
 * Examples:
 */
function tpl_function_ifa($params, &$tpl)
{
    $name = $params['name'] ?: 'ifa';
    if(isset($params['expr'])){
        View::assign($name, $params['expr'] ? $params['t'] : $params['f']);
    }elseif(isset($params['values'])){
        $values = explode(',',$params['values']);
        $values = array_combine($values, $values);
        is_null($params['from']) && $params['from'] = 'null';
        foreach($values as $k=>$v){
            // var_dump($name.'_'.$k,$v == $params['from'] ? $params['t'] : $params['f']);
            View::assign($name.'_'.$k, $v == $params['from'] ? $params['t'] : $params['f']);
        }
    }
}
