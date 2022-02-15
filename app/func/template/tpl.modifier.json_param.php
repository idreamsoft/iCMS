<?php
/*
 * Template Lite plugin
 */
function tpl_modifier_json_param($value){
    $json = json_encode($value);
    $json = str_replace('"',"&quot;",$json);
    return $json;
}
