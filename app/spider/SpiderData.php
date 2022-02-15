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

class SpiderData
{

    public static function crawl($_pid = NULL, $_rid = NULL, $_url = NULL, $_title = NULL, $_cid = NULL)
    {
        @set_time_limit(0);
        $urlId = Spider::$urlId;
        if ($urlId) {
            $sRs   = SpiderUrlModel::get($urlId);
            $title = $sRs->title;
            $cid   = $sRs->cid;
            $pid   = $sRs->pid;
            $url   = $sRs->url;
            $rid   = $sRs->rid;
        } else {
            $title = Spider::$title;
            $cid   = Spider::$cid;
            $pid   = Spider::$pid;
            $url   = Spider::$url;
            $rid   = Spider::$rid;

            $_title === NULL or $title = $_title;
            $_cid   === NULL or $cid = $_cid;
            $_pid   === NULL or $pid = $_pid;
            $_url   === NULL or $url = $_url;
            $_rid   === NULL or $rid = $_rid;
        }

        if ($pid) {
            $project        = SpiderProject::get($pid);
            $prule_list_url = $project['list_url'];
        }

        $srule           = SpiderRule::get($rid);
        $rule            = $srule['rule'];
        $dataArray       = $rule['data'];

        if ($prule_list_url) {
            $rule['list_url']   = $prule_list_url;
        }

        if (Spider::$isTest) {
            $QUERY_STRING = parse_url_qs($_SERVER['QUERY_STRING']);
            echo '
            <form action="'.$_SERVER['SCRIPT_NAME'].'" method="get">
            <input name="app" type="hidden" value="'.$_GET['app'].'">
            <input name="do" type="hidden" value="'.$_GET['do'].'">
            pid:<input name="pid" value="'.Spider::$pid.'"/><br />
            rid:<input name="rid" value="'.Spider::$rid.'"/><br />
            <input name="url" value="'.Spider::$url.'" style="width:450px;"/><br />
            <input name="title" value="'.Spider::$title.'" style="width:450px;"/>
            <button type="submit" class="btn btn-primary"><i class="fa fa-fw fa-check"></i> 抓取</button>
            </form>
            ';

            echo "<b>抓取规则信息</b><pre style='max-height:300px;overflow-y: scroll;'>";
            print_r(Security::escapeStr($srule));
            print_r(Security::escapeStr($project));
            echo "</pre><hr />";
        }

        $rule['proxy']          && SpiderHttp::$CURL_PROXY = $rule['proxy'];
        $rule['data_charset']   && Spider::$charset = $rule['data_charset'];
        $rule['data_user_agent'] && Spider::$useragent = $rule['data_user_agent'];

        $responses = array();
        if (Spider::$isShell) {
            // SpiderTools::prints('抓取内容链接:[w]%s[/w]', [$url], 'g');
        }
        $html = SpiderHttp::remote($url, __METHOD__);
        if (empty($html)) {
            $msg = sprintf(
                "%s\n采集结果为空,请检查请求是否正常\n<pre style='font-size: 12px;font-weight: normal;'>CURL_INFO=>%s</pre>",
                $url,
                var_export(SpiderHttp::$CURL_INFO, true)
            );
            SpiderError::log(
                $msg,
                $url,
                __CLASS__,
                compact('rid', 'pid')
            );
        }

        Spider::$allHtml        = array();
        $rule['__url__']        = Spider::$url;
        $responses['reurl']     = Spider::$url;
        $responses['__title__'] = $title;

        self::set_http_config($rule);
        self::set_watermark_config($rule);

        $subArray = array();

        foreach ((array)$dataArray as $key => $data) {
            $dname = $data['name'];
            if (empty($dname)) {
                continue;
            }

            //子采集
            //ooxx@字段,ooxx@URLS
            //@URLS 必需设置
            //chapter@URLS
            //chapter@title
            if (strpos($dname, '@') !== false) {
                list($sn, $sf) = explode('@', $dname);
                if ($sf === 'POST') {
                    $subArray[$sn]['POST'] = $data['rule'];
                    continue;
                }
                if ($sf !== 'URLS') {
                    $subArray[$sn]['RULES'][$sf] = $data;
                    continue;
                }
            }

            $content_html = $html;
            //设置数据源
            if ($data['data@source']) {
                $content_html = SpiderTools::getDATA($responses, $data['data@source']);
            }

            /**
             * [UNSET:name]
             * 注销[name]
             * @var string
             */
            if (strpos($dname, 'UNSET:') !== false) {
                $_dname = str_replace('UNSET:', '', $dname);
                unset($responses[$_dname]);
                continue;
            }
            /**
             * [DATA:name]
             * 把之前[name]处理完的数据当作原始数据
             * 如果之前有数据会叠加
             * 用于数据多次处理
             * @var string
             */
            if (strpos($dname, 'DATA:') !== false) {
                $_dname = str_replace('DATA:', '', $dname);
                $content_html = $responses[$_dname];
                unset($responses[$dname]);
            }
            /**
             * [PRE:name] 前置采集
             * 把PRE:name采集到的数据 当做原始数据
             * 一般用于抓取内容
             */
            $pre_dname = 'PRE:' . $dname;
            if (isset($responses[$pre_dname])) {
                $content_html = $responses[$pre_dname];
                unset($responses[$pre_dname]);
            }

            /**
             * [EMPTY:name]
             * 如果[name]之前抓取结果数据为空使用这个数据项替换
             */
            if (strpos($dname, 'EMPTY:') !== false) {
                $_dname = str_replace('EMPTY:', '', $dname);
                if (empty($responses[$_dname])) {
                    $dname = $_dname;
                } else {
                    //有值不执行抓取
                    continue;
                }
            }

            try {
                $content = SpiderContent::crawl($content_html, $data, $rule, $responses);
            } catch (\FalseEx $ex) {
                $content = null;
            } catch (\sException $ex) {
                throw $ex;
            }

            if ($data['empty']) {
                $empty = SpiderTools::real_empty($content);
                if (empty($empty)) {
                    $msg = '数据项[%s]:规则设置不允许为空.当前抓取结果为空.请检查规则是否正确![%s]';
                    $msg = sprintf($msg, $data['name'], __CLASS__);
                    SpiderError::alert(
                        $msg,
                        $rule['__url__'],
                        __CLASS__
                    );
                }
            }
            //子采集
            //ooxx@URLS 获取主采集,抓取的链接
            if (strpos($dname, '@URLS') !== false) {
                $subArray[$sn][$sf] = $content;
                continue;
            }

            unset($content_html);
            //转换二维组
            // if (strpos($dname, 'ARRAY:') !== false) {
            //     $dname = str_replace('ARRAY:', '', $dname);
            //     $cArray = array();

            //     foreach ((array)$content as $k => $value) {
            //         foreach ((array)$value as $key => $val) {
            //             $cArray[$key][$k] = $val;
            //         }
            //     }
            //     if ($cArray) {
            //         $content = $cArray;
            //         unset($cArray);
            //     }
            // }

            /**
             * [name.xxx]
             * [name.xxx.oo]
             * 采集内容做为数组
             */
            if (strpos($dname, '.') !== false) {
                $dnameArr = explode('.', $dname);
                $array = make_multi_array($dname, $content);
                $responses = array_merge_recursive($responses, $array);
            } else {
                /**
                 * 多个name 内容合并
                 */
                if (isset($responses[$dname])) {
                    if (is_array($responses[$dname])) {
                        $responses[$dname] = array_merge((array)$responses[$dname], (array)$content);
                    } else {
                        $responses[$dname] .= $content;
                    }
                } else {
                    $responses[$dname] = $content;
                }
            }
            /**
             * 对匹配多条的数据去重过滤
             */
            if (!is_array($responses[$dname]) && $data['multi']) {
                if (strpos($responses[$dname], ',') !== false) {
                    $_dnameArray = explode(',', $responses[$dname]);
                    $dnameArray  = array();
                    foreach ((array)$_dnameArray as $key => $value) {
                        $value = trim($value);
                        $value && $dnameArray[] = $value;
                    }
                    $dnameArray = array_filter($dnameArray);
                    $dnameArray = array_unique($dnameArray);
                    $responses[$dname] = implode(',', $dnameArray);
                    unset($dnameArray, $_dnameArray);
                }
            }

            gc_collect_cycles();
        }
        foreach ($responses as $key => $value) {
            if (strpos($key, ':') !== false) {
                unset($responses[$key]);
            } else {
                if ($key != 'body') {
                    $responses[$key] = str_replace(iPHP_PAGEBREAK, ',', $responses[$key]);
                }
            }
            if (is_array($responses[$key])) {
                // $responses[$key] = array_filter($responses[$key]);
                // $responses[$key] = array_unique($responses[$key]);
            }
        }

        if (isset($responses['title']) && empty($responses['title'])) {
            $responses['title'] = $responses['__title__'];
        }

        Spider::$allHtml = array();
        unset($html);

        gc_collect_cycles();

        //子采集 可独立发布
        if ($subArray) {
            if (Spider::$isTest) {
                $subData = array();
                foreach ($subArray as $key => $value) {
                    $value['URLS'] && $subData[] = self::sub_crawl($value, $rule);
                }
            } else {
                $responses['commit:callData'] = $subArray;
            }
        }

        if (Spider::$isTest) {
            echo "<b>最终采集结果:</b>";
            echo "<pre style='width:99%;word-wrap: break-word;white-space: pre-wrap;'>";
            print_r(Security::escapeStr($responses));
            if ($subArray) {
                echo '<br /><div style="padding: 20px;background-color: #ebebeb;">';
                echo "<b>子采集结果(只测试第一条):</b><br />";
                print_r(Security::escapeStr($subData));
                echo "</div>";
            }
            echo '<hr />';
            echo '使用内存:' . File::sizeUnit(memory_get_usage()) . ' 执行时间:' . Helper::timerStop() . 's';
            echo "</pre>";
        }

        if (Spider::$callback['data'] && is_callable(Spider::$callback['data'])) {
            $responses = call_user_func_array(Spider::$callback['data'], array($responses, $rule));
        }

        return $responses;
    }
    /**
     * [sub_crawl 执行子采集]
     * @param  [type] $sub  [子采集配置]
     * @param  [type] $rule [主采集规则]
     * @param  [type] $indexid [indexid]
     * @return [type]       [description]
     */
    public static function sub_crawl($sub, $rule, $id = 0)
    {
        $sPOST = $sub['POST'];
        $urls  = $sub['URLS'];
        $RULES = $sub['RULES'];
        if (!is_array($urls) && $urls) {
            $urls = explode(",", $urls);
        }
        if (empty($urls)) return;

        $count = count($urls);

        if (Spider::$isTest) {
            echo '<div style="padding: 20px;background-color: #ebebeb;">';
            echo "<h3>执行子采集:</h3>";
            echo "<hr />";
        } else {
            $_POST = array();
            if ($sPOST && strpos($sPOST, 'poid@') !== false) {
                list($_s, $poid) = explode('@', $sPOST);
                $spost  = SpiderPost::get($poid);
                // $appid   = $spost->app;
                if ($id && $spost->primary) {
                    $_POST[$spost->primary] = $id;
                }
            }
            if (Spider::$isShell) {
                SpiderTools::prints('执行子采集,使用发布规则[%s] 共%s条', [$poid, $count], 'g');
            }
        }

        $responses = array();
        $index = 1;
        foreach ($urls as $key => $url) {
            $data = self::sub_crawl_data($url, $RULES, $rule);
            if (Spider::$isTest) {
                $responses[$key] = $data;
                break;
            }
            if ($spost) {
                $_POST = array_merge($_POST, $data);
                $ret = SpiderPost::commit(false, $spost);
                is_array($ret) && $id = $ret[$spost->primary];
                if ($id) {
                    SpiderUrl::createList($id, $_POST['reurl'], $spost->app);
                    if (Spider::$isShell) {
                        SpiderTools::prints('(%s/%s) id:%s', [$index, $count, $id], 's');
                        $index++;
                    }
                } else {
                    SpiderError::log(
                        '子采集发布错误',
                        $url,
                        __CLASS__,
                        array(
                            'pid' => Spider::$pid,
                            'rid' => Spider::$rid
                        )
                    );
                }
                $responses[$key] = $id;
            }
        }

        Spider::$isTest && print "</div>";

        return $responses;
    }
    //子采集数据
    public static function sub_crawl_data($url, $RULES, $rule)
    {
        $responses = array();
        $responses['reurl'] = $url;
        $html = SpiderHttp::remote($url, __METHOD__);
        foreach ($RULES as $dname => $dvar) {
            $content = SpiderContent::crawl($html, $dvar, $rule, $responses);
            $responses[$dname] = $content;
        }
        return $responses;
    }

