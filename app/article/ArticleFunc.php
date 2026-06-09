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
class ArticleFunc extends AppsFuncCommon implements AppsFuncInterface
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
        $resource  = array();
        $whereNot  = array();
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

        self::inited($vars, $model, $where, $whereNot);
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
        return self::getResource(__METHOD__);
    }
    public static function resource($idsArray = null)
    {
        $vars = self::$vars;
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
        $article_id = $vars['id'] ?: $vars['aid'] ?: $vars['article_id'];
        $article_id or self::msg(['func_param', ['iCMS:article:data', 'aid']]);

        $model = ArticleDataModel::sharding($article_id);
        $article_id  && $where['article_id'] = $article_id;
        self::inited($vars, $model, $where);
        self::orderby();
        self::where();
        $where && $model = $model->where($where);

        $hash = md5($model->getSql());
        self::paging($hash, __METHOD__);

        $resource  = self::getCache(self::$cacheName);
        if ($vars['loop']) {
            if (empty($resource)) {
                $idsArray = self::getIds();
                if ($idsArray) {
                    $resource = $model->field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
                    // $resource = self::many($vars, $resource);
                }
                $cacheTime = isset($vars['time']) ? (int) $vars['time'] : -1;
                $vars['cache'] && Cache::set(self::$cacheName, $resource, $cacheTime);
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
        $where = [['status', '1']];
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
            $cacheName = sprintf("%s/%s/%s", iPHP_DEVICE, Article::APP, $hash);
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
                'description' => $article['description'],
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
            if ($vars['data'] || $vars['body_pics']) {
                $idArray && $DATAS = (array) ArticleApp::data($idArray);
            }
            $metaData = [];
            if ($vars['meta'] && $idArray) {
                AppsMeta::data(Article::APP, $idArray,$metaData);
            }
            $tagsData = [];
            if ($vars['tag']) {
                $tagArray = array_column($resource, 'tags', 'id');
                $tagArray && $tagsData = (array) TagApp::many($tagArray);
                unset($tagArray);
                $vars['tag'] = false; //articleApp::values 里不调用
            }

            foreach ($resource as $key => &$value) {
                try {
                    ArticleApp::values($value, $vars);
                } catch (\FalseEx $fex) {
                    $value = [];
                    unset($resource[$key]);
                    continue;
                }

                if (($vars['data'] || $vars['body_pics']) && $DATAS) {
                    $value['data']  = (array) $DATAS[$value['id']];
                    if ($vars['body_pics']) {
                        $value['body_pics'] = FilesPic::findImgUrl($value['data']['body']);
                        if (!$value['data']) {
                            unset($value['data']);
                        }
                    }
                }

                $tagsData && $value += (array) $tagsData[$value['id']];
                $metaData && $value += (array) $metaData[$value['id']];
                
                if ($vars['page']) {
                    $value['page'] = isset($GLOBALS['page']) ? $GLOBALS['page'] : 1;
                    $value['total'] = $vars['total'];
                }
                // $resource[$key] = $value;
            }
        }
        return $resource;
    }
}
