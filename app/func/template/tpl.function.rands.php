<?php

function tpl_function_rands($params, &$tpl)
{
    $values = explode(',', $params['values']);
    if (count($values) > 1) {
        $value = $values[array_rand($values)];
    } else {
        $value = $values[0];
    }
    if ($params['as']) {
        View::assign($params['as'], $value);
    } else {
        return $value;
    }
}
