<?php

function tpl_modifier_fields($array, $keys, $flag = false)
{
    return pluck($array, $keys, $flag);
}
