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

class SpiderTools
{
    public static $listArray   = array();
    public static $callback    = array();
    /**
     * 在数据项里调用之前采集的数据[DATA@name][DATA@name.key]
     * [DATA@list:name]调用列表其它数据
     */
    public static function getDATA($responses, $content)
    {
        preg_match_all('#\[DATA@(.*?)\]#is', $content, $data_match);
        $_data_replace = array();
        if (strpos($content, 'DATA@list:') !== false) {
            $listData = self::listData($responses['reurl']);
        }
        foreach ((array)$data_match[1] as $_key => $_name) {
            $_nameKeys = explode('.', $_name);
            if (strpos($_name, 'list:') !== false) {
                $_name    = str_replace('list:', '', $_name);
                $_content = $listData[$_name];
            } else {
                $_content  = $responses[$_nameKeys[0]];
            }
            if (count($_nameKeys) > 1) {
                foreach ((array)$_nameKeys as $kk => $nk) {
                    $kk && $_content = $_content[$nk];
                }
            }
            $_data_replace[$_key] = $_content;
        }
        if ($_data_replace) {
            if (count($data_match[0]) > 1 || !is_array($_data_replace[0])) {
                $content = str_replace($data_match[0], $_data_replace, $content);
            } else {
                $content = $_data_replace[0];
            }
        }
        unset($data_match, $_data_replace, $_content);
        return $content;
    }
    public static function domAttr($DOM, $selectors, $fun = 'text')
    {
        //DOM::a@href
        $selectors = str_replace('DOM::', '', $selectors);
        list($selector, $attr) = explode("@", $selectors);

        if ($attr) {
            if ($attr == 'text') {
                return trim($DOM[$selector]->text());
            }
            return $DOM[$selector]->attr($attr);
        } else {
            return $DOM[$selector]->$fun();
        }
    }
    public static function pregTag($rule)
    {
        $rule = trim($rule);
        if (empty($rule)) {
            return false;
        }
        $rule = str_replace("%>", "%>\n", $rule);
        preg_match_all("/<%(.+)%>/i", $rule, $matches);
        $pregArray = array_unique($matches[0]);
        $pregflip = array_flip($pregArray);
        foreach ((array)$pregflip as $kpreg => $vkey) {
            $pregA[$vkey] = "@@@@iCMS_PREG_" . rand(1, 1000) . '_' . $vkey . '@@@@';
        }
        $rule = str_replace($pregArray, $pregA, $rule);
        $rule = preg_quote($rule, '~');
        $rule = str_replace($pregA, $pregArray, $rule);
        $rule = str_replace("%>\n", "%>", $rule);
        $rule = preg_replace('~<%(\w{3,20})%>~i', '(?<\\1>.*?)', $rule);
        $rule = str_replace(array('<%', '%>'), '', $rule);
        unset($pregArray, $pregflip, $matches);
        gc_collect_cycles();
        //var_dump(htmlspecialchars($rule));
        return $rule;
    }
    public static function dataClean($rules, $content)
    {
        Vendor::run('phpQuery');
        $ruleArray = explode("\n", $rules);
        $NEED = $NOT = array();
        foreach ($ruleArray as $key => $rule) {
            $rule = trim($rule);
            $rule = str_replace('<BR>', "\n", $rule);
            $rule = str_replace('<n>', "\n", $rule);
            if (strpos($rule, 'BEFOR::') !== false) {
                $befor = str_replace('BEFOR::', '', $rule);
                $content = $befor . $content;
            } else if (strpos($rule, 'AFTER::') !== false) {
                $after = str_replace('AFTER::', '', $rule);
                $content = $content . $after;
            } else if (strpos($rule, 'IF::') !== false) {
                list($expr, $tf) = explode('?', $rule);
                $find = str_replace('IF::', '', $expr);
                empty($tf) && $tf = '1:0';
                list($t, $f) = explode(':', $tf);
                $t = str_replace('<%SELF%>', $content, $t);
                $content = strpos($content, $find) === false ? $f : $t;
            } else if (strpos($rule, 'CUT::') !== false) {
                $len = str_replace('CUT::', '', $rule);
                $content = iString::cut($content, $len);
            } else if (strpos($rule, 'SPLIT::') !== false) {
                $delimiter = str_replace('SPLIT::', '', $rule);
                $content = explode($delimiter, $content);
            } else if (strpos($rule, '<%SELF%>') !== false) {
                $content = str_replace('<%SELF%>', $content, $rule);
            } else if (strpos($rule, '<%nbsp%>') !== false) {
                $content  = str_replace(array('&nbsp;', '&#12288;'), '', $content);
                $_content = htmlentities($content);
                $content  = str_replace(array('&nbsp;', '&#12288;', '&amp;nbsp;', '&amp;#12288;'), '', $_content);
                $content  = html_entity_decode($content);
                unset($_content);
            } else if (strpos($rule, 'HTML::') !== false) {
                $tag = str_replace('HTML::', '', $rule);
                if ($tag == 'ALL') {
                    $content = preg_replace('/<[\/\!]*?[^<>]*?>/is', '', $content);
                } else {
                    $rep = "\\1";
                    if (strpos($tag, '*') !== false) {
                        $rep = '';
                        $tag = str_replace('*', '', $tag);
                    }
                    $content = preg_replace("/<{$tag}[^>].*?>(.*?)<\/{$tag}>/si", $rep, $content);
                    $content = preg_replace("@<{$tag}[^>]*>@is", "", $content);
                }
            } else if (strpos($rule, 'LEN::') !== false) {
                $len        = str_replace('LEN::', '', $rule);
                $len_content = preg_replace(array('/<[\/\!]*?[^<>]*?>/is', '/\s*/is'), '', $content);
                if (iString::strlen($len_content) < $len) {
                    return null;
                }
            } else if (strpos($rule, 'IMG::') !== false) {
                $img_count = str_replace('IMG::', '', $rule);
                preg_match_all("/<img.*?src\s*=[\"|'](.*?)[\"|']/is", $content, $match);
                $img_array  = array_unique($match[1]);
                if (count($img_array) < $img_count) {
                    return null;
                }
            } else if (strpos($rule, 'DOM::') !== false) {
                $rule = str_replace('DOM::', '', $rule);
                $dflag = false;
                if ($rule[0] == ':') {
                    $dflag = true;
                    $rule = substr($rule, 1);
                }
                $doc = phpQuery::newDocumentHTML($content, 'UTF-8');
                //DOM::div.class::attr::ooxx
                //DOM::div.class[fun][attr]
                //DOM::div.title[attr][data-title]
                list($pq_dom, $pq_fun, $pq_attr) = explode("::", $rule);
                if (strpos($rule, '][') !== false) {
                    list($pq_dom, $pq_fun, $pq_attr) = explode("[", $rule);
                    $pq_fun  = rtrim($pq_fun, ']');
                    $pq_attr = rtrim($pq_attr, ']');
                }
                $pq_array = phpQuery::pq($pq_dom);
                foreach ($pq_array as $pq_key => $pq_val) {
                    if ($pq_fun) {
                        if ($pq_attr) {
                            $pq_content = phpQuery::pq($pq_val)->$pq_fun($pq_attr);
                        } else {
                            $pq_content = phpQuery::pq($pq_val)->$pq_fun();
                        }
                    } else {
                        $pq_content = (string)phpQuery::pq($pq_val);
                    }
                    $pq_pattern[$pq_key] = $pq_content;
                }
                phpQuery::unloadDocuments($doc->getDocumentID());
                if ($dflag) {
                    $_content[$key] = implode('', (array)$pq_pattern);
                } else {
                    $content = str_replace($pq_pattern, '', $content);
                }
                unset($doc, $pq_array);
            } else if (strpos($rule, '==') !== false) {
                list($_pattern, $_replacement) = explode("==", $rule);
                $_pattern     = trim($_pattern);
                $_replacement = trim($_replacement);
                $_replacement = str_replace('\n', "\n", $_replacement);
                if (strpos($_pattern, '~SELF~') !== false) {
                    $_pattern = str_replace('~SELF~', $content, $_pattern);
                }
                if (strpos($_replacement, '~SELF~') !== false) {
                    $_replacement = str_replace('~SELF~', $content, $_replacement);
                }
                if (strpos($_replacement, '~S~') !== false) {
                    $_replacement = str_replace('~S~', ' ', $_replacement);
                }
                if (strpos($_replacement, '~N~') !== false) {
                    $_replacement = str_replace('~N~', "\n", $_replacement);
                }
                $replacement[$key] = $_replacement;
                $pattern[$key] = '|' . self::pregTag($_pattern) . '|is';
                $content = preg_replace($pattern, $replacement, $content);
            } else if (strpos($rule, 'KEY::') !== false) {
                $rule = str_replace('KEY::', '', $rule);
                $content = $content[$rule];
            } else if (strpos($rule, 'FUNC::') !== false) {
                preg_match('/FUNC::(\w+)\(/is', $rule, $func_match);
                $func = $func_match[1];
                preg_match_all('/[\"|\'](.*?)[\"|\']/is', $rule, $param_match);
                $param = $param_match[1];
                foreach ($param as $key => $value) {
                    if ($value == '@me') {
                        $param[$key] = $content;
                    }
                }
                $content = call_user_func_array($func, $param);
            } else if (strpos($rule, 'NEED::') !== false) {
                $NEED[$key] = self::data_check('NEED::', $rule, $content);
            } else if (strpos($rule, 'NOT::') !== false) {
                $NOT[$key] = self::data_check('NOT::', $rule, $content);
            } else {
                $content = preg_replace('|' . self::pregTag($rule) . '|is', '', $content);
            }
        }
        if (is_array($_content)) {
            $content = implode('', $_content);
        }
        if ($NOT) {
            $content = self::data_check_result($NOT, 'NOT::');
            if ($content === null) {
                return null;
            }
        }
        if ($NEED) {
            $content = self::data_check_result($NEED, 'NEED::');
            if ($content === null) {
                return null;
            }
        }
        unset($NOT, $NEED);
        return $content;
    }
    public static function data_check_result($variable, $prefix)
    {
        foreach ((array)$variable as $key => $value) {
            if ($value != $prefix) {
                return $value;
            }
        }
        return null;
    }
    public static function data_check($prefix, $rule, $content)
    {
        $check = str_replace($prefix, '', $rule);
        $bool  = array(
            'NOT::'  => false,
            'NEED::' => true
        );
        if (strpos($check, ',') === false) {
            if (strpos($content, $check) === false) {
                $checkflag = false;
            } else {
                $checkflag = true;
            }
        } else {
            $checkArray = explode(',', $check);
            foreach ($checkArray as $key => $value) {
                if (strpos($content, $value) === false) {
                    $checkflag = false;
                } else {
                    $checkflag = true;
                }
                if ($checkflag == $bool[$prefix]) {
                    break;
                }
            }
        }
        return $checkflag === $bool[$prefix] ? $content : $prefix;
    }

