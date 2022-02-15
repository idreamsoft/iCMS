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
class Pinyin
{
    /**
     * 词库来自Overtrue\Pinyin
     *
     * @return Array
     */
    public static function table()
    {
        if (empty($GLOBALS["iPHP_PINTIN_TABLE"])) {
            $table = gzuncompress(file_get_contents(__DIR__ . '/pinyin/pinyin.table'));
            $GLOBALS["iPHP_PINTIN_TABLE"] = json_decode($table, true);
            // $files = glob(__DIR__.'/pinyin/words_*');
            // $GLOBALS["iPHP_PINTIN_TABLE"] = [];
            // foreach ($files as $key => $value) {
            //     $GLOBALS["iPHP_PINTIN_TABLE"]+= include $value;
            // }
        }
        return $GLOBALS["iPHP_PINTIN_TABLE"];
    }

    public static function get($string, $split = "", $punct = true)
    {
        $table = self::table();
        // $string = htmlentities($string, ENT_QUOTES | ENT_IGNORE, "UTF-8");
        if ($punct) {
            //英文标点
            // $string = preg_replace("/[[:punct:]]/i", '', $string);
            //移除除中文字母数字外的所有字符
            $string = preg_replace('/[^(\x{4e00}-\x{9fff}|\w+)]/u', '', trim($string));
            //preg_match_all('/[\x{4e00}-\x{9fff}]|[\w+]/u', $string, $match);
        }
        $string = strtr($string, $table);
        $string = self::format($string);
        $string = preg_replace("/\s+/i", $split, trim($string));
        $split && $string = preg_replace("/[" . preg_quote($split) . "]+/i", $split, $string);
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
        if (empty($pinyin)) return $pinyin;

        $replacements = array(
            'üē' => array('ue', 1), 'üé' => array('ue', 2), 'üě' => array('ue', 3), 'üè' => array('ue', 4),
            'ā' => array('a', 1), 'ē' => array('e', 1), 'ī' => array('i', 1), 'ō' => array('o', 1), 'ū' => array('u', 1), 'ǖ' => array('yu', 1),
            'á' => array('a', 2), 'é' => array('e', 2), 'í' => array('i', 2), 'ó' => array('o', 2), 'ú' => array('u', 2), 'ǘ' => array('yu', 2),
            'ǎ' => array('a', 3), 'ě' => array('e', 3), 'ǐ' => array('i', 3), 'ǒ' => array('o', 3), 'ǔ' => array('u', 3), 'ǚ' => array('yu', 3),
            'à' => array('a', 4), 'è' => array('e', 4), 'ì' => array('i', 4), 'ò' => array('o', 4), 'ù' => array('u', 4), 'ǜ' => array('yu', 4),
        );

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
