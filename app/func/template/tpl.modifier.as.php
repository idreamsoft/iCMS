<?php
/*
 * Template Lite plugin
 */
function tpl_modifier_as($value,$key){
    View::assign($key,$value);
}
