<?php

function tpl_modifier_cnlen($str, $charset = 'UTF-8')
{
    return iString::strlen($str, $charset);
}
