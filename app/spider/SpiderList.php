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

SpiderList::$timer[0] = time();
/**
 * 列表抓取
 */
class SpiderList
{
    /**
     * 设置抓取列表网址
     * 每行一个
     *
     * @var String
     */
    public static $urls  = null;
    /**
     * 设置抓取规则
     *
     * @var Array
     */
    public static $rule  = [];
    /**
     * 采集工作模块
     *
     * @var String
     */
    public static $work  = null;
    public static $ids   = array();
    public static $timer = array();
    public static $result  = array();
    public static $SETTING  = array();


    public static function crawl($work = NULL, $pid = NULL, $_rid = NULL, $_urls = null, $callback = null, $_check = false)
    {
        @set_time_limit(0);
        $pid === NULL && $pid = Spider::$pid;

        if ($pid) {
            $project = SpiderProject::get($pid);
            $cid = $project['cid'];
            $rid = $project['rid'];
        } else {
            $cid = Spider::$cid;
            $rid = Spider::$rid;
        }

        if ($_rid !== NULL) $rid = $_rid;

        if (Spider::$isShell) {
            Helper::buffer();
            $lastupdate = $project['lastupdate'];
            if ($project['psleep']) {
                if (time() - $lastupdate < $project['psleep']) {
                    return SpiderTools::prints(
                        '采集方案 %s[pid=%s],%s刚采集过了,请%s小时后在继续采集',
                        [$project['name'], $pid, format_date($lastupdate), ($project['psleep'] / 3600)],
                        'g'
                    );
                }
            }
            $pid && SpiderTools::prints('开始采集方案[pid=%s]', [$pid], 'g');
            $rid && SpiderTools::prints('使用采集规则[rid=%s]', [$rid], 'g');
        }

        $srule = SpiderRule::get($rid);
        $rule  = self::$rule ?: $srule['rule'];
        $urls  = $rule['list_urls'];

        $project['urls'] && $urls = $project['urls'];
        self::$urls     && $urls = self::$urls;
        $_urls          && $urls = $_urls;

        self::$ids = compact('rid', 'pid');

        //生成列表网址
        $urlsArray = (array)self::makeListUrls($urls, $work);
        if (empty($urlsArray)) {
            $msg = "采集列表为空!请填写!";
            SpiderError::log(
                $msg,
                $urls,
                __CLASS__,
                self::$ids
            );
        }

        if ($rule['mode'] == "2") {
            Vendor::run('phpQuery');
            Spider::$isTest && $_GET['pq_debug'] && phpQuery::$debug = 1;
        }

        self::$result['pubArray'] = array();
        self::$result['pubCount'] = array();
        self::$result['pubAllCount'] = array();

        $_count = count($urlsArray);

        SpiderHttp::$CURL_PROXY = $rule['proxy'];
        Spider::$urlslast   = null;

        if (Spider::$isTest) {
            echo '<b>最终需抓取列表总共:</b>' . $_count . "条<br />";
            echo "<pre style='max-height:150px;overflow-y: scroll;'>";
            print_r($urlsArray);
            echo '</pre>';
            echo '<b>测试第一条</b><br />';
            $urlsArray = array(reset($urlsArray));
        }
        if (Spider::$isShell) {
            SpiderTools::prints('最终需抓取列表总共(%s)条', [$_count], 's');
        }
        $params = compact('rid', 'pid', 'cid');
        foreach ($urlsArray as $key => $url) {

            //获取 $url 中的所有内容网址
            $urlsData = self::fetchListUrlData($url, $params, $rule, $project, $key, $_count);

            //内容规则 PID@xx 返回URL列表
            if ($callback == 'CALLBACK@URL') {
                return array_column($urlsData, 'url');
            } elseif ($work == "WEB@MANUAL") {
                $result[$url] = SpiderUrl::checkout($urlsData, compact('pid'));
            } elseif ($work == "WEB@AUTO") {
                SpiderUrl::checkout($urlsData, compact('pid'));
                self::handleWebAuto($urlsData, $params, $result);
            } elseif ($work == 'DATA@RULE') {
                $result[$url] = self::handleDataRule($urlsData, $params);
            } elseif ($work == "TEST") {
                array_walk($urlsData, [__CLASS__, 'handleMakeTestHtml'], $params);
            } elseif ($work == "shell") {
                self::handleShell($urlsData, $url, $params, $rule, $project, $key);
            }
        }
        gc_collect_cycles();

        switch ($work) {
            case 'WEB@AUTO':
            case 'DATA@RULE':
                return $result;
                break;
            case 'WEB@MANUAL':
                return compact('rid', 'pid', 'cid', 'work', 'rule', 'result');
                break;
            case "shell":
                self::shellOutput($pid);
                break;
        }
    }
    /**
     * 获取 $url 中的所有内容网址
     *
     * @param [type] $key
     * @param string $url
     * @param [type] $pid
     * @param [type] $rule
     * @param [type] $project
     * @param [type] $_count
     * @return Array
     */
    public static function fetchListUrlData(&$url, $params, $rule, $project, $key = 0, $_count = 0)
    {
        extract($params);
        $url = trim($url);
        Spider::$urlslast = $url;
        if ($pid && Spider::$isShell) {
            $lastkey_file   = self::lastkey($pid);
            if (file_exists($lastkey_file)) {
                $lastkey     = file_get_contents($lastkey_file);
                $lastkeytime = filemtime($lastkey_file);
                if (trim($lastkey) > $key && time() - $lastkeytime < $project['psleep']) {
                    return SpiderTools::prints('%s 该列表已经抓取过...', [$url], 'g');
                }
            }
            file_put_contents($lastkey_file, $key);
        }

        if (Spider::$isShell) {
            SpiderTools::prints('开始抓取 %s/%s 条列表链接:[w]%s[/w]', [$key + 1, $_count, $url], 'g');
        }
        if (Spider::$isTest) {
            echo '<b>抓取列表:</b>' . $url . "<br />";
        }
        $html = SpiderHttp::remote($url, __METHOD__);

        if (empty($html)) {
            $msg = "采集列表内容为空!";
            $msg .= var_export(SpiderHttp::$CURL_INFO, true);
            SpiderError::log($msg, $url, __CLASS__, self::$ids);
        }
        if ($rule['list_urls_format']) {
            $html = SpiderTools::dataClean($rule['list_urls_format'], $html);
            if (Spider::$isTest) {
                echo '<b>列表抓取结果整理后:</b>';
                echo '<div style="max-height:300px;overflow-y: scroll;">';
                echo htmlspecialchars($html);
                echo "</div><hr />";
            }
        }
        if ($rule['mode'] == "2") {
            $doc = phpQuery::newDocumentHTML($html, 'UTF-8');
            $list_area = $doc[trim($rule['list_area_rule'])];
            // if(strpos($rule['list_area_format'], 'DOM::')!==false){
            //     $list_area = SpiderTools::dataClean($rule['list_area_format'], $list_area);
            // }

            if ($rule['list_area_format']) {
                $list_area_format = trim($rule['list_area_format']);
                //ARRAY::div.class
                if (strpos($list_area_format, 'ARRAY::') !== false) {
                    $list_area_format = str_replace('ARRAY::', '', $list_area_format);
                    $lists = array();
                    foreach ($list_area as $la_key => $la) {
                        $lists[] = phpQuery::pq($list_area_format, $la);
                    }
                } else {
                    $lists = phpQuery::pq($list_area_format, $list_area);
                }
            } else {
                $lists = $list_area;
            }
        } elseif ($rule['mode'] == "3") {
            $list_area = json_decode($html, true);

            if (Spider::$isTest && is_null($list_area)) {
                echo '<b>JSON解析错误:</b>';
                echo '<b>' . json_last_error_msg() . '</b>';
                echo "<hr />";
            }
            //data->list->title
            if ($rule['list_area_rule']) {
                $list_area_rule = explode('->', $rule['list_area_rule']);
                $level = 0;
                $lists = SpiderTools::array_filter_key($list_area, $list_area_rule, $level);
            } else {
                $lists = $list_area;
            }
            if ($rule['list_area_format']) {
                $lists = SpiderTools::dataClean($rule['list_area_format'], $lists);
            }
        } else {
            $list_area_rule = SpiderTools::pregTag($rule['list_area_rule']);
            if ($list_area_rule && $rule['list_area_rule'] != '<%content%>') {
                preg_match('|' . $list_area_rule . '|is', $html, $matches);
                $list_area = $matches['content'];
            } else {
                $list_area = $html;
            }
            if ($rule['list_area_format']) {
                $list_area = SpiderTools::dataClean($rule['list_area_format'], $list_area);
            }
            preg_match_all('|' . SpiderTools::pregTag($rule['list_url_rule']) . '|is', $list_area, $lists, PREG_SET_ORDER);
        }
        $html = null;
        unset($html);

        if (Spider::$isTest) {
            echo '<b>列表采集模式:</b>' . Security::escapeStr($rule['mode']) . "<br />";
            echo '<b>列表区域规则:</b>' . Security::escapeStr($rule['list_area_rule']) . "<br />";
            echo '<b>列表区域整理:</b>' . Security::escapeStr($rule['list_area_format']);
            echo "<hr />";
            echo '<b>列表区域抓取结果:</b>';
            echo '<div style="max-height:300px;overflow-y: scroll;">';
            if (is_array($list_area)) {
                echo "<pre>";
                var_dump($list_area);
                echo "</pre>";
            } else {
                echo Security::escapeStr($list_area);
            }
            echo '</div>';
            echo "<hr />";
            echo '<b>列表链接规则:</b>' . Security::escapeStr($rule['list_url_rule']);
            echo "<hr />";
            if ($project['list_url']) {
                echo '<b>方案网址合成规则:</b>' . Security::escapeStr($project['list_url']);
            } else {
                echo '<b>规则网址合成规则:</b>' . Security::escapeStr($rule['list_url']);
            }
            echo "<hr />";
        }
        $list_area = null;
        unset($list_area);

        if ($project['list_url']) {
            $rule['list_url'] = $project['list_url'];
        }

        $urlsData = self::datas($lists, $rule, $url);

        if ($rule['sort'] == "1") {
            //arsort($lists);
        } elseif ($rule['sort'] == "2") {
            krsort($urlsData);
        } elseif ($rule['sort'] == "3") {
            shuffle($urlsData);
        }
        if ($rule['mode'] == "2") {
            phpQuery::unloadDocuments($doc->getDocumentID());
        }

        if (Spider::$callback['urls'] && is_callable(Spider::$callback['urls'])) {
            $_work = call_user_func_array(Spider::$callback['urls'], array(&$urlsData, $url));
            if ($_work === false) {
                return;
            }
            $_work && $work = $_work;
        }
        $urlsDataCount = count($urlsData);
        if (empty($urlsDataCount)) {
            SpiderError::alert(
                "采集列表记录为空",
                $url,
                __CLASS__
            );
        }
        self::urlsData($urlsData, $params);
        $lists = null;
        unset($lists);
        gc_collect_cycles();
        return $urlsData;
    }
    /**
     * 对获取到的内容列表 进行过滤 入库
     */
    public static function urlsData(&$urlsData, $params)
    {
        extract($params);
        foreach ($urlsData as $idx => $value) {
            if ($value['url'] === false) {
                unset($urlsData[$idx]);
            }
        }
        //非测试模式 且 开启 Spider::$listSave = true;
        if (!(!Spider::$isTest && Spider::$listSave)) {
            return;
        }
    }
    public static function handleMakeTestHtml($value, $key, $params)
    {
        extract($params);
        $title = $value['title'];
        $url   = $value['url'];
        $hash  = md5($url);
        unset($value['title'], $value['url']);
        if (Spider::$isTest) {
            echo '<b>列表抓取结果:</b>' . $key . '<br />';
            echo $title . ' (<a href="' . ADMINCP_URL . '=spiderProject&do=test' .
                '&url=' . urlencode($url) .
                '&rid=' . $rid .
                '&pid=' . $pid .
                '&title=' . urlencode($title) .
                '" target="_blank">测试内容规则</a>) <br />';
            echo $url . "<br />";
            echo $hash . "<br />";
            if ($value) {
                echo '<b>其它采集结果:</b>';
                echo '<pre>';
                var_dump(array_map('htmlspecialchars', $value));
                echo '</pre>';
            }
            echo "<hr />";
        }
    }
    public static function handleWebAuto($urlsData, $params, &$result)
    {
        extract($params);
        foreach ($urlsData as $lkey => $value) {
            $result[] = array(
                'url'   => $value['url'],
                'title' => $value['title'],
                'cid'   => $cid, 'rid' => $rid, 'pid' => $pid,
                'hash'  => md5($value['url'])
            );
        }
    }
    public static function handleDataRule($urlsData, $params)
    {
        extract($params);
        $work = 'DATA@RULE';
        if (!Spider::$isTest && SpiderList::$SETTING['DATA@RULE']['check']) {
            SpiderUrl::lotUrlCheck($urlsData, $params);
        }
        foreach ($urlsData as $lkey => $value) {
            try {
                $content = SpiderData::crawl($pid, $rid, $value['url'], $value['title']);
                if ($content) {
                    $urlId = SpiderUrl::create([
                        'url'   => $value['url'],
                        'title' => $value['title'],
                        'cid'   => $cid, 'rid' => $rid, 'pid' => $pid,
                        'status'  => '1', 'publish' => '0',
                        //已经采集 未发布
                        'indexid' => '0', 'pubdate' => '0'
                    ]);
                    Spider::$spider_url_ids[$lkey] = $urlId;
                }
            } catch (\sException $ex) {
                throw $ex;
            }
        }
    }
    public static function handleShell($urlsData, $url, $params, $rule, $project, $key)
    {

        extract($params);
        $work = 'shell';
        $urlsDataCount = count($urlsData);
        SpiderTools::prints('获取到内容链接 %s 条记录', [$urlsDataCount], 's');
        self::$result['pubCount'][$key]['url']   = $url;
        self::$result['pubCount'][$key]['count'] = $urlsDataCount;
        self::$result['pubAllCount']['count'] += $urlsDataCount;
        $idx = 1;
        foreach ($urlsData as $lkey => $value) {
            Spider::$title = $value['title'];
            Spider::$url   = $value['url'];
            SpiderTools::prints('开始采集...(%s/%s)', [$idx, $urlsDataCount], 'g');
            SpiderTools::prints('Title:[w]%s[/w]', [Spider::$title], 's');
            SpiderTools::prints('Url:[w]%s[/w]', [Spider::$url], 's');
            $idx++;
            Spider::$rid = $rid;
            try {
                $wait = 3;
                $wait_start = time();
                $publish  = Spider::publish("shell", true);
                self::$result['pubCount'][$key]['success']++;
                self::$result['pubAllCount']['success']++;
                $wait += time() - $wait_start;

                if($publish['id']){
                    SpiderTools::prints('采集完成并发布成功[id=%s]%s√', [$publish['id'], str_repeat('.', $wait)], 'y');
                }else{
                    SpiderTools::prints('采集完成 %s %s√',['', str_repeat('.', $wait)], 'y');
                }

                if ($project['sleep']) {
                    if ($rule['mode'] != "2") {
                        unset($lists[$lkey]);
                    }
                    gc_collect_cycles();
                    $usleep = $project['sleep'] * 1000;
                    SpiderTools::prints('暂停%s秒后继续', [($project['sleep'] / 1000)], 'y');
                    usleep($usleep); //1000000 = 1s
                } else {
                    //sleep(1);
                }
            } catch (\FalseEx $ex) {
                $msg = $ex->getMessage();
                $state = $ex->getState();
                if ($state == "published") {
                    self::$result['pubCount'][$key]['published']++;
                    self::$result['pubAllCount']['published']++;
                    SpiderTools::prints('%s', $msg, 'r');
                    SpiderTools::prints('published', [], 'r');
                } else {
                    self::$result['pubCount'][$key]['error']++;
                    self::$result['pubAllCount']['error']++;
                    SpiderTools::prints('发布出错[%s].跳过当前链接', $msg, 'r');
                }
                continue;
            } catch (\sException $ex) {
                throw $ex;
            }
        }
    }
    public static function shellOutput($pid)
    {
        SpiderProjectModel::update(array('lastupdate' => time()), $pid);
        self::$timer[1] = time();
        echo str_repeat("=", 30) . PHP_EOL;
        $logfile = iPHP_APP_CACHE . "/spider.{$pid}.log";
        SpiderTools::prints('采集数据统结果', [], 'y');
        print_r(self::$result['pubAllCount']);
        SpiderTools::prints('全部采集完成', [], 'y');
        SpiderTools::prints('用时:%s,%s-%s', [
            format_time((self::$timer[1] - self::$timer[0]), 'cn'),
            date("Y-m-d H:i:s", self::$timer[0]),
            date("Y-m-d H:i:s", self::$timer[1])
        ], 'y');
        SpiderTools::prints('详细采集结果请查看:%s', [Security::filterPath($logfile)], 'y');
        echo str_repeat("=", 30) . PHP_EOL;
        $lastkey_file = self::lastkey($pid);
        file_exists($lastkey_file) && @unlink($lastkey_file);
        file_put_contents($logfile, var_export(self::$result['pubCount'], true));
        file_put_contents($logfile, var_export(self::$result['pubAllCount'], true), FILE_APPEND);
    }

