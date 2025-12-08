<?php
/*
 * Template Lite plugin
 */
function tpl_modifier_if($value,$true,$false=''){
    return $value?$true:$false;
}