    public static function set_http_config($rule)
    {
        Http::$CURLOPT_ENCODING       = '';
        Http::$CURLOPT_REFERER        = '';
        Http::$CURLOPT_TIMEOUT        = 10;
        Http::$CURLOPT_CONNECTTIMEOUT = 3;
        $rule['http']['ENCODING']      && Http::$CURLOPT_ENCODING        = $rule['http']['ENCODING'];
        $rule['http']['REFERER']       && Http::$CURLOPT_REFERER         = $rule['http']['REFERER'];
        $rule['http']['TIMEOUT']       && Http::$CURLOPT_TIMEOUT         = $rule['http']['TIMEOUT'];
        $rule['http']['CONNECTTIMEOUT'] && Http::$CURLOPT_CONNECTTIMEOUT  = $rule['http']['CONNECTTIMEOUT'];
    }
    public static function set_watermark_config($rule)
    {
        // FilesMark::$config['pos'] = Config::get('watermark.pos');
        // FilesMark::$config['x']   = Config::get('watermark.x');
        // FilesMark::$config['y']   = Config::get('watermark.y');
        // FilesMark::$config['img'] = Config::get('watermark.img');
        FilesMark::$enable = Config::get('watermark.enable');
        FilesMark::$config = Config::get('watermark');

        if ($rule['watermark_mode']) {
            FilesMark::$config['pos'] = $rule['watermark']['pos'];
            FilesMark::$config['x']   = $rule['watermark']['x'];
            FilesMark::$config['y']   = $rule['watermark']['y'];
            $rule['watermark']['img'] && FilesMark::$config['img'] = $rule['watermark']['img'];
        }
        if ($rule['watermark_mode'] == "2") {
            FilesMark::$enable = false;
        }
    }
}