    public static function check_content($content, $code)
    {
        if (strpos($code, 'DOM::') !== false) {
            Vendor::run('phpQuery');
            $doc     = phpQuery::newDocumentHTML($content, 'UTF-8');
            $pq_dom  = str_replace('DOM::', '', $code);
            $matches = (bool)(string)phpQuery::pq($pq_dom);
            phpQuery::unloadDocuments($doc->getDocumentID());
            unset($doc, $content);
        } else {
            $_code = self::pregTag($code);
            if (preg_match('/(<\w+>|\.\*|\.\+|\\\d|\\\w)/i', $code)) {
                preg_match('|' . $_code . '|is', $content, $_matches);
                $matches = $_matches['content'];
            } else {
                $matches = strpos($content, $code);
            }
            unset($content);
        }
        return $matches;
    }
    public static function check_content_code($content, $type = null)
    {
        if (Spider::$content_right_code && $type == 'right') {
            $right_code = self::check_content($content, Spider::$content_right_code);
            if ($right_code === false) {
                return false;
            }
        }
        if (Spider::$content_error_code && $type == 'error') {
            $error_code = self::check_content($content, Spider::$content_error_code);
            if ($error_code !== false) {
                return false;
            }
        }
        return true;
    }
    public static function mkurls($url, $format, $begin, $num, $step, $zeroize, $reverse)
    {
        $urls = array();
        $start = (int)$begin;
        if ($format == 0) {
            $num = $num - 1;
            if ($num < 0) {
                $num = 1;
            }
            $end = $start + $num * $step;
        } else if ($format == 1) {
            // $end = $start*pow($step,$num-1);
            $end = $start + $num * $step;
        } else if ($format == 2) {
            $start = ord($begin);
            $end   = ord($num);
            $step  = 1;
        }
        $zeroize = ($zeroize == 'true' ? true : false);
        $reverse = ($reverse == 'true' ? true : false);
        //var_dump($url.','.$format.','.$begin.','.$num.','.$step,$zeroize,$reverse);
        if ($reverse) {
            for ($i = $end; $i >= $start;) {
                $id = $i;
                if ($format == 2) {
                    $id = chr($i);
                }
                if ($zeroize) {
                    $len = strlen($end);
                    //$len==1 && $len=2;
                    $id  = sprintf("%0{$len}d", $i);
                }
                $urls[] = str_replace('<*>', $id, $url);
                // if($format==1){
                //   $i=$i/$step;
                // }else{
                // }
                $i = $i - $step;
            }
        } else {
            for ($i = $start; $i <= $end;) {
                $id = $i;
                if ($format == 2) {
                    $id = chr($i);
                }
                if ($zeroize) {
                    $len = strlen($end);
                    //$len==1 && $len=2;
                    $id  = sprintf("%0{$len}d", $i);
                }
                $urls[] = str_replace('<*>', $id, $url);
                // if($format==1){
                //   $i=$i*$step;
                // }else{
                // }
                $i = $i + $step;
            }
        }
        return $urls;
    }

