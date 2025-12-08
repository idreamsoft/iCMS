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
class SpiderAdmincp extends SpiderBase
{
    /**
     * 手动采集页
     * @return void
     */
    public function do_manual()
    {
        $responses = SpiderList::crawl('WEB@MANUAL');
        extract($responses);
        include self::view('spider.manual');
    }

    /**
     * 自动采集页
     * @return void
     */
    public function do_auto()
    {
        $tasks = SpiderList::crawl('WEB@AUTO');
        include self::view('auto');
    }

    public function done_auto()
    {
        return $this->do_crawl();
    }
    // public function done_start()
    // {
    //     $a = SpiderList::crawl('WEB@AUTO');
    //     $this->do_mpublish($a);
    // }

    public function done_crawl()
    {
        $input = Request::input();
        return $this->do_crawl($input);
    }

    public function do_crawl($a = null)
    {
        try {
            $_POST && $a = $_POST;

            Spider::$cid = $a['cid'];
            Spider::$pid = $a['pid'];
            Spider::$rid = $a['rid'];
            Spider::$url = $a['url'];
            Spider::$title = $a['title'];

            $publish = Spider::publish('WEB@AUTO');
            $text = sprintf(
                '%s <span class="badge bg-success">发布成功</span> => 内容ID[%s]',
                Spider::$url,
                $publish['indexid']
            );
            $state = 'success';
        } catch (\FalseEx $ex) {
            $msg = $ex->getMessage();
            $state = $ex->getState();
            if ($state == 'published') {
                $text = sprintf('<span class="badge bg-info-light">已发布</span> %s', $msg);
                $state = 'published';
            } else {
                $text = sprintf('%s <span class="badge bg-warning">发布出错</span> %s', Spider::$url, $msg);
                $state = 'error';
            }
        }
        return ['state'=> $state, 'data'=> [
            'text'  => $text,
            'url'   => Spider::$url,
            'title' => Spider::$title,
        ]];
    }

    /**
     * 批量发布
     * @return void
     */
    public function do_mpublish($listArray = [])
    {
        if ($_POST['pub']) {
            $listArray = [];
            foreach ((array) $_POST['pub'] as $i => $uri) {
                parse_str($uri, $output);
                if ($output) {
                    $output['index'] = $i;
                    $listArray[$i] = $output;
                }
            }
        }
        empty($listArray) && self::alert('暂无最新内容');
        Script::$break = false;
        Script::flush_start();
        Script::dialog('开始采集', '', false, 0, false);
        include self::view('spider.mpublish');
    }

    /**
     * 发布
     * @return void
     */
    public function done_publish($work = null)
    {
        return $this->publish($work);
    }

    public function do_publish($work = null)
    {
        return $this->publish($work);
    }

    public function publish($work = null)
    {
        try {
            $result = Spider::publish($work);
            Admincp::$APP_METHOD = __FUNCTION__;
            return $result;
        } catch (\FalseEx $ex) {
            $msg = $ex->getMessage();
            self::alert($msg);
        }
    }
}
