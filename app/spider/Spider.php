<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class Spider
{
    const APP = 'spider';
    const APPID = iCMS_APP_SPIDER;

    public static $cid = null;
    public static $rid = null;
    public static $pid = null;
    public static $urlId = null;
    public static $poid = null;
    public static $title = null;
    public static $url = null;
    public static $work = false;
    public static $urlslast = null;
    public static $allHtml = [];
    public static $indexid = null;

    public static $listSave = false;
    public static $isShell = false;
    public static $isTest = false;

    public static $content_right_code = false;
    public static $content_error_code = false;

    public static $referer = null;
    public static $encoding = null;
    public static $useragent = null;
    public static $cookie = null;
    public static $charset = null;
    public static $CURL_PROXY = false;
    public static $PROXY_ARRAY = [];
    public static $PROXY_URL = false;
    /**
     * 采集回调
     *
     * data  内容采集结果回调
     * content 采集项目回调
     * @var array
     */
    public static $callback = [];

    public static $spider_url_ids = [];

    public static function checker($pid = null, $url = null, $title = null)
    {
        $pid === null && $pid = Spider::$pid;
        $url === null && $url = Spider::$url;
        $title === null && $title = Spider::$title;
        $project = SpiderProject::get($pid);
        $indexid = self::getIndexId();
        Spider::$callback['url:id'] = 0;
        Spider::$callback['url:indexid'] = 0;
        Spider::$callback['url:data'] = [];

        if (($project['checker'] && empty($indexid))) {
            $mode = $project['checker'];
            $mode = Spider::$callback['checker:mode'] ?: $mode;
            $url = Spider::$callback['checker:url'] ?: $url;
            $title = Spider::$callback['checker:title'] ?: $title;
            $url = SpiderUrl::url($url);
            $hash = SpiderUrl::hash($url, $title);
            $where = [];

            switch ($mode) {
                case '1': //按网址检查
                case '4': //按网址检查更新
                case '7': //按[网址]检查,只更新[子采集]
                    // $scheme = parse_url($url, PHP_URL_SCHEME);
                    // if ($scheme) {
                    //     $_url  = str_replace($scheme . '://', '', $url);
                    //     $urls = array($_url, 'http://' . $_url, 'https://' . $_url);
                    //     $where['url'] = $urls;
                    // } else {
                    //     $where['url'] = $url;
                    // }
                    $where['url'] = $url;
                    $label = $url . PHP_EOL;
                    $msg = $label . '该网址的内容已经发布过!请检查是否重复';
                    break;
                case '2': //按标题检查
                case '5': //按标题检查更新
                    $where['title'] = $title;
                    $label = $title . PHP_EOL;
                    $msg = $label . '该标题的内容已经发布过!请检查是否重复';
                    break;
                case '3': //网址和标题
                case '6': //网址和标题更新
                    $where['url'] = $url;
                    $where['title'] = $title;
                    $label = $title . PHP_EOL . $url;
                    $msg = $label . '该网址和标题的内容已经发布过!请检查是否重复';
                    break;
                case '8': //路径和标题 hash
                    $where['hash'] = $hash;
                    $label = $title . PHP_EOL . $url . PHP_EOL . $hash;
                    $msg = $label . '该网址和标题的内容已经发布过!请检查是否重复';
                    break;
            }
            switch ($project['self']) {
                case '1':
                    $where['pid'] = $pid;
                    break;
                case '2':
                    $where['rid'] = Spider::$rid;
                    break;
            }
            $row = [];
            if ($where) {
                $md5 = md5(json_encode($where));
                if (Spider::$callback['checker'] && Spider::$callback['checker'] == $md5) {
                    $row = Spider::$callback['url:data'];
                } else {
                    $row = SpiderUrlModel::field('id,indexid,publish,status')->where($where)->get();
                    Spider::$callback['checker'] = $md5;
                }
            }
            if ($row) {
                Spider::$callback['url:data'] = $row;
                if (in_array($mode, ['1', '2', '3'])) {
                    if (in_array($row['publish'], ['1', '2'])) {
                        // if (Spider::$isShell) {
                        //     SpiderTools::prints('%s [publish=%s] %s', [$msg, $row['publish'], __METHOD__], 'r');
                        // }
                        throw new FalseEx($msg, 'published');
                    }
                } else {
                    Spider::$callback['url:id'] = $row['id'];
                    Spider::$callback['url:indexid'] = $row['indexid'];
                    return true;
                }
            } else {
                return true;
            }
        }
        return true;
    }

    public static function publish($work = null, $check = false)
    {
        @set_time_limit(0);
        Spider::$callback['STATUS'] = 'publish';
        // Spider::$callback['RETURN'] = false;

        $check && Spider::checker(Spider::$pid, Spider::$url, Spider::$title);

        $_POST = Spider::$callback['_POST'] ?: SpiderData::crawl();

        if (Spider::$callback['_POST:DATA'] && $_POST) {
            $_POST = array_merge($_POST, Spider::$callback['_POST:DATA']);
        }

        if ($_POST === false) {
            throw new FalseEx('$_POST = false', 0);
        };

        foreach ((array)$_POST as $key => $value) {
            if ($value === null && $key != '__title__') {
                SpiderError::log(
                    sprintf('$_POST[%s] = null', $key),
                    $_POST['reurl'],
                    __CLASS__
                );
            }
        }

        Spider::checker(Spider::$pid, $_POST['reurl'], $_POST['title']);

        if (Spider::$callback['return']) {
            return call_user_func_array(Spider::$callback['return'],[$_POST]);
        }

        $project = SpiderProject::get(Spider::$pid);
        $_POST['cid'] = isset($_POST['cid']) ? $_POST['cid'] : $project['cid'];

        $poid = Spider::$poid ?: $project['poid'];
        $spost = SpiderPost::get($poid);
        if (empty($spost)) {
            throw new FalseEx('SpiderPost empty');
        };

        if ($spost['app']) {
            $className = Admincp::init($spost['app']);
            if (!class_exists($className)) {
                throw new FalseEx($className . ' class not exists');
            }
            $admincp = iPHP::getInstance($className);
            $appid = $admincp->appid();
            !isset($_POST['appid']) && $_POST['appid'] = $appid;
        };

        $indexid = self::getIndexId();
        //已经发布过的
        if ($indexid) {
            if ($spost['primary']) {
                $_POST[$spost['primary']] = $indexid;
            } elseif($admincp) {
                $model = $admincp->getModel();
                $pk = $model::getPrimaryKey();
                $pk && $_POST['rs'][$pk] = $indexid;
                $_POST['dataId'] = $admincp->getDataIds($indexid);
                // $admincp->spider_getDataIds($indexid);
                // self::getAppDataIds($indexid, $app);
            }
        }

        if (Spider::$callback['post'] && is_callable(Spider::$callback['post'])) {
            $ret = call_user_func_array(
                Spider::$callback['post'],
                [&$_POST, Spider::$callback['post:data'], Spider::$urlId]
            );
            if ($ret) {
                return $_POST;
            }
        }

        if ($_POST === false) {
            throw new FalseEx('$_POST = false [2]', 2);
        };

        $urlId = Spider::$callback['url:id'] ?: Spider::$urlId;
        if (empty($urlId)) {
            list($urlId, $newIndexId) = SpiderUrl::getId($_POST); //填加采集数据到spider_url表 并获取ID
            // 如果 newIndexId 存在且与当前的 indexid 不同，则更新 indexid 并处理
            if ($newIndexId && $newIndexId != $indexid) {
                $indexid = $newIndexId;
                // $admincp->spider_getDataIds($indexid);
            }
        }
        //todo list
        //支持 update:xxx 字段更新
        //if($project['checker']=="7"||Spider::$callback['UPDATE_SUB_CRAWL']){
        if (Spider::$callback['UPDATE_SUB_CRAWL']) {
            $result = ['id' => $urlId, 'indexid'=> $indexid];
        } else {
            $admincp = SpiderPost::commit($urlId, $spost);
            $result = ['id' => $urlId, 'indexid'=>$admincp->RESULT['id']];
        }

        if ($_POST['commit:callData']) {
            $rule = SpiderRule::get($project['rid']);
            foreach ($_POST['commit:callData'] as $key => $value) {
                if ($value['URLS']) {
                    $result[$key . '_data'] = SpiderData::sub_crawl(
                        $value,
                        $rule,
                        $result['indexid']
                    );
                }
            }
        }

        Spider::$callback['save'] && Spider::$callback['commit'] = Spider::$callback['save'];
        if (Spider::$callback['commit'] && is_callable(Spider::$callback['commit'])) {
            $ret = call_user_func_array(Spider::$callback['commit'], [$result, $_POST]);
            if ($ret['callback'] || $ret['return']) {
                return $ret;
            }
        }
        Spider::$callback['STATUS'] = null;

        return $result;
    }

    public static function setCallback($urlId = 0, $_id = 0, &$admincp = null)
    {
        if ($admincp) {
            /**
             * 主表 回调 更新关联ID
             */
            $admincp->callback['saved']['primary'] = function (&$data) use ($urlId) {
                Spider::$callback['result'] = [
                    'id' => $data['id'],
                ];
                SpiderUrl::update_indexid($urlId, $data['id']);
            };
            /**
             * 数据表 回调 成功发布
             */
            $admincp->callback['saved']['data'] = function (&$data) use ($urlId) {
                SpiderUrl::update_publish($urlId);
            };
        } else {
            SpiderUrl::update_indexid($urlId, $_id);
            SpiderUrl::update_publish($urlId);
        }
    }

    public static function callback($obj, $id, $type = null)
    {
        if ($type === null || $type == 'primary') {
            if ($obj->SPIDER['primary']) {
                call_user_func_array($obj->SPIDER['primary'], [$id]);
            }
        }
        if ($type === null || $type == 'data') {
            if ($obj->SPIDER['data']) {
                call_user_func($obj->SPIDER['data']);
            }
        }
    }

    public static function getIndexId()
    {
        $indexid = Spider::$indexid ?: (int)Request::param('indexid');
        return Spider::$callback['url:indexid'] ?: (int)$indexid;
    }

    public static function getAppDataIds($indexid, $APPDATA)
    {
        if (empty($indexid)) {
            return;
        }

        if ($APPDATA['app'] == 'article') {
            $_POST['article_id'] = $indexid;
            $_POST['dataId'] = ArticleDataModel::getValue($indexid, 'id');
        } else {
            $model = Content::model($APPDATA);
            $pk = $model::getPrimaryKey();
            $_POST[$pk] = $indexid;
            if ($unionKey = ContentDataModel::$unionKey) {
                $where = [$unionKey => $indexid];
                $_POST[$unionKey] = $indexid;
                $key = AppsTable::DATA_PRIMARY_KEY;
                $_POST[$key] = ContentDataModel::where($where)->field($key)->value();
            }
        }
    }
}
