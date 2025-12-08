<?php

function tpl_modifier_lang()
{
    $args = func_get_args();
    return LangHook::text($args);
}
