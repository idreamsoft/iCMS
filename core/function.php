<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
function randColorClass($type = 'both')
{
    // $type 参数说明：
    // 'bg'    - 只返回背景色类
    // 'text'  - 只返回文字色类
    // 'both'  - 随机返回背景色或文字色类（默认）

    // 定义主题名称
    $themes = ['primary', 'default', 'amethyst', 'city', 'flat', 'modern', 'smooth'];

    // 各种变体
    $bgVariants = ['', 'lighter', 'light', 'dark', 'darker', 'op', 'dark-op'];
    $textVariants = ['', 'lighter', 'light', 'dark', 'darker'];

    // 上下文颜色
    $contextual = ['success', 'info', 'warning', 'danger', 'success-light', 'info-light', 'warning-light', 'danger-light'];

    // 决定是主题色还是上下文色
    $isTheme = (rand(0, 1) === 0);

    if ($isTheme) {
        // 主题色
        $theme = $themes[array_rand($themes)];

        // 根据类型选择前缀和变体
        if ($type === 'bg') {
            $prefix = 'bg';
            $variant = $bgVariants[array_rand($bgVariants)];
        } elseif ($type === 'text') {
            $prefix = 'text';
            $variant = $textVariants[array_rand($textVariants)];
        } else {
            // both
            $prefix = (rand(0, 1) === 0) ? 'bg' : 'text';
            $variants = ($prefix === 'bg') ? $bgVariants : $textVariants;
            $variant = $variants[array_rand($variants)];
        }

        $suffix = $variant ? "-$variant" : '';
        return "$prefix-$theme$suffix";
    } else {
        // 上下文色
        $color = $contextual[array_rand($contextual)];

        if ($type === 'bg') {
            $prefix = 'bg';
        } elseif ($type === 'text') {
            $prefix = 'text';
        } else {
            $prefix = (rand(0, 1) === 0) ? 'bg' : 'text';
        }

        return "$prefix-$color";
    }
}
function randomColor($format = 'hex')
{
    // 生成 0 到 255 之间的随机数
    $r = rand(0, 255);
    $g = rand(0, 255);
    $b = rand(0, 255);

    switch (strtolower($format)) {
        case 'hex':
            // 使用 sprintf 将十进制转换为两位十六进制
            return sprintf('#%02X%02X%02X', $r, $g, $b);

        case 'rgb':
            return "rgb($r, $g, $b)";

        case 'rgba':
            // 随机透明度 0.0 到 1.0，保留两位小数
            $a = round(rand(0, 100) / 100, 2);
            return "rgba($r, $g, $b, $a)";

        default:
            return sprintf('#%02X%02X%02X', $r, $g, $b); // 默认返回 hex
    }
}
function randomModernColor($format = 'hex')
{
    // 色相：全色谱随机（0-360）
    $h = rand(0, 360);

    // 饱和度：60% - 100%，保证颜色鲜艳但不刺眼
    $s = rand(60, 100);

    // 亮度：40% - 70%，避免太暗或太亮（现代感的关键）
    $l = rand(40, 70);

    // 将 HSL 转换为 RGB
    $h /= 360;
    $s /= 100;
    $l /= 100;

    if ($s == 0) {
        $r = $g = $b = $l; // 灰色
    } else {
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $r = hueToRgb($p, $q, $h + 1 / 3);
        $g = hueToRgb($p, $q, $h);
        $b = hueToRgb($p, $q, $h - 1 / 3);
    }

    $r = round($r * 255);
    $g = round($g * 255);
    $b = round($b * 255);

    switch (strtolower($format)) {
        case 'hex':
            return sprintf('#%02X%02X%02X', $r, $g, $b);
        case 'rgb':
            return "rgb($r, $g, $b)";
        case 'rgba':
            $a = round(rand(70, 100) / 100, 2); // 透明度 0.7~1.0
            return "rgba($r, $g, $b, $a)";
        default:
            return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
}

// 辅助函数：HSL 转 RGB 的中间计算
function hueToRgb($p, $q, $t)
{
    if ($t < 0) {
        $t += 1;
    }
    if ($t > 1) {
        $t -= 1;
    }
    if ($t < 1 / 6) {
        return $p + ($q - $p) * 6 * $t;
    }
    if ($t < 1 / 2) {
        return $q;
    }
    if ($t < 2 / 3) {
        return $p + ($q - $p) * (2 / 3 - $t) * 6;
    }
    return $p;
}
function autoformat($html)
{
    Vendor::import('HtmlAutoFormatter');
    return HtmlAutoFormatter::autoformat($html);
}

function cnum($subject)
{
    $searchList = [
        ['ⅰ', 'ⅱ', 'ⅲ', 'ⅳ', 'ⅴ', 'ⅵ', 'ⅶ', 'ⅷ', 'ⅸ', 'ⅹ'],
        ['㈠', '㈡', '㈢', '㈣', '㈤', '㈥', '㈦', '㈧', '㈨', '㈩'],
        ['①', '②', '③', '④', '⑤', '⑥', '⑦', '⑧', '⑨', '⑩'],
        ['一', '二', '三', '四', '五', '六', '七', '八', '九', '十'],
        ['零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖', '拾'],
        ['Ⅰ', 'Ⅱ', 'Ⅲ', 'Ⅳ', 'Ⅴ', 'Ⅵ', 'Ⅶ', 'Ⅷ', 'Ⅸ', 'Ⅹ', 'Ⅺ', 'Ⅻ'],
        ['⑴', '⑵', '⑶', '⑷', '⑸', '⑹', '⑺', '⑻', '⑼', '⑽', '⑾', '⑿', '⒀', '⒁', '⒂', '⒃', '⒄', '⒅', '⒆', '⒇'],
        ['⒈', '⒉', '⒊', '⒋', '⒌', '⒍', '⒎', '⒏', '⒐', '⒑', '⒒', '⒓', '⒔', '⒕', '⒖', '⒗', '⒘', '⒙', '⒚', '⒛']
    ];
    $replace = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];
    foreach ($searchList as $key => $search) {
        $subject = str_replace($search, $replace, $subject);
    }

    return $subject;
}
function archive_date($date)
{
    $limit = time() - $date;
    if ($limit <= 86400) {
        return '今天';
    } elseif ($limit > 86400 && $limit <= 172800) {
        return '昨天';
    } else {
        //return get_date($date,'dm');
        return '<span class="day">' . get_date($date, 'd') . '</span><span class="mon">' . get_date($date, 'm') . '月</span>';
    }
}
function key2num($resource)
{
    $sort_key = 0;
    // $_release = array();
    foreach ((array)$resource as $key => $value) {
        $_resource[$sort_key] = $value;
        // $_release[$sort_key] = $value['items'][0];
        ++$sort_key;
    }
    return $_resource;
}

