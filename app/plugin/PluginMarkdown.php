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
    public static function parser($content, $htmldecode = false)
    {
        if (self::$flag) return $content;

        is_array($content) && $content = implode('', $content);
        
        $content = htmlspecialchars_decode($content);

        Plugin::init(__CLASS__);
        Plugin::library('Parsedown');
        $Parsedown = new Parsedown();
        $Parsedown->setBreaksEnabled(true);
        $Parsedown->setSafeMode(true);
        $content = str_replace(array(
            '#--' . iPHP_APP . '.Markdown--#',
            '#--' . iPHP_APP . '.PageBreak--#',
        ), array('', '@--' . iPHP_APP . '.PageBreak--@'), $content);
        $content = $Parsedown->text($content);
        $content = str_replace('@--' . iPHP_APP . '.PageBreak--@', '#--' . iPHP_APP . '.PageBreak--#', $content);
        self::$flag = true;
        return $content;
    }
}
