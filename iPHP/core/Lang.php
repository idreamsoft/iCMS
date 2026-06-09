<?php

/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
class Lang
{
    public static $default = 'zh';
    public static $language = '';
    public static $DEBUG = null;
    public static $CACHE = [];
    public static $APPS = [];
    public static $DATA = [];
    public static $languages = [
        'zh'    => ['简体中文', '简体中文', [
            'zh-CN'=> '中国大陆',
            'zh-TW'=> '中国台湾'
        ]],
        'zh-tw' => ['繁體中文', '繁體中文'],
        'en'    => ['English', '英语', [
            'en-US'=> '美国',
            'en-GB'=> '英国',
            'en-CA'=> '加拿大',
            'en-AU'=> '澳大利亚',
        ]],
        'es'    => ['Español', '西班牙语', [
            'es-ES'=> '西班牙',
            'es-MX'=> '墨西哥',
            'es-AR'=> '阿根廷'
        ]],
        'fr'    => ['Français', '法语', [
            'fr-FR'=> '法国',
            'fr-CA'=> '加拿大',
            'fr-CH'=> '瑞士',
            'fr-BE'=> '比利时',
            'fr-LU'=> '卢森堡',
        ]],
        'ja'    => ['日本語', '日语', [
            'ja-JP'=> '日本'
        ]],
        'ko'    => ['한국어', '韩语', [
            'ko-KR'=> '韩国',
        ]],
        'ru'    => ['Русский', '俄语', [
            'ru-RU'=> '俄罗斯',
        ]],
        'pt'    => ['Português', '葡萄牙语', [
            'pt-BR'=> '巴西',
            'pt-PT'=> '葡萄牙',
            'pt-MZ'=> '莫桑比克',
            'pt-CV'=> '佛得角',
        ]],
        'it'    => ['Italiano', '意大利语', [
            'it-IT'=> '意大利',
            'it-CH'=> '瑞士',
        ]],
        'de'    => ['Deutsch', '德语', [
            'de-AT'=> '奥地利',
            'de-CH'=> '瑞士',
            'de-DE'=> '德国',
        ]],
        'el'    => ['Ελληνικά', '希腊语', [
            'el-GR'=> '希腊',
        ]],
        'fa'    => ['فارسی', '波斯语', [
            'fa-IR'=> '波斯语',
        ]],
        'bg'    => ['Български', '保加利亚语', [
            'bg-BG'=> '保加利亚',
        ]],
        'da'    => ['Dansk', '丹麦语', [
            'da-DK'=> '丹麦',
            'da-GL'=> '格陵兰',
        ]],
        'no'    => ['Norsk', '挪威语', [
            'no-NO'=> '挪威'
        ]],
        'fi'    => ['Suomi', '芬兰语', [
            'fi-FI'=> '芬兰',
        ]],
        'cs'    => ['Čeština', '捷克语', [
            'cs-CZ'=> '捷克',
        ]],
        'hu'    => ['Magyar', '匈牙利语', [
            'hu-HU'=> '匈牙利',
        ]],
        'nl'    => ['Nederlands', '荷兰语', [
            'nl-NL'=> '荷兰'
        ]],
        'sv'    => ['Svenska', '瑞典语', [
            'sv-SE'=> '瑞典',
        ]],
        'tr'    => ['Türkçe', '土耳其语', [
            'tr-TR'=> '土耳其',
        ]],
        'ar'    => ['العربية', '阿拉伯语', [
            'ar-AE'=> '阿拉伯联合酋长国',
            'ar-BH'=> '巴林',
            'ar-DZ'=> '阿尔及利亚',
            'ar-EG'=> '埃及',
        ]],
        'vi'    => ['Tiếng Việt', '越南语', [
            'vi-VN'=> '越南',
        ]],
        'pl'    => ['Polski', '波兰语', [
            'pl-PL'=> '波兰',
        ]],
        'id'    => ['Bahasa Indonesia', '印尼语', [
            'id-ID'=> '印度尼西亚',
        ]],
        'hi'    => ['हिन्दी', '印地语', [
            'hi-IN'=> '印度',
        ]],
        'th'    => ['ไทย', '泰语', [
            'th-TH'=> '泰国',
        ]]
    ];

    const PATTERN = '/^([a-zA-Z0-9\_\-]+(:[a-z0-9\_\-]+)+)$/i';

    public static function init($lang = 'zh')
    {
        // echo iDebug::backtraceHtml();
        Lang::set($lang);
        Lang::all(true);
    }

