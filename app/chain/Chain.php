<?php

class Chain
{
    const APP = 'chain';
    const APPID = iCMS_APP_CHAIN;
    const CACHE_KEY = 'chain';

    public static function cache()
    {
        $result = ChainModel::orderBy('CHAR_LENGTH(`keyword`)', 'DESC')->select();
        $array = array();
        foreach ((array) $result as $i => $val) {
            $a = array($val['keyword']);
            if (Request::isUrl($val['replace'], true)) {
                $val['replace'] = self::getLink($val['keyword'], $val['replace'], false);
            } else {
                $val['replace'] = htmlspecialchars_decode($val['replace']);
                $val['replace'] = str_replace(array('&#34;', '&#39;'), array('"', "'"), $val['replace']);
            }
            $array[] = array($val['keyword'], $val['replace']);
        }
        Cache::set(self::CACHE_KEY, $array, 0);
    }
    public static function getLink($name, $url, $flag = true)
    {
        $link = sprintf('<a href="%s" target="_blank" class="chain">%s</a>', $url, $name);
        if ($flag) {
            $link = htmlspecialchars($link);
            $link = str_replace(array('"', "'"), array('&#34;', '&#39;'), $link);
        }
        return $link;
    }
    public static function replace($array, $content, $limit = '-1')
    {
        preg_match_all("/<a[^>]*?>(.*?)<\/a>/si", $content, $matches); //链接不替换
        $linkArray  = array_unique($matches[0]);
        $linkArray && $linkflip = array_flip($linkArray);
        foreach ((array) $linkflip as $linkHtml => $linkkey) {
            $linkA[$linkkey] = '@L_' . rand(1, 1000) . '_' . $linkkey . '@';
        }
        $content = str_replace($linkArray, $linkA, $content);

        preg_match_all("/<[\/\!]*?[^<>]*?>/si", $content, $matches);
        $htmArray   = (array) array_unique($matches[0]);
        $htmArray && $htmflip = array_flip($htmArray);
        foreach ((array) $htmflip as $kHtml => $vkey) {
            $htmA[$vkey] = "@H_" . rand(1, 1000) . '_' . $vkey . '@';
        }
        $content = str_replace($htmArray, $htmA, $content);

        // constructing mask(s)...
        foreach ((array) $array as $k => $v) {
            $search[$k]   = '@' . preg_quote($v[0], '@') . '@i';
            $replace[$k] = "@R_" . rand(1, 1000) . '_' . $k . '@';
            $replaceArray[$k]  = $v[1];
        }

        $content = preg_replace($search, $replace, $content, $limit);
        $content = str_replace($replace, $replaceArray, $content);
        $content = str_replace($htmA, $htmArray, $content);
        $content = str_replace($linkA, $linkArray, $content);
        unset($linkArray, $linkflip, $linkA);
        unset($htmArray, $htmflip, $htmA);
        unset($replace, $replaceArray);
        unset($search, $matches);
        return $content;
    }
}
