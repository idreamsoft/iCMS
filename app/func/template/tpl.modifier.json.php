<?php
/*
 * Template Lite plugin
 */
function tpl_modifier_json($value)
{
    if (is_array($value)) {
        $json = json_encode($value);
        $json = str_replace('"', '&quot;', $json);
        return $json;
    }
    return '[]';
}
