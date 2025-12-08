<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ChainHook
{

    /**
     * 微信接口:内链替换
     * @param [type] $content [参数]
     * @return [string]       [返回替换过的内容]
     */
    public static function weixin($content)
    {
    }
    /**
     * 钩子:内链替换
     * @param [type] $content [参数]
     * @return [string]       [返回替换过的内容]
     */
    public static function field_hook($content)
    {
        $limit = Config::get('chain.limit');
        if ($limit == 0) {
            return $content;
        }
        $array = Cache::get(Chain::CACHE_KEY);
        if ($array && is_string($content)) {
            return Chain::replace($array, $content, $limit);
        }
        return $content;
    }
}