    public static function check_urls($content)
    {
        if (is_array($content)) {
            $content = array_filter($content);
            $content = array_unique($content);
        }
        if ($content) {
            $where = ['url' => $content];
            $all = SpiderUrlListModel::field('id,url')->where($where)->select();
            if ($all) {
                $urls   = array_column($all, 'url', 'id');
                $content = array_diff($content, $urls);
                if (Spider::$isShell) {
                    print self::datetime() . "\033[36mSpiderTools::check_urls\033[0m => 已采[" . count($urls) . "]条,还剩[" . count($content) . "]条" . PHP_EOL;
                }
            }
        }
        return $content;
    }
    public static function collect_urls($content)
    {
        if (is_array($content)) {
            $content = array_filter($content);
            $content = array_unique($content);
        }

        $pid   = Spider::$pid;
        $rid   = Spider::$rid;
        $source = 'spider_url_collect';
        $table = sprintf("%s_%d", $source, $rid);
        $path  = iPHP_APP_CACHE . '/spider/' . $table . '.txt';
        if ($rid) {
            if (!file_exists($path)) {
                if (!DB::hasTable($table)) {
                    try {
                        return DB::copy($source, $table);
                    } catch (sException $ex) {
                        $state = $ex->getState();
                        if ($state === '42000') { //无创建表权限
                            throw $ex;
                        }
                        return false;
                    }
                }
                File::mkdir(dirname($path));
                file_put_contents($path, time());
            }

            if ($pid) {
                $model = DB::table($table);
                if (is_array($content)) {
                    foreach ($content as $url) {
                        $model->create(
                            array('url' => $url, 'pid' => $pid),
                            true
                        );
                    }
                } else {
                    $model->create(
                        array('url' => $content, 'pid' => $pid),
                        true
                    );
                }
            }
        }
        return $content;
    }
    public static function url_complement($baseUrl, $href)
    {
        $href = trim($href);
        if (Request::isUrl($href)) {
            return $href;
        } else {
            if (substr($href, 0, 1) == '/') {
                $base_uri  = parse_url($baseUrl);
                if ($href[1] == '/') {
                    $base_host = $base_uri['scheme'] . ':/';
                } else {
                    $base_host = $base_uri['scheme'] . '://' . $base_uri['host'];
                }
                return $base_host . '/' . ltrim($href, '/');
            } else {

                if (substr($baseUrl, -1) != '/') {
                    $info = pathinfo($baseUrl);
                    $info['extension'] && $baseUrl = $info['dirname'];
                }
                $baseUrl = rtrim($baseUrl, '/');
                return File::path($baseUrl . '/' . ltrim($href, '/'));
            }
        }
    }
    public static function img_url_complement($content, $baseurl)
    {
        preg_match_all("/<img.*?src\s*=[\"|'](.*?)[\"|']/is", $content, $img_match);
        if ($img_match[1]) {
            $_img_array = array_unique($img_match[1]);
            $_img_urls  = array();
            foreach ((array)$_img_array as $_img_key => $_img_src) {
                $_img_urls[$_img_key] = SpiderTools::url_complement($baseurl, $_img_src);
            }
            $content = str_replace($_img_array, $_img_urls, $content);
        }
        unset($img_match, $_img_array, $_img_urls, $_img_src);
        return $content;
    }
    public static function checkpage(&$newbody, $bodyA, $_count = 1, $nbody = "", $i = 0, $k = 0)
    {
        $ac = count($bodyA);
        $nbody .= $bodyA[$i];
        $pics    = FilesPic::findImgUrl($nbody);
        $_pcount = count($pics);
        //  print_r($_pcount);
        //  echo "\n";
        //  print_r('_count:'.$_count);
        //  echo "\n";
        //  var_dump($_pcount>$_count);
        if ($_pcount >= $_count) {
            $newbody[$k] = $nbody;
            $k++;
            $nbody = "";
        }
        $ni = $i + 1;
        if ($ni <= $ac) {
            self::checkpage($newbody, $bodyA, $_count, $nbody, $ni, $k);
        } else {
            $newbody[$k] = $nbody;
        }
    }
    public static function mergePage($content)
    {
        $_content = $content;
        $pics     = FilesPic::findImgUrl($_content);
        $_pcount  = count($pics);
        if ($_pcount < 4) {
            $content = str_replace(iPHP_PAGEBREAK, "", $content);
        } else {
            $contentA = explode(iPHP_PAGEBREAK, $_content);
            $newcontent = array();
            self::checkpage($newcontent, $contentA, 4);
            if (is_array($newcontent)) {
                $content = array_filter($newcontent);
                $content = implode(iPHP_PAGEBREAK, $content);
                //$content      = addslashes($content);
            } else {
                //$content      = addslashes($newcontent);
                $content = $newcontent;
            }
            unset($newcontent, $contentA);
        }
        unset($_content);
        return $content;
    }
    public static function textlen($string)
    {
        return function_exists('mb_strlen') ? mb_strlen($string, "UTF-8") : strlen($string);
    }
    public static function autoBreakPage($content, $pageBit = '15000', $pageBreak = iPHP_PAGEBREAK)
    {
        $text      = str_replace('</p><p>', "</p>\n<p>", $content);
        $textArray = explode("\n", $text);
        $pageNum   = 0;
        $resource  = array();
        $textLen   = strlen($text);
        $resLen    = 0;
        $pLen    = 0;
        foreach ($textArray as $key => $p) {
            $pageLen = strlen($resource[$pageNum]);
            $pLen   += strlen($p);
            $slen    = $pLen > 0 ? $textLen - $pLen : 0;
            // echo $key.' '.$pageLen.' '.$textLen.' '.$pLen.' '.$slen.PHP_EOL;
            if ($pageLen > $pageBit && $slen > $pageBit) {
                $pageNum++;
                $resource[$pageNum] = $p;
            } else {
                $resource[$pageNum] .= $p;
            }
            unset($textArra[$key]);
        }
        unset($text, $textArray);
        if ($pageBreak === false) {
            return $resource;
        }
        return implode($pageBreak, (array)$resource);
    }


