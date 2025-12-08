<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class LangHook
{
    public static $reserved = [];

    public static function format_text($text)
    {
        return sprintf('<span>%s</span>', $text);
    }

    public static function text($args, $flag = true)
    {
        $value = $args[0] ?: '';
        if ($flag) {
            $text = call_user_func_array('Lang', $args);
            $format = '@R_%s@';
        } else {
            $text = $value;
            $format = '@R_K%s@'; //|keep
        }
        $key = sprintf($format, short_md5($value));
        self::$reserved[$key] = $text;
        return self::format_text($key);
    }

    public static function run_html_99997(&$html)
    {
        $html = LangI18n::parse($html, Lang::$DATA);
    }

    /**
     * 页面输出后 99999表示最后执行
     * 替换保留字符
     *
     * @param string $html 引用内容
     * @return void
     */
    public static function run_html_99999(&$html)
    {
        if (self::$reserved) {
            foreach (self::$reserved as $key => $value) {
                $key = self::format_text($key);
                $key2 = str_replace('</span>', '', $key);
                is_array($value) && $value = 'error.array';
                $html = str_replace([$key, htmlspecialchars($key), $key2], [$value, $value, $value], $html);
            }
        }
    }

    /**
     * 运行开始
     * 如果有翻译程序，则不执行
     * @return void
     */
    public static function run_begin_0()
    {
        if (isset(iCMS::$config['translate']) && iCMS::$config['translate']['enable']) {
            return;
        }

        $lang = Lang::$default;
        if (isset(iCMS::$config['language']) && iCMS::$config['language']['site']) {
            $lang = iCMS::$config['language']['site'];
        }
        // 如果设备语言存在，则使用设备语言
        if (isset(Adapter::$device['lang'])) {
            $lang = Adapter::$device['lang'];
        }
        self::setting($lang);
    }

    public static function setting($lang, $default_lang = null)
    {
        // 当使用翻译应用时，没有编定翻译语言时，使用设备语言
        if ($default_lang && isset(Adapter::$device['lang'])) {
            $_lang = Adapter::$device['lang'];
            $_lang !== $lang && $lang = $_lang;
        }   
        self::setInit($lang);
        self::setTpl();
    }

    public static function setInit($lang)
    {
        Lang::init($lang);
        $_lang = Lang::getLang();
        View::generic([
            'LANGUAGE'          => $_lang,
            'LANGUAGE_TEXT'     => Lang::$languages[$_lang][0],
        ]);
    }

    public static function setTpl($device_tpl = null)
    {
        empty($device_tpl) && $device_tpl = Adapter::$device['tpl'];
        Lang::template($device_tpl);
        View::assign('LANG', Lang::$DATA);
    }
}