function text2link($text = '')
{
    if ($text && strpos($text, '||') !== false) {
        list($title, $url) = explode('||', $text);
        return '<a href="' . $url . '" target="_blank">' . $title . '</a>';
    } else {
        return $text;
    }
}
function put_php_file($path, $data)
{
    $data = iPHP_FILE_HEAD . PHP_EOL . $data;
    file_put_contents($path, $data);
}
function get_php_file($path)
{
    if (is_file($path)) {
        $json = file_get_contents($path);
        $json = get_php_content($json);
    }
    return $json;
}
function get_php_content($content)
{
    $content = str_replace(iPHP_FILE_HEAD, '', $content);
    $content = trim($content);
    return $content;
}

function iCMS_php_head()
{
    return '<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-' . date('Y') . ' iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*
*/
defined(\'iPHP\') OR exit(\'What are you doing?\');
';
}

/**
 * Json数据格式化
 * @param  Mixed  $data   数据
 * @param  String $indent 缩进字符，默认4个空格
 * @return String
 * @source 来源网络
 * @author 佚名
 */

function jsonFormat($data, $indent = null)
{
    is_array($data) or $data = json_decode($data, true);
    if (empty($data)) {
        return '';
    }
    
    // 使用PHP内置的JSON_PRETTY_PRINT选项，更可靠
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // 如果需要自定义缩进，则替换默认的4个空格
    if ($indent !== null && $indent !== '    ') {
        $json = str_replace('    ', $indent, $json);
    }
    
    return $json;
}


//二维数组按给定键名过滤
function pickKeys($resource, $keys, $remove = false)
{
    is_array($keys) or $keys = explode(',', $keys);
    foreach ((array)$resource as $key => $value) {
        foreach ($value as $k => $v) {
            if (in_array($k, $keys)) {
                if ($remove) {
                    unset($resource[$key][$k]);
                }
            } else {
                if (!$remove) {
                    unset($resource[$key][$k]);
                }
            }
        }
    }
    return  $resource;
}

function cutWords($body, $length = 100, $enable = true)
{
    if ($enable && $length) {
        $bodyText = is_array($body) ? implode("\n", $body) : $body;
        $bodyText = str_replace(iPHP_PAGEBREAK, "\n", $bodyText);
        $bodyText = str_replace('</p><p>', "</p>\n<p>", $bodyText);

        $textArray = explode("\n", $bodyText);
        $pageNum = 0;
        $resource = [];
        foreach ($textArray as $key => $p) {
            $text = preg_replace(['/<[\/\!]*?[^<>]*?>/is', '/\s{2,}/is'], '', $p);
            // $pageLen   = strlen($resource);
            // $output    = implode('',array_slice($textArray,$key));
            // $outputLen = strlen($output);
            $output = implode('', $resource);
            $outputLen = strlen($output);
            if ($outputLen > $length) {
                // $pageNum++;
                // $resource[$pageNum] = $p;
                break;
            } else {
                $resource[] = $text;
            }
        }
        $words = implode("\n", $resource);
        $words = iString::cut($words, $length);
        $words = trim($words);
        $words = str_replace(iPHP_PAGEBREAK, '', $words);
        $words = preg_replace('/^[\s|\n|\t]{2,}/m', '', $words);
        unset($bodyText);
        return $words;
    }
}
function mask($string, $start = 2, $end = 2, $mask = '**')
{
    $length = mb_strlen($string, 'UTF-8');

    if ($length <= ($start + $end)) {
        return $string;
    }

    $firstPart = mb_substr($string, 0, $start, 'UTF-8');
    $lastPart = mb_substr($string, -$end, $end, 'UTF-8');

    return $firstPart . $mask . $lastPart;
}