    public static function prints($format, $array, $opt = 'w')
    {
        if (Spider::$isShell || iPHP_SHELL) {
            $map = [
                'r' => "%s\033[%s31m %s \033[0m", //红色字
                'g' => "%s\033[%s32m %s \033[0m", //绿色字
                'y' => "%s\033[%s33m %s \033[0m", //黄色字
                'b' => "%s\033[%s34m %s \033[0m", //蓝色字
                'p' => "%s\033[%s35m %s \033[0m", //紫色字
                's' => "%s\033[%s36m %s \033[0m", //天蓝字
                'w' => "%s\033[%s37m %s \033[0m", //白色字
            ];
            //字背景颜色范围:40 - 49
            $bgcMap = [
                'd' => 40, //黑  dark
                'dr' => 41, //深红 
                'g' => 42, //绿 
                'y' => 43, //黄色 
                'b' => 44, //蓝色 
                'p' => 45, //紫色 
                'dg' => 46, //深绿 
                'w' => 47, //白色
            ];

            //\33[0m 关闭所有属性
            //\33[1m 设置高亮度
            //\33[4m 下划线
            //\33[5m 闪烁
            //\33[7m 反显
            //\33[8m 消隐
            // 字背景颜色范围:40 - 49


            list($color, $bgc, $prop) = explode(':', $opt);

            $format = html2text($format);
            if (strpos($format, '[/') !== false) {
                preg_match("@\[(\w)\](.+)\[\/\w\]@", $format, $matches);
                // var_dump($matches);
                if ($matches) {
                    $format = str_replace($matches[0], '', $format);
                    $_color = $matches[1];
                    $_format = $matches[2];
                }
            }
            $prop && $format1 = sprintf("\33[%sm", $prop);
            $bgc && $format2 = sprintf(
                "%s;",
                is_numeric($bgc) ? $bgc : $bgcMap[$bgc]
            );
            $format = sprintf($map[$color], $format1, $format2, $format) . $_format;
        }
        is_array($array) or $array = [$array];
        array_unshift($array, $format);
        print self::datetime() . call_user_func_array('sprintf', $array) . PHP_EOL;
    }
    public static function datetime()
    {
        $mtimestamp   = sprintf("%.3f", microtime(true)); // 带毫秒的时间戳
        $timestamp    = floor($mtimestamp); // 时间戳
        $milliseconds = round(($mtimestamp - $timestamp) * 1000); // 毫秒
        $milliseconds = sprintf("%-'03s", $milliseconds);
        return date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds . ' ';
    }

