<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');

class SpiderHelper
{
    public static $MAP = [];
    public static function test($content, $rule, $responses, $data)
    {
        var_dump($content, $rule, $responses, $data);
        exit;
    }
    public static function getTitles($key = null)
    {
        if (empty(self::$MAP)) {
            $array = self::getArray();
            foreach ($array as $idx => $value) {
                self::$MAP += $value;
            }
        }
        return $key ? self::$MAP[$key] : self::$MAP;
    }
    public static function getArray()
    {
        $helper = [];
        $many = Etc::many('spider', 'spider.helper*');
        Etc::mergeRecursive($many, $helper, false);
        return $helper;
    }
    public static function run($content, $data, $rule, $responses)
    {
        if ($data['process']) {
            Spider::$isTest && print "<b>数据处理:</b><br />";
            foreach ($data['process'] as $key => $value) {
                if (!is_array($value)) {
                    continue;
                }
                //特殊处理方法
                //@方法
                //@check_urls
                if (substr($value['helper'], 0, 1) == '@') {
                    $sk = substr($value['helper'], 1);
                    $value[$sk]   = true;
                    $sfuncArray[] = $value;
                    continue;
                }
                if (Spider::$isTest) {
                    $hNo = $key + 1;
                    echo $hNo . '# ' . $value['helper'];
                    if ($value['helper'] == 'dataclean') {
                        echo '(' . htmlspecialchars($value['rule']) . ')';
                    }
                    echo '<br />';
                }
                $value[$value['helper']] = true;
                if (is_array($content) && substr($value['helper'], 0, 6) !== 'array_') {
                    foreach ($content as $idx => &$con) {
                        self::helper($con, $value, $rule, $responses, $data);
                    }
                } else {
                    self::helper($content, $value, $rule, $responses, $data);
                }
            }
        }
        is_array($content) && $content = array_filter($content);

        if ($sfuncArray) foreach ($sfuncArray as $key => $value) {
            Spider::$isTest && print ($hNo + 1) . '# ' . $value['helper'] . '<br />';
            $content = self::helper_func($content, $value, $rule);
        }
        return $content;
    }
    public static function helper(&$content, $process, $rule, $responses, $data)
    {
        $helper_callback = Spider::$callback['helper'];
        //Spider::$callback['helper']['dataclean'] = function(){}
        if ($helper_callback && isset($helper_callback[$process['helper']])) {
            $process_helper = $helper_callback[$process['helper']];
            if ($process_helper && is_callable($process_helper)) {
                return call_user_func_array($process_helper, array($content, $process, $rule, $responses, $data));
            }
        }

        switch ($process['helper']) {
            case 'dataclean':
                $content = SpiderTools::dataClean($process['rule'], $content);
                /**
                 * 在数据项里调用之前采集的数据[DATA@name][DATA@name.key]
                 */
                if (strpos($content, '[DATA@') !== false) {
                    $content = SpiderTools::getDATA($responses, $content);
                }
                break;
            case 'stripslashes':
                $content = stripslashes($content);
                break;
            case 'addslashes':
                $content = addslashes($content);
                break;
            case 'base64_encode':
                $content = base64_encode($content);
                break;
            case 'base64_decode':
                $content = base64_decode($content);
                break;
            case 'urlencode':
                $content = urlencode($content);
                break;
            case 'urldecode':
                $content = urldecode($content);
                break;
            case 'rawurlencode':
                $content = rawurlencode($content);
                break;
            case 'rawurldecode':
                $content = rawurldecode($content);
                break;
            case 'parse_str':
                $content = parse_url_qs($content);
                break;
            case 'http_build_query':
                is_array($content) && $content = http_build_query($content);
                break;
            case 'trim':
                if (is_array($content)) {
                    $content = array_map('trim', $content);
                } else {
                    $content = str_replace('&nbsp;', '', trim($content));
                }
                break;
            case 'json_encode':
                is_array($content) && $content = json_encode($content);
                break;
            case 'json_decode':
                $content = json_decode($content, true);
                if (is_null($content)) {
                    $msg = '数据项[%s]:JSON ERROR(%s)';
                    $msg = sprintf($msg, $data['name'], json_last_error_msg());
                    SpiderError::alert(
                        $msg,
                        $rule['__url__'],
                        __CLASS__
                    );
                }
                break;
            case 'htmlspecialchars_decode':
                $content = htmlspecialchars_decode($content);
                break;
            case 'htmlspecialchars':
                $content = htmlspecialchars($content);
                break;
            case 'cleanhtml':
                $content = preg_replace('/<[\/\!]*?[^<>]*?>/is', '', $content);
                break;
            case 'format':
                if ($process['format'] && $content) {
                    $content = autoformat($content);
                }
                break;
            case 'nl2br':
                if ($process['nl2br'] && $content) {
                    $content = nl2br($content);
                }
                break;
            case 'url_absolute':
                if ($process['url_absolute'] && $content) {
                    $content = SpiderTools::url_complement($rule['__url__'], $content);
                }
                break;
            case 'img_absolute':
                if ($process['img_absolute'] && $content) {
                    $content = SpiderTools::img_url_complement($content, $rule['__url__']);
                }
                break;
            case 'capture':
                $content && $content = SpiderHttp::remote($content, __METHOD__);
                break;
            case 'download':
                $content && $content = FilesClient::remote($content);
                break;
            case 'autobreakpage':
                if ($process['autobreakpage'] && $content) {
                    $content = SpiderTools::autoBreakPage($content);
                }
                break;
            case 'mergepage':
                if ($process['mergepage'] && $content) {
                    $content = SpiderTools::mergePage($content);
                }
                break;
            case 'filter':
                $fwd = iPHP::callback('Filter::run', array(&$content), false);
                if ($fwd) {
                    $msg = '数据项[%s]:中包含【%s】被系统屏蔽的字符!';
                    $msg = sprintf($msg, $data['name'], $fwd);
                    SpiderError::alert(
                        $msg,
                        $rule['__url__'],
                        __CLASS__
                    );
                }
                break;
            case 'empty':
                $empty = SpiderTools::real_empty($content);
                if (empty($empty)) {
                    $msg = '数据项[%s]:规则设置不允许为空,当前抓取结果为空.请检查规则是否正确![%s]';
                    $msg = sprintf($msg, $data['name'], __CLASS__);
                    SpiderError::alert(
                        $msg,
                        $rule['__url__'],
                        __CLASS__
                    );
                }
                unset($empty);
                break;
            case 'xml2array':
                $content = Utils::xmlToArray($content);
                break;
            case 'array':
                if (strpos($content, iPHP_PAGEBREAK) !== false) {
                    $content = explode(iPHP_PAGEBREAK, $content);
                }
                if (empty($content)) {
                    $content = [];
                } else {
                    $content = (array)$content;
                }
                break;
            case 'clean_cn_blank':
                $_content = htmlentities($content);
                $content  = str_replace(array('&#12288;', '&amp;#12288;'), '', $_content);
                unset($_content);
                break;
            case 'array_filter_empty':
                if (is_array($content)) {
                    $content = array_filter($content);
                } else {
                    if (strpos($content, iPHP_PAGEBREAK) !== false) {
                        $content = explode(iPHP_PAGEBREAK, $content);
                        $content = array_filter($content);
                    }
                }
                break;
            case 'array_reverse':
                if (is_array($content)) {
                    $content = array_reverse($content);
                } else {
                    if (strpos($content, iPHP_PAGEBREAK) !== false) {
                        $content = explode(iPHP_PAGEBREAK, $content);
                        $content = array_reverse($content);
                    }
                }
                break;
            case 'implode':
            case 'array_implode':
                is_array($content) && $content = implode(',', $content);
                break;
            case 'explode':
                is_array($content) or $content = explode(',', $content);
                break;
            default:
                if (!is_callable($process['helper'])) {
                    $msg = '数据项[%s]:找不到数据处理方法【%s】跳过该方法';
                    $msg = sprintf($msg, $data['name'], $process['helper']);
                    SpiderError::alert(
                        $msg,
                        $rule['__url__'],
                        __CLASS__,
                        false
                    );
                } else {
                    $content = call_user_func_array($process['helper'], [$content, $data, $responses, $rule]);
                }
                break;
        }
        if (Spider::$callback['helper:end'] && is_callable(Spider::$callback['helper:end'])) {
            $content = call_user_func_array(Spider::$callback['helper:end'], array($content, $process, $rule, $responses, $data));
        }
        return $content;
    }
    public static function helper_func($content, $process, $rule)
    {
        //@check_urls
        if ($process['check_urls']) {
            $content && $content = SpiderTools::check_urls($content);
        }
        //@collect_urls
        if ($process['collect_urls']) {
            $content && $content = SpiderTools::collect_urls($content);
        }
        return $content;
    }
}