    /**
     * 列表生成
     * @param  [type] $urls [description]
     * @return [type]       [description]
     */
    public static function makeListUrls($urls, $work)
    {
        $urlsArray  = explode("\n", $urls);
        $urlsArray  = array_filter($urlsArray);
        $_urlsArray = $urlsArray;
        $urlsList   = array();
        if (Spider::$isShell) {
            // echo "$urls\n";
            print_r($urlsArray);
        }

        foreach ($_urlsArray as $_key => $_url) {
            $_url = trim($_url);
            if (empty($_url)) {
                continue;
            }

            $_url      = htmlspecialchars_decode($_url);
            $_urlsList = array();
            /**
             * RULE@rid@url
             * url使用[rid]规则采集并返回列表结果
             */
            if (strpos($_url, 'RULE@') !== false) {
                if (Spider::$isShell) {
                    echo str_repeat("-=", 30) . PHP_EOL;
                }
                list($___s, $_rid, $_urls) = explode('@', $_url);
                if (Spider::$isTest) {
                    print_r('<b>使用[rid:' . $_rid . ']规则抓取列表</b>:' . $_urls);
                    echo "<hr />";
                }
                $_urlsList = (array)self::crawl($work, false, $_rid, $_urls, 'CALLBACK@URL');

                if (Spider::$isShell) {
                    echo date("Y-m-d H:i:s ") . '使用[rid:' . $_rid . ']规则抓取列表' . PHP_EOL;
                    echo date("Y-m-d H:i:s ") . "获取链接:" . count($_urlsList) . '条记录' . PHP_EOL;
                }

                foreach ($_urlsList as $uk => $vurl) {
                    $urls_match = self::urls_match($vurl);
                    if ($urls_match) {
                        $urlsList  = array_merge($urlsList, $urls_match);
                        unset($_urlsList[$uk]);
                    }
                }
                $_urlsList && $urlsList  = array_merge($urlsList, $_urlsList);
                unset($urlsArray[$_key]);
                if (Spider::$isShell) {
                    echo str_repeat("-=", 30) . PHP_EOL;
                }
            } else {
                $urls_match = self::urls_match($_url);
                if ($urls_match) {
                    $urlsList  = array_merge($urlsList, $urls_match);
                    unset($urlsArray[$_key]);
                }
            }
        }
        $urlsList && $urlsArray = array_merge($urlsArray, $urlsList);
        unset($_urlsArray, $_key, $_url, $_matches, $_urlsList, $urlsList, $urls_match);
        $urlsArray = array_filter($urlsArray);
        $urlsArray = array_unique($urlsArray);
        return $urlsArray;
    }
    /**
     * 列表链接规则
     * @param  [type] $lists [description]
     * @param  [type] $rule  [description]
     * @param  [type] $url   [description]
     * @return Array
     */
    public static function datas($lists, $rule, $url)
    {
        if (Spider::$callback['SpiderList:datas'] && is_callable(Spider::$callback['SpiderList:datas'])) {
            return call_user_func_array(Spider::$callback['SpiderList:datas'], array($lists, $rule, $url));
        }
        $array = array();
        if ($lists) foreach ($lists as $lkey => $row) {
            $data = self::responses($row, $rule, $url);
            if ($data) foreach ($data as $key => $value) {
                if (is_numeric($key) || strpos($key, 'var_') !== false) {
                    unset($data[$key]);
                }
            }
            $data && $array[$lkey] = $data;
        }
        return $array;
    }
    public static function responses($data, $rule, $baseUrl = null)
    {
        $responses = array();

        if ($rule['mode'] == "3") {
            $list_url_rule = explode("\n", $rule['list_url_rule']);
            foreach ($list_url_rule as $key => $value) {
                $key_rule = trim($value);
                if (empty($key_rule)) {
                    continue;
                }
                $rkey = $key_rule;
                $dkey = $key_rule;
                if (strpos($key_rule, '@@') !== false) {
                    list($rkey, $dkey) = explode("@@", $key_rule);
                }
                $data[$dkey] && $responses[$rkey] = $data[$dkey];
            }
        } elseif ($rule['mode'] == "2") {
            // }else if(is_object($data)){
            $DOM = phpQuery::pq($data);

            $dom_key_map = array('title', 'url');
            $list_url_rule = explode("\n", $rule['list_url_rule']);
            empty($list_url_rule) && $list_url_rule = $dom_key_map;
            foreach ($list_url_rule as $key => $value) {
                $dom_rule = trim($value);
                if (empty($dom_rule)) {
                    continue;
                }
                //pic@@DOM::img@src
                $content  = '';
                $dom_key  = '';
                if (strpos($dom_rule, '@@') !== false) {
                    list($dom_key, $dom_rule) = explode("@@", $dom_rule);
                }
                if (strpos($dom_rule, 'DOM::') !== false) {
                    $content = SpiderTools::domAttr($DOM, $dom_rule);
                } else {
                    if ($dom_rule == 'url' || $dom_rule == 'href') {
                        $dom_key  = 'url';
                        $dom_rule = 'href';
                    }
                    if ($dom_rule == 'title' || $dom_rule == 'text') {
                        $dom_key  = 'title';
                        $dom_rule = 'text';
                    }
                    if ($dom_rule == '@title') {
                        $dom_key  = 'title';
                        $dom_rule = 'title';
                    }
                    if ($dom_rule == 'text') {
                        $content = $DOM->text();
                    } else {
                        $content = $DOM->attr($dom_rule);
                    }
                }
                empty($dom_key) && $dom_key  = $dom_key_map[$key];
                $responses[$dom_key] = str_replace('&nbsp;', '', trim($content));
            }
            unset($DOM);
        } elseif (strpos($rule['list_url_rule'], '<%url%>') !== false) {
            $responses = $data;
        }
        $title = trim($responses['title']);
        $url   = trim($responses['url']);
        $url   = str_replace('<%url%>', $url, htmlspecialchars_decode($rule['list_url']));

        preg_match_all('#<%(\w{3,20})%>#is', $url, $f_match);
        foreach ((array)$f_match[1] as $_key => $_name) {
            $url = str_replace($f_match[0][$_key], trim($responses[$_name]), $url);
        }

        if (strpos($url, 'AUTO::') !== false && $baseUrl) {
            $url = str_replace('AUTO::', '', $url);
            $url = SpiderTools::url_complement($baseUrl, $url);
        }

        Request::isUrl($url) or $url = SpiderTools::url_complement($baseUrl, $url);

        if ($rule['list_url_clean']) {
            $url = SpiderTools::dataClean($rule['list_url_clean'], $url);
            if ($url === null) {
                return array();
            }
        }
        $title = preg_replace('/<[\/\!]*?[^<>]*?>/is', '', $title);

        $responses['title'] = $title;
        $responses['url'] = $url;

        return $responses;
    }
    /**
     * 列表网址生成
     * @param  [type] $_url [description]
     * @return [type]       [description]
     */
    public static function urls_match($url)
    {
        preg_match_all('|<(.*?)>|', $url, $matches);
        foreach ($matches[1] as $key => $value) {
            $url = self::urls_make($url, $value);
        }
        return (array)$url;
    }
    public static function urls_make($url, $rule)
    {
        $urlsList = array();
        if (is_array($url)) {
            foreach ($url as $key => $vurl) {
                $_urlsList = self::urls_make($vurl, $rule);
                $urlsList = array_merge($urlsList, $_urlsList);
            }
        } else {
            if (strpos($rule, 'DATE:') !== false) {
                list($type, $format) = explode(':', $rule);
                $urlsList[] = str_replace('<' . $rule . '>', date($format), trim($url));
            } elseif (strpos($rule, 'FOR:') !== false) {
                //<FOR:1-100>
                list($type, $format) = explode(':', $rule);
                list($start, $end) = explode('-', $format);
                if ($start > $end) {
                    //<FOR:100-1>
                    for ($i = $start; $i >= $end; $i--) {
                        $urlsList[] = str_replace('<' . $rule . '>', $i, trim($url));
                    }
                } else {
                    //<FOR:1-100>
                    for ($i = $start; $i <= $end; $i++) {
                        $urlsList[] = str_replace('<' . $rule . '>', $i, trim($url));
                    }
                }
            } elseif (strpos($rule, 'EACH:') !== false) {
                //<EACH:1,2,3,4>
                list($type, $format) = explode(':', $rule);
                $array = explode(',', $format);
                foreach ($array as $key => $value) {
                    $urlsList[] = str_replace('<' . $rule . '>', $value, trim($url));
                }
            } else {
                list($format, $begin, $num, $step, $zeroize, $reverse) = explode(',', $rule);
                $url = str_replace($rule, '*', trim($url));
                $_urlsList = SpiderTools::mkurls($url, $format, $begin, $num, $step, $zeroize, $reverse);
                $urlsList = array_merge($urlsList, $_urlsList);
            }
        }
        return $urlsList;
    }
    public static function lastkey($pid)
    {
        return sprintf("%s/spider.%s.lastkey.pid", iPHP_APP_CACHE, $pid);
    }
}
