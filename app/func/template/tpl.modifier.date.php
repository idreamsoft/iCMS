<?php

function tpl_modifier_date($value, $format = '', $flag = false)
{
    $value && !is_numeric($value) && $value = strtotime($value);
    if ($flag) {
        return get_date($value, $format);
    }
    return $value ? get_date($value, $format) : '';
}
