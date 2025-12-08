<?php
/*
 * iCMS for Template Lite plugin
 */
function tpl_block_cache($vars, &$content, $tpl)
{
    $vars['id'] or print('cache 标签出错! 缺少"id"属性或"id"值为空.');
    $cache_time = isset($vars['time']) ? (int) $vars['time'] : -1;
    $cache_name = View::$template_device . '/block_cache/' . $vars['id'];
    $_content = Cache::get($cache_name);

    if ($_content === null) {
        Cache::set($cache_name, $content, $cache_time);
    } else {
        $content = $_content;
    }
    if ($vars['assign']) {
        $tpl->assign($vars['assign'], $content);
        return false;
    }
    return true;
}
