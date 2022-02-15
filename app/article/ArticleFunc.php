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
class ArticleFunc extends AppsFuncCommon implements AppsFuncBase
{

    public static function value($vars)
    {
    }
    public static function category($vars)
    {
        return self::node($vars);
    }

    public static function lists($vars)
    {
        $whereNot  = array();
        $resource  = array();
        $model     = ArticleModel::field('id');
        $status    = isset($vars['status']) ? $vars['status'] : 1;
        $where     = [['status', $status]];

        $vars['call'] == 'user'  && $where[]    = ['postype', 0];
        $vars['call'] == 'admin' && $where[]    = ['postype', 1];
        isset($vars['userid'])   && $where[]    = ['userid', $vars['userid']];
        isset($vars['weight'])   && $where[]    = ['weight', $vars['weight']];
        isset($vars['ucid'])     && $where[]    = ['ucid', $vars['ucid']];
        isset($vars['pic'])      && $where[]    = ['haspic', '1'];
        isset($vars['nopic'])    && $where[]    = ['haspic', '0'];
        isset($vars['startdate']) && $where[] = array('pubdate', '>=', str2time($vars['startdate'] . (strpos($vars['startdate'], ' ') !== false ? '' : " 00:00:00")));
        isset($vars['enddate'])   && $where[] = array('pubdate', '<=', str2time($vars['enddate'] . (strpos($vars['enddate'], ' ') !== false ? '' : " 00:00:00")));

        self::init($vars, $model, $where, $whereNot);
        self::setApp(Article::APPID, Article::APP);
        self::nodes('cid');
        self::tags();
        self::props();
        self::keywords();
        self::orderby([
            'hot'   => 'hits',
            'today' => 'hits_today',
            'yday'  => 'hits_yday',
            'week'  => 'hits_week',
            'month' => 'hits_month'
        ]);
        self::where();
        return self::getResource(__METHOD__, [__CLASS__, 'resource']);
    }
    public static function resource($vars, $idsArray = null)
    {
        $vars['ids'] && $idsArray = $vars['ids'];

        $resource = ArticleModel::field('*')
            ->where($idsArray)
            ->orderBy('id', $idsArray)
            ->select();

        $resource = self::many($vars, $resource);
        return $resource;
    }
    public static function data($vars)
    {
        $where  = array();
        $article_id = $vars['aid']?:$vars['article_id']; 
        $article_id or Script::warning('iCMS&#x3a;' . Article::APP . '&#x3a;data 标签出错! 缺少"aid"属性或"aid"值为空.');

        $model = ArticleDataModel::sharding($article_id);
        $article_id  && $where['article_id'] = $article_id;
        self::orderby();
        self::where();
        $where && $model->where($where);

        $hash = md5($model->getSql());
        $paging = self::paging($hash, __METHOD__);
        list($total, $offset, $pageSize) = $paging;

        $cacheName = sprintf('%s/%s/%s/%d_%d', iPHP_DEVICE, Article::APP, $hash, $offset, $pageSize);
        $resource  = self::getCache($cacheName);
        if ($vars['loop']) {
            if (empty($resource)) {
                $idsArray = self::getIds($paging);
                if ($idsArray) {
                    $resource = $model->field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
                    // $resource = self::many($vars, $resource);
                }
                $cacheTime = isset($vars['time']) ? (int) $vars['time'] : -1;
                $vars['cache'] && Cache::set($cacheName, $resource, $cacheTime);
            }
        } else {
            $resource = $model->field('*')->get();
        }
        return $resource;
    }
    public static function prev($vars)
    {
        $vars['order'] = 'p';
        return self::next($vars);
    }
    public static function next($vars)
    {
        // if($vars['param']){
        //     $vars+= $vars['param'];
        //     unset($vars['param']);
        // }
        $where = [];
        empty($vars['order']) && $vars['order'] = 'n';
        isset($vars['cid']) && $where[] = ['cid', $vars['cid']];
        if ($vars['order'] == 'p') {
            $where[] = ['id', '<', $vars['id']];
            $field = 'max(id)'; //INNODB
            // $sql .= " AND `id` < '{$vars['id']}' ORDER BY id DESC LIMIT 1";//MyISAM
        } elseif ($vars['order'] == 'n') {
            $where[] = ['id', '>', $vars['id']];
            $field = 'min(id)'; //INNODB
            // $sql .= " AND `id` > '{$vars['id']}' ORDER BY id ASC LIMIT 1";//MyISAM
        }

        $model = ArticleModel::field($field)->where($where);
        $hash = md5($model->getSql());
        if ($vars['cache']) {
            $cacheName = sprintf("s%/s%/s%", iPHP_DEVICE, Article::APP, $hash);
            $resource = Cache::get($cacheName);
            if (is_array($resource)) {
                return $resource;
            }
        }
        $id = $model->value();
        $id && $article = ArticleModel::field('*')->get($id);

        if ($article) {
            $node = NodeCache::getId($article['cid']);
            $resource = array(
                'id'    => $article['id'],
                'title' => $article['title'],
                'pic'   => FilesPic::getArray($article['pic']),
                'url'   => Route::get(Article::APP, array((array)$article, $node))->href,
            );
        }
        $cacheTime = isset($vars['time']) ? (int) $vars['time'] : -1;
        $vars['cache'] && Cache::set($cacheName, $resource, $cacheTime);

        return $resource;
    }
    public static function many($vars, $resource = [])
    {
        if ($resource) {
            $idArray = array_column($resource, 'id');
            if ($vars['data'] || $vars['pics']) {
                $idArray && $DATAS = (array) ArticleApp::data($idArray);
            }
            if ($vars['meta'] && $idArray) {
                $metaData = (array) AppsMeta::data(Article::APP, $idArray);
            }

            if ($vars['tags']) {
                $tagArray = array_column($resource, 'tags', 'id');
                $tagArray && $tagsData = (array) TagApp::many($tagArray);
                unset($tagArray);
                $vars['tag'] = false; //articleApp::values 里不调用
            }

            foreach ($resource as $key => &$value) {
                try {
                    articleApp::values($value, $vars);
                } catch (\FalseEx $fex) {
                    $value = [];
                    continue;
                }
                if (($vars['data'] || $vars['pics']) && $DATAS) {
                    $value['data']  = (array) $DATAS[$value['id']];
                    if ($vars['pics']) {
                        $value['pics'] = FilesPic::findImgUrl($value['data']['body']);
                        if (!$value['data']) {
                            unset($value['data']);
                        }
                    }
                }

                if ($vars['tags'] && $tagsData) {
                    $value += (array) $tagsData[$value['id']];
                }
                if ($vars['meta'] && $metaData) {
                    $value += (array) $metaData[$value['id']];
                }

                if ($vars['page']) {
                    $value['page'] = $GLOBALS['page'] ? $GLOBALS['page'] : 1;
                    $value['total'] = $vars['total'];
                }
                // $resource[$key] = $value;
            }
        }
        return $resource;
    }
}