    /**
     * 获取本地语言
     *
     * @param string|array $keys 完整的句子或者 a:b,a:b:c,a:b:c:d 标签模式
     * @param array|null $params 替换参数
     * @return string
     */
    public static function get($keys = '', $params = null)
    {
        if (empty($keys)) {
            return $keys;
        }

        if (is_array($keys)) {
            $params = $keys;
            $keys = array_shift($params);
        }
        $text = self::text($keys);
        empty($text) && $text = $keys;

        if ($params && is_string($text)) {
            is_array($params) or $params = [$params];

            // 处理参数中的语言标签
            $params = array_map(function ($value) {
                return self::text($value);
            }, $params);

            // 处理命名参数 {param}
            preg_match_all('/\{(\w+)\}/i', $text, $matches);
            if ($matches[1]) {
                foreach ($matches[1] as $idx => $key) {
                    $key = strtolower($key);
                    $value = $params[$key];
                    is_null($value) or $text = str_replace($matches[0][$idx], $value, $text);
                }
            }
            // 处理位置参数 %s
            else {
                $count = count($params);
                $length = substr_count($text, '%s');
                if ($length > 0) {
                    if ($count > $length) {
                        $params = array_slice($params, 0, $length);
                    } elseif ($count < $length) {
                        $fill = array_fill($count, ($length - $count), '?');
                        $params = array_merge($params, $fill);
                    }
                    $params = array_merge([$text], $params);
                    $text = call_user_func_array('sprintf', $params);
                } else {
                    $text .= implode(' ', $params);
                }
            }
        } else {
            if (is_string($text) && strpos($text, '%s') !== false) {
                $text = str_replace('%s', '', $text);
            }
        }
        return $text;
    }

    /**
     * 设置当前语言
     */
    public static function set($value)
    {
        $value = Security::safeStr($value, '-');
        self::$language = $value;
        Session::set('language', $value, 0);
    }

