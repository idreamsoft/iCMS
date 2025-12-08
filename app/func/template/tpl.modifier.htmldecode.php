<?php
function tpl_modifier_htmldecode($value){
    if (is_array($value)) {
        return array_map('tpl_modifier_htmldecode', $value);
    }
    return $value?htmlspecialchars_decode($value):'';
}
