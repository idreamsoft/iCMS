<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PluginMarkdown
{
    public static $flag = false;
    public static $styles = [];
    public static $classes = [];
    public static $lazyload = true;

    public static function html2md($content, $helper=null)
    {
        Plugin::init(__CLASS__);
        Plugin::library('Html2md');
        $converter = new Html2md();
        $markdown = $converter->convert($content);
        return $markdown;
    }

    public static function parser($content, $htmldecode = false)
    {
        if (self::$flag) {
            return $content;
        }

        is_array($content) && $content = implode('', $content);

        if (empty($content)) {
            return $content;
        }

        $content = htmlspecialchars_decode($content);

        Plugin::init(__CLASS__);
        Plugin::library('Parsedown');
        $Parsedown = new Parsedown();
        $Parsedown->setBreaksEnabled(true);
        $Parsedown->setSafeMode(true);
        $Parsedown->setLazyload(self::$lazyload);
        self::$classes && $Parsedown->setClasses(self::$classes);
        self::$styles && $Parsedown->setStyles(self::$styles);
        $content = str_replace([
            '#--' . iPHP_APP . '.Markdown--#',
            '#--' . iPHP_APP . '.PageBreak--#',
        ], ['', '@--' . iPHP_APP . '.PageBreak--@'], $content);
        $content = $Parsedown->text($content);
        $content = str_replace('@--' . iPHP_APP . '.PageBreak--@', '#--' . iPHP_APP . '.PageBreak--#', $content);
        self::$flag = true;

        return $content;
    }
}
