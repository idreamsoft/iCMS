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
class ArticleFuncSearch  extends AppsFuncCommon
{
    public static function search($vars)
    {
        $config = Config::get('article.sphinx');
        if (empty($config)) {
            return array();
        }

        $resource = array();
        $page = (int) Request::get('page');
        $pageSize = isset($vars['row']) ? (int) $vars['row'] : 10;
        $start = ($page && isset($vars['page'])) ? ($page - 1) * $pageSize : 0;

        self::init($vars);
        self::setApp(Article::APPID, Article::APP);

        $hash = md5(json_encode($vars));

        $cacheName = sprintf(
            '%s/%s/%s/%d_%d',
            iPHP_DEVICE,
            self::$app,
            $hash,
            $start,
            $pageSize
        );
        $resource  = self::getCache($cacheName);
        if ($vars['cache']) {
            if (is_array($resource)) {
                return $resource;
            }
        }
        $SPH = Vendor::run('SphinxClient', $config['host']);
        $SPH->SetArrayResult(true);
        if (isset($vars['weights'])) {
            //weights='title:100,tags:80,keywords:60,name:50'
            $wa = explode(',', $vars['weights']);
            foreach ($wa as $wk => $wv) {
                $waa = explode(':', $wv);
                $FieldWeights[$waa[0]] = $waa[1];
            }
            $FieldWeights or $FieldWeights = array("title" => 100, "tags" => 80, "name" => 60, "keywords" => 40);
            $SPH->SetFieldWeights($FieldWeights);
        }

        $SPH->SetMatchMode(SPH_MATCH_EXTENDED2);
        if ($vars['mode']) {
            $vars['mode'] == "SPH_MATCH_BOOLEAN" && $SPH->SetMatchMode(SPH_MATCH_BOOLEAN);
            $vars['mode'] == "SPH_MATCH_ANY" && $SPH->SetMatchMode(SPH_MATCH_ANY);
            $vars['mode'] == "SPH_MATCH_PHRASE" && $SPH->SetMatchMode(SPH_MATCH_PHRASE);
            $vars['mode'] == "SPH_MATCH_ALL" && $SPH->SetMatchMode(SPH_MATCH_ALL);
            $vars['mode'] == "SPH_MATCH_EXTENDED" && $SPH->SetMatchMode(SPH_MATCH_EXTENDED);
            $vars['mode'] == "SPH_MATCH_EXTENDED2" && $SPH->SetMatchMode(SPH_MATCH_EXTENDED2);
        }

        isset($vars['userid']) && $SPH->SetFilter('userid', array($vars['userid']));
        isset($vars['postype']) && $SPH->SetFilter('postype', array($vars['postype']));

        if (isset($vars['cid'])) {
            $cids = $vars['sub'] ? NodeCache::getIds($vars['cid']) : (array) $vars['cid'];
            $cids or $cids = (array) $vars['cid'];
            $cids = array_map("intval", $cids);
            $SPH->SetFilter('cid', $cids);
        }
        if (isset($vars['cid!'])) {
            $cids = $vars['sub'] ? NodeCache::getIds($vars['cid!']) : (array) $vars['cid!'];
            $cids or $cids = (array) $vars['cid!'];
            $cids = array_map("intval", $cids);
            $SPH->SetFilter('cid', $cids, true);
        }
        if (isset($vars['startdate'])) {
            $startime = strtotime($vars['startdate']);
            $enddate = empty($vars['enddate']) ? time() : strtotime($vars['enddate']);
            $SPH->SetFilterRange('pubdate', $startime, $enddate);
        }
        $SPH->SetLimits($start, $pageSize, 10000);

        $orderby = '@weight DESC, @id DESC';

        $vars['orderby'] && $orderby = $vars['orderby'];

        $vars['pic'] && $SPH->SetFilter('haspic', array(1));
        $vars['id!'] && $SPH->SetFilter('@id', array($vars['id!']), true);

        $SPH->setSortMode(SPH_SORT_EXTENDED, $orderby);

        is_array($vars['q']) && $vars['q'] = implode('|', $vars['q']);
        $query = str_replace(',', '|', $vars['q']);
        $query = str_replace('/', '|', $query);
        $vars['acc'] && $query = '"' . $vars['q'] . '"';
        $vars['@'] && $query = '@(' . $vars['@'] . ') ' . $query;

        $res = $SPH->Query($query, $config['index']);

        if ($res === false) {
            $msg = array();
            $SPH->_error    && $msg[] = '[ERROR]' . $SPH->GetLastError();
            $SPH->_warning  && $msg[] = '[WARNING]' . $SPH->GetLastWarning();
            $SPH->_connerror && $msg[] = '[connerror]' . $SPH->connerror;
            Script::warning(implode('<hr />', $msg));
            return array();
        }

        $idsArray = array();
        if (is_array($res["matches"])) {
            $idsArray = array_column($res["matches"], 'id');
        }

        $paging = self::paging($hash, __METHOD__);
        list($total, $offset, $pageSize) = $paging;

        if (empty($resource)) {
            $resource = ArticleFunc::resource($vars, $idsArray);
            $cacheTime = isset(self::$vars['time']) ? (int) self::$vars['time'] : -1;
            self::$vars['cache'] && Cache::set($cacheName, $resource, $cacheTime);
        }
        self::$vars['keys'] && pluck($resource, self::$vars['keys'], self::$vars['is_remove_keys']);

        return $resource;
    }
}