    public static function str_cut($str, $start, $end)
    {
        $content = strstr($str, $start);
        $content = substr($content, strlen($start), strpos($content, $end) - strlen($start));
        return $content;
    }

    public static function utf8_num_decode($entity)
    {
        $convmap = array(0x0, 0x10000, 0, 0xfffff);
        return mb_decode_numericentity($entity, $convmap, 'UTF-8');
    }
    public static function utf8_entity_decode($entity)
    {
        $entity  = '&#' . hexdec($entity) . ';';
        $convmap = array(0x0, 0x10000, 0, 0xfffff);
        return mb_decode_numericentity($entity, $convmap, 'UTF-8');
    }
    public static function array_filter_key($array, $filter, $level)
    {
        $_filter = $filter[$level];
        unset($filter[$level]);
        foreach ((array)$array as $key => $value) {
            if ($key == $_filter) {
                if (empty($filter)) {
                    return $value;
                } else {
                    ++$level;
                    return self::array_filter_key($value, $filter, $level);
                }
            } else {
            }
        }
    }
    public static function real_empty($text)
    {
        is_array($text) && $text = implode('', $text);
        $text = strip_tags($text, '<img>');
        $text = preg_replace('/[\s|\r\n]*/', '', $text);
        $text = str_replace(array('&nbsp;', '&#12288;'), '', $text);
        $text = htmlentities($text, ENT_QUOTES | ENT_IGNORE, "UTF-8");
        $text = str_replace(array('&nbsp;', '&#12288;', '&amp;nbsp;', '&amp;#12288;'), '', $text);
        $text = trim($text);
        return $text;
    }
}
