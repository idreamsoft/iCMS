<?php

function tpl_modifier_cut($str, $len, $end = null)
{
    return iString::cut($str, $len, $end);
}
