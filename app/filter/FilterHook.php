<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilterHook
{

    /**
     * [钩子:查找禁用词,返回true或false]
     * @param [string] $content [参数]
     * @return [string]         [返回禁用词]
     */
    public static function run_disable($content)
    {
        return Filter::disable($content) ? true : false;
    }
    /**
     * [钩子:关键词替换过滤,返回替换过的内容]
     * @param [sting] $content [参数]
     * @return [string]        [返回替换过的内容]
     */
    public static function run_filter($content)
    {
        return Filter::replace($content);
    }
}
