<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class SpiderApp extends AppsBase
{
    public function __construct()
    {
        $admin = AdminModel::get(1);
        Admin::init($admin);
        Files::init(Admin::data());
    }

    public static function shell_re($a = null)
    {
        Utils::cliHelp($a, __FUNCTION__, '[pid|rid|cid]=value');
        $where = [];
        if ($pid = $a['pid']) {
            $where['pid'] = $pid;
        }
        if ($rid = $a['rid']) {
            $where['rid'] = $rid;
        }
        if ($cid = $a['cid']) {
            $where['cid'] = $cid;
        }
        if ($where) {
            $rows = SpiderUrlModel::where($where)->orderBy('id', 'desc')->select();

            Spider::$isShell = true;
            Spider::$work = 'shell';
            foreach ((array)$rows as $key => $row) {
                // $_POST['status'] = '0';
                Spider::$indexid = $row['indexid'];
                Spider::$pid = $row['pid'];
                Spider::$rid = $row['rid'];
                Spider::$url = $row['url'];
                SpiderTools::prints("重新采集：%s", [Spider::$url], 's');
                try {
                    $callback = Spider::publish('shell');
                } catch (Exception $ex) {
                    SpiderTools::prints("重新采集失败:%s", [$ex->getMessage()], 'p');
                    continue;
                }
                SpiderTools::prints("%s 重新采集完成", [Spider::$url], 's');
                print_r($callback);
                echo "\n";
            }
        }
    }

    public static function shell_auto($a = null)
    {
        Utils::cliHelp($a, __FUNCTION__);

        $project = SpiderProjectModel::where([
            'auto'=> 1,
        ])->orderBy('id', 'desc')->select();
        Spider::$isShell = true;
        Spider::$work = 'shell';
        foreach ((array)$project as $key => $pro) {
            $pfile = iPHP_APP_CACHE . '/spider.' . $pro['id'] . '.pid';
            $GLOBALS['shutdown_pid'] = $pfile;
            if (file_exists($pfile)) {
                $time = filemtime($pfile);
                if ($time - $project['lastupdate'] >= $project['psleep']) {
                    File::rm($pfile);
                } else {
                    echo 'project[' . $pro['id'] . '],runing...' . PHP_EOL;
                    SpiderTools::prints("方案ID:%s,开始采集...", [$pro['id']], 's');

                    continue;
                }
            }
            file_put_contents($pfile, $pro['id']);
            Spider::$pid = $pro['id'];
            try {
                SpiderList::crawl('shell', $pro['id'], $pro['rid']);
            } catch (Exception $ex) {
                SpiderTools::prints("采集失败:%s", [$ex->getMessage()], 'p');
                continue;
            }
            @unlink($pfile);
        }
    }

    public static function shell_pid($pid = null)
    {
        empty($pid) && $pid = '--help';
        Utils::cliHelp($pid, __FUNCTION__, '[pid]');

        Http::$CURLOPT_TIMEOUT = 60; //数据传输的最大允许时间
        Http::$CURLOPT_CONNECTTIMEOUT = 10;  //连接超时时间

        $project = explode(',', $pid);
        Spider::$isShell = true;
        Spider::$work = 'shell';
        foreach ((array)$project as $key => $pid) {
            if (empty($pid)) {
                continue;
            }
            $pfile = iPHP_APP_CACHE . '/spider.' . $pid . '.pid';
            $GLOBALS['shutdown_pid'] = $pfile;
            if (file_exists($pfile)) {
                $project = SpiderProjectModel::get($pid);
                $time = filemtime($pfile);
                if ($time - $project['lastupdate'] >= $project['psleep']) {
                    File::rm($pfile);
                } else {
                    echo 'project[' . $pid . '],runing...' . PHP_EOL;
                    continue;
                }
            }
            file_put_contents($pfile, $pid);
            Spider::$pid = $pid;
            try {
                SpiderList::crawl('shell');
            } catch (Exception $ex) {
                SpiderTools::prints("采集失败:%s", [$ex->getMessage()], 'p');
                continue;
            }
            @unlink($pfile);
        }
    }
}
