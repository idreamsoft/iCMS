<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class LangI18n
{
    public static function parse($content, $translations = [])
    {
        // 匹配：{{ $i('key') }} 或 {{ $i("key", { ... }) }}
        $pattern = '/\{\{\s*\$[t|i]\s*\(\s*([\'"])(.*?)\1\s*(?:,\s*(\{(?:[^{}]|(?3))*\}))?\s*\)\s*\}\}/';

        $callback = function ($matches) use ($translations) {
            $quote = $matches[1];     // 单引号或双引号
            $key = $matches[2];       // 翻译键，如 app.title
            $paramsRaw = isset($matches[3]) ?$matches[3]: null; // 参数部分（JSON-like 字符串）

            // 支持层级嵌套 key（例如 app.title）
            $translated = self::getTranslationByKey($translations, $key);

            if (!$translated) {
                return $matches[2]; // 无翻译，保留原样
            }

            if ($paramsRaw) {
                // 尝试解析 JSON-like 的参数字符串为数组
                $params = self::parseParams($paramsRaw);
                foreach ($params as $k => $v) {
                    $translated = str_replace("%$k%", $v, $translated);
                }
            }

            return $translated;
        };

        return preg_replace_callback($pattern, $callback, $content);
    }

    /**
     * 根据 . 号分隔的 key 获取多级数组中的翻译内容
     */
    protected static function getTranslationByKey($array, $key)
    {
        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $k) {
            if (is_array($current) && array_key_exists($k, $current)) {
                $current = $current[$k];
            } else {
                return null;
            }
        }

        return $current;
    }

    // 辅助函数：解析类似 { name: "Alice", appName: 'MyApp' } 的对象表达式
    public static function parseParams($jsonLike)
    {
        // 替换 key 上的单引号为双引号，并处理 : 为 =
        $ini = str_replace(['{', '}', "'", '"'], '', $jsonLike);
        $lines = explode(',', $ini);

        $result = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            [$key, $value] = array_map('trim', explode(':', $line, 2));
            $result[$key] = trim($value, "'\" "); // 去除可能的引号
        }

        return $result;
    }
}
