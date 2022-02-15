<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PluginMarkdownHook
{
    /**
     * [插件:正文markdown解析]
     * @param string $content  [参数]
     */
    public static function run($content, &$resource = null)
    {
        $resource['markdown'] && $content = PluginMarkdown::parser($content,$resource['htmldecode']);
        return $content;
    }
}