    public static function getLocale(&$locales = [])
    {
        // $locales = [];
        // foreach (self::$languages as $lang => $q) {
        //     $q[2] && $locales = array_merge($locales, $q[2]);
        // }
        // 获取浏览器语言设置
        $httpLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?: '';
        // $httpLang = 'de-DE';
        // $httpLang = 'de;q=0.8';
        // $httpLang = 'en-US;q=0.6';
        // $httpLang = 'en;q=0.4';
        // $httpLang = 'zh-CN,zh;q=0.9,en;q=0.8';
        // 解析语言参数（带权重排序）
        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $httpLang, $matches);
        $locales = [];
        foreach ($matches[1] as $index => $lang) {
            $q = $matches[4][$index] ? (float)$matches[4][$index] : 1.0;
            $locales[$lang] = $q;
        }
        // 按权重降序排序
        arsort($locales);
        $locale = key($locales);
        $lang = substr($locale, 0, 2);
        // var_dump($lang);
        return [$locale, $lang];
    }

    /**
     * 获取当前语言
     */
    public static function getLang()
    {
        // echo iDebug::backtraceHtml();

        // 优先级 1: 从 URL 参数获取语言
        $lang = Request::get('lang');
        // var_dump(['Request::get(\'lang\')'=>$lang]);
        // 优先级 2: 从 Session 获取语言
        if (empty($lang)) {
            $lang = self::$language;
            // var_dump(['self::$language'=>$lang]);
        }
        if (empty($lang)) {
            $lang = Session::get('language');
            // var_dump(['Session::get(\'language\')'=>$lang]);
        }
        // 优先级 3: 从浏览器语言头获取语言
        if (empty($lang) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            list($locale, $lang) = self::getLocale();
            $lower = strtolower($locale);
            if ($lang == 'zh' && $lower != 'zh-cn') {
                $lang = 'zh-tw';
            }
        }

        // 优先级 4: 默认语言
        if (empty($lang)) {
            $lang = self::$default; // 默认语言为中文
        }
        $lang = Security::safeStr($lang, '-');
        return $lang;
    }

    /**
     * 获取默认语言包
     *
     * @param string $lg 语言代码
     * @return array
     */
    public static function defaults($lg = null)
    {
        return Cache::temp('defaults', function ($lg) {
            is_null($lg) && $lg = self::getLang();
            $pattern = iPHP_CONFIG_DIR . "/lang/{$lg}*.json";
            $files = glob($pattern, GLOB_BRACE);
            krsort($files);
            $lang = [];
            foreach ($files as $path) {
                $_lang = iJson::decode($path);
                is_array($_lang) && self::merge($lang, $_lang);
            }
            return $lang;
        }, [$lg]);
    }

    /**
     * 获取语言包包名
     */
    public static function package()
    {
        $pattern = iPHP_CONFIG_DIR . '/lang/*.json';
        $files = glob($pattern, GLOB_BRACE);
        krsort($files);
        $lang = [];
        foreach ($files as $path) {
            $decode = iJson::decode($path);
            is_array($decode) && $lang[$decode['Package']] = $decode['Language'];
        }
        return $lang;
    }

    /**
     * 查找语言包
     */
    public static function json(&$langs, $lg, $app = '*')
    {
        //获取默认语言包
        $langs = self::defaults($lg);
        //优先查找本地语言包
        //aa/etc/lang/zh.json
        $many = Etc::many($app, "lang/{$lg}", 'recursive', false);
        $many && self::merge($langs, $many);
        //如果多个语言包,则合并并排除 zh.admincp*.json
        //aa/etc/lang/zh.aa.json
        $many = Etc::many($app, "lang/{$lg}.*", 'recursive', false, '.admincp.');
        $many && self::merge($langs, $many);
    }

    /**
     * 获取全部语言包
     *
     * @return array
     */
    public static function all($expire = '-1')
    {
        $key = 'lang:all';
        $langs = Cache::KV($key, function () {
            $lg = self::getLang();
            self::json($langs, $lg);
            return $langs;
        }, $expire);
        self::merge($langs);
        return self::$DATA;
    }

    /**
     * 加载语言包
     *
     * @param string $app 应用名
     * @param string $foo 语言包类型
     */
    public static function load($app = '*', $foo = 'admincp')
    {
        $lg = self::getLang();
        //{$app}/etc/lang/zh.admincp*.json
        $langs = Etc::many($app, "lang/{$lg}.{$foo}*", 'recursive', false);
        self::merge($langs);
    }

    public static function getJson($name, $lg)
    {
        $langs = [];
        $paths = glob(iPHP_TEMPLATE_DIR . "/{$name}/locales/{$lg}*.json", GLOB_BRACE);
        if(empty($paths)) {
            $paths = glob(iPHP_TEMPLATE_DIR . "/{$name}/lang/{$lg}*.json", GLOB_BRACE);
        }
        $paths && $langs = Etc::many($paths, null, 'recursive', false);
        return $langs;
    }

    /**
     * 模板语言包
     *
     * @param string $tag 语言标签
     * @return string
     */
    public static function template($name = '*')
    {
        $lg = self::getLang();
        $langs = self::getJson($name, $lg);
        self::merge($langs);

        $all = self::getJson($name, 'ALL');
        if ($all) {
            $langs = $all[$lg];
            $langs && self::merge($langs);
        }
    }

    /**
     * 合并语言
     *
     * @param array $langs 语言包
     * @param array|bool $data 合并数组
     * @return array
     */
    public static function merge(&$langs, $data = null)
    {
        if ($data === null) {
            $data = $langs;
            $langs = self::$DATA;
        }
        self::$DATA = $langs = array_replace_recursive($langs, $data);
    }

    /**
     * 获取语言文本
     *
     * @param string $tag 语言标签
     * @return string
     */
    public static function text($tag = null)
    {
        $langs = self::$DATA ?: [];
        if (!$tag) {
            return $tag;
        }
        $regex = '/^(?=[\w:-]*$)(?=.*[A-Za-z])([\w-]+:)*[\w-]+$/';
        if (!preg_match($regex, $tag)) {
            return $tag;
        }
        // $tag = htmlspecialchars($tag, ENT_QUOTES, 'UTF-8');
        $text = $tag;
        $flag_common = false;

        if (strpos($tag, ':') === false) {
            if (is_string($tag) && ctype_upper($tag[0]) && isset($langs[$tag])) {
                return $langs[$tag];
            } else {
                if (!isset($langs[$tag])) {
                    $tag = 'common:' . $tag;
                    $flag_common = true;
                }
            }
        }

        $tagArray = explode(':', $tag);
        if (is_array($tagArray)) {
            foreach ($tagArray as $k) {
                if (is_array($langs) && isset($k)) {
                    $langs = $langs[$k];
                }
            }
            $text = $langs ?: $tag;
        }
        if ($flag_common) {
            $text = str_replace('common:', '', $text);
        }
        return $text;
    }
}
