<?php

// namespace iPHP\core;

/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
class Pinyin
{
    /**
     * 词库来自Overtrue\Pinyin
     *
     * @return Array
     */
    public static function table()
    {
        if (empty($GLOBALS['iPHP_PINTIN_TABLE'])) {
            $table = gzuncompress(file_get_contents(__DIR__ . '/pinyin/pinyin.table'));
            $GLOBALS['iPHP_PINTIN_TABLE'] = json_decode($table, true);
            // $files = glob(__DIR__.'/pinyin/words_*');
            // $GLOBALS["iPHP_PINTIN_TABLE"] = [];
            // foreach ($files as $key => $value) {
            //     $GLOBALS["iPHP_PINTIN_TABLE"]+= include $value;
            // }
        }
        return $GLOBALS['iPHP_PINTIN_TABLE'];
    }
    public static function isAlphaNumeric($str) {
        return preg_match('/^[a-zA-Z0-9\s]+$/', $str) === 1;
    }
    public static function get($string, $split = '', $punct = true)
    {
        $enstr = preg_replace('/[^(\x{4e00}-\x{9fff}|\w+|\s)]/u', '', trim($string));
        // 判断一个字符串是否全为英文字符或数字
        if(self::isAlphaNumeric($enstr)){
            return preg_replace("/\s+/i", $split, trim($enstr));
        }
        //英文标点
        // $string = preg_replace("/[[:punct:]]/i", '', $string);
        //移除除中文字母数字外的所有字符
        $punct && $string = preg_replace('/[^(\x{4e00}-\x{9fff}|\w+)]/u', '', trim($string));
        
        $table = self::table();
        $string = strtr($string, $table);
        $string = self::format($string);
        $string = preg_replace("/\s+/i", $split, trim($string));
        $split && $string = preg_replace('/[' . preg_quote($split) . ']+/i', $split, $string);
        return $string;
    }

    /**
     * Overtrue\Pinyin\Pinyin
     *
     * @param string $pinyin
     * @return string
     */
    public static function format($pinyin)
    {
        if (empty($pinyin)) {
            return $pinyin;
        }

        $replacements = [
            'üē' => ['ue', 1], 'üé' => ['ue', 2], 'üě' => ['ue', 3], 'üè' => ['ue', 4],
            'ā'  => ['a', 1], 'ē' => ['e', 1], 'ī' => ['i', 1], 'ō' => ['o', 1], 'ū' => ['u', 1], 'ǖ' => ['yu', 1],
            'á'  => ['a', 2], 'é' => ['e', 2], 'í' => ['i', 2], 'ó' => ['o', 2], 'ú' => ['u', 2], 'ǘ' => ['yu', 2],
            'ǎ'  => ['a', 3], 'ě' => ['e', 3], 'ǐ' => ['i', 3], 'ǒ' => ['o', 3], 'ǔ' => ['u', 3], 'ǚ' => ['yu', 3],
            'à'  => ['a', 4], 'è' => ['e', 4], 'ì' => ['i', 4], 'ò' => ['o', 4], 'ù' => ['u', 4], 'ǜ' => ['yu', 4],
        ];

        foreach ($replacements as $unicode => $replacement) {
            if (false !== strpos($pinyin, $unicode)) {
                $umlaut = $replacement[0];
                // https://zh.wikipedia.org/wiki/%C3%9C
                if ('yu' == $umlaut) {
                    $umlaut = 'v';
                }
                $pinyin = str_replace($unicode, $umlaut, $pinyin);
                // $pinyin = str_replace($unicode, $umlaut, $pinyin).($this->hasOption($option, PINYIN_ASCII_TONE) ? $replacement[1] : '');
            }
        }

        return $pinyin;
    }
}
