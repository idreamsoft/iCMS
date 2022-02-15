<?php
/*
 * Template Lite plugin
 */
function tpl_modifier_assign($value,$key){
    View::assign($key,$value);
}
