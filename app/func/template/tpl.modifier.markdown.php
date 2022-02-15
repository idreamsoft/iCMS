<?php
/*
 * Template Lite plugin
 */
function tpl_modifier_markdown($content, $decode = false)
{
    return PluginMarkdown::parser($content,$decode);
}
