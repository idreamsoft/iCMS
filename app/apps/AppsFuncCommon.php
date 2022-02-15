<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class AppsFuncCommon
{
    protected static $app   = null;
    protected static $_appid = 0;

    protected static $model = null;
    protected static $vars = [];
    protected static $where = [];
    protected static $whereNot = [];
    protected static $join = [];

    public static $PAGES = [];
    public static $pageData = [];

    public static function value($vars)
    {
    }
    public static function nodeSelect($vars)
    {
        $class =  get_called_class();
        if (strpos($class, 'Func') !== false) {
            $app = substr($class, 0, -4);
            $vars['appid'] = $app::APPID;
        }
        return nodeFunc::select($vars);
    }
    public static function node($vars)
    {
        $class =  get_called_class();
        if (strpos($class, 'Func') !== false) {
            $vars['appid'] = substr($class, 0, -4);
        }
        return nodeFunc::lists($vars);
    }
    public static function tag($vars)
    {
        $class =  get_called_class();
        if (strpos($class, 'Func') !== false) {
            $vars['appid'] = substr($class, 0, -4);
        }
        return tagFunc::lists($vars);
    }
    public static function lists($vars)
    {
    }
    public static function getPageData()
    {
        return self::$pageData;
    }
    public static function getPages()
    {
        return self::$PAGES;
    }
    protected static function setApp($appid, $app)
    {
        self::$_appid = $appid;
        self::$app = $app;
    }
    protected static function init($vars, $model = null, $where = null, $whereNot = null)
    {
        self::$vars = &$vars;
        self::$model = &$model;
        self::$where = &$where;
        self::$whereNot = &$whereNot;
        self::$join = [];
        self::$app = null;
        self::$_appid = 0;
    }
    protected static function getIds($param, $field = 'id', $optimize = false)
    {
        list($total, $offset, $pageSize) = $param;
        if ($optimize) {
            // if ($offset > 1000 && $total > 2000 && $offset >= $total / 2) {
            if ($offset) {
                if ($offset >= $total / 2) {
                    $_offset = $total - $offset - $pageSize;
                    $_offset < 0 && $_offset = 0;
                    $offset = $_offset;
                }
            }
            $by = self::$model->getBy();
            isset($_offset) && self::$model->orderBy($field, $by == 'DESC' ? 'ASC' : 'DESC');
        }
        $idArray = self::$model->limit($offset, $pageSize)->pluck($field);
        // DB::getQueryLog(1);
        isset($_offset) && $idArray = array_reverse($idArray, true);
        return $idArray;
    }
    protected static function offset(&$offset, $total, $pageSize)
    {
        if ($offset) {
            // if ($offset > 1000 && $total > 2000 && $offset >= $total / 2) {
            if ($offset >= $total / 2) {
                $_offset = $total - $offset - $pageSize;
                $_offset < 0 && $_offset = 0;
                $offset = $_offset;
            }
        }
        return $_offset;
    }

    protected static function paging($hash, $METHOD, &$Paging = null)
    {
        $pageTotal = 0;
        $pageSize  = isset(self::$vars['row']) ? (int) self::$vars['row'] : 10;
        $offset = (int) self::$vars['offset'];
        $total = (int) self::$vars['total'];
        if (self::$vars['page']) {
            $totalType = self::$vars['total_cache'] ? 'G' : null;
            isset(self::$vars['pageNum']) && $total = (int) self::$vars['pageNum'] * $pageSize;

            if (!isset(self::$vars['total']) && !isset(self::$vars['pageNum'])) {
                $total = Paging::totalCache(
                    [self::$model, 'count'],
                    $hash,
                    $totalType,
                    Config::get('cache.page_total')
                );
            }
            $config = array(
                'totalType' => $totalType,
                'count'     => $total,
                'size'      => $pageSize,
                'ajax'      => self::$vars['page_ajax'] ?: null,
            );
            if (self::$vars['display'] == 'iframe' || self::$vars['page_ajax']) {
                $config['name'] = 'pn';
                $config['nowindex']  = (int)$GLOBALS['pn'];
            }
            self::$PAGES = Paging::make($config);
            $pageTotal = self::$PAGES->total;
            $offset = self::$PAGES->offset;
        }
        $METHOD = strtolower(str_replace(['Func', '::'], ['', '_'], $METHOD));
        View::assign($METHOD . "_total", $total);
        self::$pageData = [$total, $offset, $pageSize, $pageTotal, self::$PAGES];
        return self::$pageData;
    }
    protected static function nodes($field = 'cid')
    {
        $hidden = NodeCache::get('hidden');
        $hidden && $whereNot[] = [$field, 'NOT IN', $hidden];

        $not = self::$vars[$field . '!'];
        $not or $not = self::$vars['node_id!'];
        if ($not) {
            $idArray = explode(',', $not);
            self::$vars['sub'] && $idArray = array_merge($idArray, NodeCache::getIds($idArray));
            self::$whereNot[] = [$field, 'NOT IN', $idArray];
        }

        $nodeId = self::$vars[$field];
        $nodeId or $nodeId = self::$vars['node_id'];

        if ($nodeId) {
            $idArray = explode(',', $nodeId);
            self::$vars['sub'] && $idArray = array_merge($idArray, NodeCache::getIds($idArray, true, $hidden));
            self::$where[]  = [$field, $idArray];
        }
        $nodeIds = self::$vars[$field . 's'];
        $nodeIds or $nodeIds = self::$vars['node_ids'];

        if ($nodeIds) {
            $idArray = explode(',', $nodeIds);
            self::$vars['sub'] && $idArray = array_merge($idArray, NodeCache::getIds($idArray, true, $hidden));
            $idArray && self::$join[] = AppsMap::join('node', $idArray, self::$_appid, $field);
        }
    }
    protected static function tags($fields = null)
    {
        $TagMapAs = 0;
        if (isset(self::$vars['tag[]']) && is_array(self::$vars['tag[]'])) {
            is_array($fields) && $fArray = Former::fields($fields);
            $tagArray = self::$vars['tag[]'];
            $flag = false;
            foreach ($tagArray as $field => $tid) {
                if ($fields) {
                    $flag = ($fArray[$field]['type'] == 'tag');
                } else {
                    $flag = true;
                }
                $flag && self::$join[] = AppsMap::join('tag', $tid, self::$_appid, $field, $TagMapAs);
                $TagMapAs++;
            }
        }
        if (isset(self::$vars['tag']) && is_array(self::$vars['tag'])) {
            $tids  = self::$vars['tag']['id'];
            $field = self::$vars['tag']['field'];
            if (is_array(self::$vars['tag'][0])) {
                $tids  = array_column(self::$vars['tag'], 'id');
                $field = self::$vars['tag'][0]['field'];
            }
            self::$join[] = AppsMap::join('tag', $tids, self::$_appid, $field, $TagMapAs);
            $TagMapAs++;
        }

        $field = (is_array($fields) ? 'tags' : $fields) ?: 'tags';

        if (isset(self::$vars['tids'])) {
            self::$join[] = AppsMap::join('tag', self::$vars['tids'], self::$_appid, $field, $TagMapAs);
            $TagMapAs++;
        }
        if (isset(self::$vars['tid'])) {
            self::$join[] = AppsMap::join('tag', self::$vars['tid'], self::$_appid, $field, $TagMapAs);
            $TagMapAs++;
        }
    }
    protected static function props($fields = null)
    {
        $PropMapAs = 0;
        if (isset(self::$vars['prop[]']) && is_array(self::$vars['prop[]'])) {
            $propArray = self::$vars['prop[]'];
            $fields && $fArray = Former::fields($fields);
            foreach ($propArray as $field => $pid) {
                $FA = $fArray[$field];
                if ($FA && in_array($FA['type'], array('radio_prop', 'multi_prop', 'prop'))) {
                    if ($FA['multiple']) {
                        self::$join[] = AppsMap::join('prop', $pid, self::$_appid, $field, $PropMapAs);
                    } else {
                        self::$where[]  = [$field, $pid];
                    }
                    $PropMapAs++;
                }
            }
        }
        if (isset(self::$vars['pid']) && !isset(self::$vars['pids'])) {
            self::$where[]  = ['pid', self::$vars['pid']];
        }
        if (isset(self::$vars['pid!'])) {
            self::$whereNot[] = ['pid', 'NOT IN', self::$vars['pid!']];
        }
        if (isset(self::$vars['pids']) && !isset(self::$vars['pid'])) {
            self::$join[] = AppsMap::join('prop', self::$vars['pids'], self::$_appid, 'pid', $PropMapAs);
            $PropMapAs++;
        }
    }
    protected static function keywords($concat = 'title,keywords,description')
    {
        if ($keyword = self::$vars['keyword']) {
            $kwExp = 'LIKE';
            if (strpos($keyword, ',') === false) {
                $keywords = str_replace(array('%', '_'), array('\%', '\_'), $keyword);
                $keywords = "%{$keywords}%";
            } else {
                $pieces   = explode(',', $keyword);
                $keywords = implode('|', array_filter($pieces));
                $kwExp = 'REGEXP';
            }
            self::$where[] = ['CONCAT(' . $concat . ')', $kwExp, $keywords];
        }
    }
    protected static function orderby($mapArray = [], $default = 'id')
    {
        $by = strtoupper(self::$vars['by']) == "ASC" ? "ASC" : "DESC";
        $field = $default;
        if (self::$vars['orderby']) {
            list($orderby, $_by) = explode(' ', self::$vars['orderby']);
            $_by && $by = $_by;
            $field = $mapArray[$orderby] ?: $default;
        }
        self::$model->field($field)->orderBy($field, $by);
    }
    protected static function where()
    {
        if (isset(self::$vars['id'])) {
            $expArray = explode(',', '=,<,>,<>,!=,<=,>=,like,not like');
            $id = self::$vars['id'];
            if (is_array($id) && in_array($id[0], $expArray)) {
                self::$where[] = ['id', $id[0], $id[1]];
            } else {
                self::$where[] = ['id', $id];
            }
        }
        if (isset(self::$vars['id!'])) {
            if (is_array(self::$vars['id!'])) {
                self::$whereNot[] = ['id', 'not in', self::$vars['id!']];
            } else {
                self::$whereNot[] = ['id', '<>', self::$vars['id!']];
            }
        }
        if (self::$vars['where']) {
            if (is_array(self::$vars['where'])) {
                self::$where = array_merge(self::$where, self::$vars['where']);
            } else {
                //存在SQL注入
                // $where[] = DB::raw(trim(self::$vars['where']));
            }
        }

        self::$whereNot && self::$model->where(self::$whereNot);
        self::$where && self::$model->where(self::$where);
        self::$join && self::$model->alias(self::$app)->join(self::$join);
        count(self::$join) > 0  && $distinct = 'id';
        self::$vars['distinct'] && $distinct = self::$vars['distinct'];
        $distinct && self::$model->distinct($distinct);
    }
    protected static function getCache($cacheName)
    {
        if (self::$vars['cache']) {
            isset(self::$vars['cache_name']) && $cacheName = self::$vars['cache_name'];
            $resource = Cache::get($cacheName);
            if (is_array($resource)) {
                return $resource;
            }
        }
    }
    protected static function getResource($method, $call = null, $data = null)
    {
        $sql = self::$model->getSql();
        // var_dump(self::$model);
        if (is_array(iDebug::$DATA['Func.sql'])) {
            iDebug::$DATA['Func.sql'][] = $sql;
        }

        $hash = md5($sql);
        $paging = self::paging($hash, $method);
        list($total, $offset, $pageSize) = $paging;

        $cacheName = sprintf(
            '%s/%s/%s/%d_%d',
            iPHP_DEVICE,
            self::$app,
            $hash,
            $offset,
            $pageSize
        );
        $resource  = self::getCache($cacheName);
        if (empty($resource)) {
            if ($call === true) {
                $resource = self::$model->select();
            } elseif ($call) {
                $idsArray = self::getIds($paging, 'id', self::$vars['optimize']);
                if ($idsArray) {
                    $METHOD = strtolower(str_replace(['Func', '::'], ['', '_'], $method));
                    View::assign($METHOD . "_ids", $idsArray);
                    $resource = call_user_func_array($call, [self::$vars, $idsArray, $data, $paging]);
                }
            }
            $cacheTime = isset(self::$vars['time']) ? (int) self::$vars['time'] : -1;
            self::$vars['cache'] && Cache::set($cacheName, $resource, $cacheTime);
        }
        // DB::getQueryLog(1);
        self::$vars['keys'] && pluck($resource, self::$vars['keys'], self::$vars['is_remove_keys']);

        return $resource;
    }
}
