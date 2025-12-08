<?php

function tpl_modifier_keep()
{
    $args = func_get_args();
    return LangHook::text($args,false);
}
