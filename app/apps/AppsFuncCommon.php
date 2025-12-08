<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
abstract class AppsFuncCommon
{
    protected static $app = null;
    protected static $appid = null;
    protected static $class = null;

    protected static $model = null;
    protected static $vars = [];
    protected static $where = [];
    protected static $whereNot = [];
    protected static $join = [];
    protected static $debug = [];

    /**
     * @var Pages
     */
    public static $PAGES = [];
    /**
     * total, current, pageSize, pageTotal, offset
     *
     * @var array
     */
    public static $pageData = [];
    public static $cacheName = null;

    /**
     * View.php:280 自动执行
     *
     * @return void
     */
    public static function init()
    {
        self::$app = self::$appid = null;
        $class = static::$class;
        if (empty($class)) {
            $called = get_called_class();
            $class = substr($called, 0, -4);
        }
        /**
         * 在子类里设置 $app,$appid 参考 CommentReplyFunc
         */
        static::$app && self::$app = static::$app;
        static::$appid && self::$appid = static::$appid;

        if (class_exists($class)) {
            $define_app = $class . '::APP';
            if (empty(self::$app) && defined($define_app)) {
                self::$app = constant($define_app);
            }
            $define_appid = $class . '::APPID';
            if (empty(self::$appid) && defined($define_appid)) {
                self::$appid = constant($define_appid);
            }
        }
    }

    public static function msg($msg)
    {
        if (is_array($msg)) {
            echo Lang::get('msg:' . $msg[0], $msg[1]) ;
        } else {
            echo $msg;
        }
        echo '<hr />';
    }

    public static function value($vars)
    {
    }

    public static function nodeSelect($vars)
    {
        $vars['appid'] = self::$appid;

        return nodeFunc::select($vars);
    }

    public static function node($vars)
    {
        $vars['appid'] = self::$appid;
        return nodeFunc::lists($vars);
    }

    public static function tag($vars)
    {
        $vars['appid'] = self::$appid;
        return tagFunc::lists($vars);
    }
    public static function comment($vars)
    {
        $vars['appid'] = self::$appid;
        return commentFunc::lists($vars);
    }

    public static function lists($vars)
    {
    }

    public static function config($vars)
    {
        $config = Config::get(self::$app);
        if (isset($vars['key']) && is_array($config)) {
            $config = $config[$vars['key']];
        }
        $vars['keys'] && $config = array_filter_keys($config, $vars['keys'], isset($vars['is_remove_keys']) ? false : true);
        return $config;
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
        self::$appid = $appid;
        self::$app = $app;
    }

    protected static function inited(&$vars, &$model = null, &$where = null, &$whereNot = null)
    {
        self::$vars = &$vars;
        self::$model = &$model;
        self::$where = &$where;
        self::$whereNot = &$whereNot;
        self::$join = [];
        if ($vars['get']) {
            $req = Request::get();
            unset($req['id']);
            $gets = explode(',', $vars['get']);
            foreach ($gets as $g) {
                // isset($req[$g]) && $where[] = [$g, $req[$g]];
                isset($req[$g]) && self::$vars[$g] = $req[$g];
            }
        }
    }

    protected static function getIds($field = 'id', $optimize = false)
    {
        $total = self::$pageData['total'];
        $pageSize = self::$pageData['pageSize'];
        $offset = self::$pageData['offset'];

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

    /**
     * Undocumented function
     *
     * @param string $hash
     * @param string $METHOD
     * @param string $Paging
     * @return $total, $current, $pageSize, $pageTotal,$offset
     */
    protected static function paging($hash, $METHOD, &$Paging = null)
    {
        $pageTotal = 0;
        $pageSize = isset(self::$vars['row']) ? (int) self::$vars['row'] : 10;
        $offset = (int) self::$vars['offset'];
        $total = (int) self::$vars['total'];
        $current = 0;

        if (self::$vars['page']) {
            $totalType = self::$vars['total_cache'] ? 'G' : null;
            isset(self::$vars['page_num']) && $total = (int) self::$vars['page_num'] * $pageSize;

            if (!isset(self::$vars['total']) && !isset(self::$vars['page_num'])) {
                $total = Paging::totalCache(
                    [self::$model, 'count'],
                    $hash,
                    $totalType,
                    Config::get('cache.page_total')
                );
            }
            $config = [
                'page_style' => self::$vars['page_style'],
                'totalType'  => $totalType,
                'count'      => $total,
                'size'       => $pageSize,
                'ajax'       => self::$vars['page_ajax'] ?: null,
            ];
            if (self::$vars['display'] == 'iframe' || self::$vars['page_ajax']) {
                $config['name'] = 'pn';
                $config['nowindex'] = (int)Request::sparam('pn', 0);
            }
            if (isset(self::$vars['page_index'])) {
                $config['nowindex'] = (int)self::$vars['page_index'];
            }
            self::$PAGES = Paging::make($config);
            View::generic([
                'PAGES' => self::$PAGES,
                'PAGE'  => self::$PAGES->vars()
            ]);

            $pageTotal = (int)self::$PAGES->total;
            $offset = (int)self::$PAGES->offset;
            $current = (int)self::$PAGES->nowindex;
        }
        $METHOD = strtolower(str_replace(['Func', '::'], ['', '_'], $METHOD));
        View::assign($METHOD . '_total', $total);
        self::$pageData = compact(
            'total', //总条数
            'current', //当前页码
            'pageSize', //每页条数
            'pageTotal', //总页数
            'offset'//当前limit数 当前页码-1*每页条数
        );

        self::$cacheName = sprintf(
            '%s/%s/%s/%d_%d',
            iPHP_DEVICE,
            self::$app,
            $hash,
            $current,
            $pageSize
        );
        return [(int)$total, (int)$current, (int)$pageSize, (int)$pageTotal, (int)$offset];
    }

    protected static function nodes($field = 'cid')
    {
        $hidden = NodeCache::get('hidden');
        $hidden && $whereNot[] = [$field, 'NOT IN', $hidden];
        //cid!='1'
        //node_id!='1'
        //排除节点
        $not = self::$vars[$field . '!'];
        $not or $not = self::$vars['node_id!'];
        if ($not) {
            $idArray = explode(',', $not);
            self::$vars['sub'] && $idArray = array_merge($idArray, NodeCache::getIds($idArray));
            self::$whereNot[] = [$field, 'NOT IN', $idArray];
        }
        //dir='ooxx'
        //dir='xx,oo'
        if (isset(self::$vars['dir'])) {
            $dirArray = explode(',', self::$vars['dir']);
            $idArray = [];
            foreach ($dirArray as $key => $dir) {
                $cid = NodeCache::get('dir2nid', $dir);
                $cid && $idArray[] = $cid;
            }
            $idArray = array_filter($idArray);
            self::$vars['sub'] && $idArray = array_merge($idArray, NodeCache::getIds($idArray, true, $hidden));
            self::$where[] = [$field, $idArray];
        }
        //cid='1'
        //node_id='1'
        //选择节点
        $nodeId = self::$vars[$field];
        $nodeId or $nodeId = self::$vars['node_id'];

        if ($nodeId) {
            $idArray = explode(',', $nodeId);
            self::$vars['sub'] && $idArray = array_merge($idArray, NodeCache::getIds($idArray, true, $hidden));
            self::$where[] = [$field, $idArray];
        }
        //cids='1'
        //node_ids='1'
        //选择节点 使用关系表
        $nodeIds = self::$vars[$field . 's'];
        $nodeIds or $nodeIds = self::$vars['node_ids'];

        if ($nodeIds) {
            $idArray = explode(',', $nodeIds);
            self::$vars['sub'] && $idArray = array_merge($idArray, NodeCache::getIds($idArray, true, $hidden));
            // self::$where[] = [$field, 'FIND_IN_SET', $idArray];
            // self::$where[] = [$field, 'FIND_IN_SET', '1'];
            // self::$where[] = [$field, 'between', [1, 2]];
            // var_dump(self::$model->getTableName());
            $idArray && self::$join[] = AppsMap::join('node', $idArray, self::$appid, $field);
        }
    }

    protected static function tags($fields = null)
    {
        $field = (is_array($fields) ? 'tags' : $fields) ?: 'tags';
        // tags="name"
        if (self::$vars[$field]) {
            $tags = Tag::name(self::$vars[$field]);
            self::multiValue($field, $tags);
        }

        $TagMapAs = 0;
        // tagArr="name"
        if (isset(self::$vars['tagArr']) && is_array(self::$vars['tagArr'])) {
            is_array($fields) && $fArray = Former::fields($fields);
            $tagArray = self::$vars['tagArr'];
            $flag = false;
            foreach ($tagArray as $field => $tid) {
                if ($fields) {
                    $flag = ($fArray[$field]['type'] == 'tag');
                } else {
                    $flag = true;
                }
                $flag && self::$join[] = AppsMap::join('tag', $tid, self::$appid, $field, $TagMapAs);
                $TagMapAs++;
            }
        }
        // tag="$tag" 标签数据，使用FIND_IN_SET
        if (isset(self::$vars['tag']) && is_array(self::$vars['tag'])) {
            $tids = self::$vars['tag']['id'];
            $field = self::$vars['tag']['field'];
            if (is_array(self::$vars['tag'][0])) {
                $tids = array_column(self::$vars['tag'], 'id');
                $field = self::$vars['tag'][0]['field'];
            }
            self::$where[] = [$field, 'FIND_IN_SET', $tags];
            // self::$join[] = AppsMap::join('tag', $tids, self::$appid, $field, $TagMapAs);
            $TagMapAs++;
        }
        // tids="1,2,3" 使用关系表
        if (isset(self::$vars['tids']) && !isset(self::$vars['tid'])) {
            self::$join[] = AppsMap::join('tag', self::$vars['tids'], self::$appid, $field, $TagMapAs);
            $TagMapAs++;
        }
        if (isset(self::$vars['tag_id'])) {
            self::$vars['tid'] = self::$vars['tag_id'];
        }
        // tid="1,2,3" 使用FIND_IN_SET
        if (isset(self::$vars['tid'])) {
            $tids = self::$vars['tid'];
            !is_array($tids) && $tids = explode(',', $tids);
            $tags = Tag::get($tids);
            $fields = array_column($tags, 'field');
            $field = $fields[0];
            $names = array_column($tags, 'name');
            $names = implode(',', $names);
            self::multiValue($field, $names);
        }
    }

    protected static function multiValue($field, $value, $not = false)
    {
        if (strpos($value, ',') === false) {
            if ($not) {
                self::$whereNot[] = DB::raw("FIND_IN_SET('" . $value . "',pid) = 0");
            } else {
                self::$where[] = [$field, 'FIND_IN_SET', $value];
            }
        } else {
            $values = str_replace(',', '|', $value);
            if ($not) {
                self::$whereNot[] = ['pid', 'NOT REGEXP', '[[:<:]](' . $values . ')[[:>:]]'];
            } else {
                self::$where[] = [$field, 'REGEXP', '[[:<:]](' . $values . ')[[:>:]]'];
            }
        }
    }

    protected static function props($fields = null)
    {
        if (isset(self::$vars['pid']) && !isset(self::$vars['pids'])) {
            self::multiValue('pid', self::$vars['pid']);
        }
        if (isset(self::$vars['pid!'])) {
            self::multiValue('pid', self::$vars['pid!'], true);
        }
        $PropMapAs = 0;
        if (isset(self::$vars['prop[]']) && is_array(self::$vars['prop[]'])) {
            $propArray = self::$vars['prop[]'];
            $fields && $fArray = Former::fields($fields);
            foreach ($propArray as $field => $pid) {
                $FA = $fArray[$field];
                if ($FA && in_array($FA['type'], ['radio_prop', 'multi_prop', 'prop'])) {
                    if ($FA['multiple']) {
                        self::$join[] = AppsMap::join('prop', $pid, self::$appid, $field, $PropMapAs);
                    } else {
                        self::$where[] = [$field, $pid];
                    }
                    $PropMapAs++;
                }
            }
        }
        if (isset(self::$vars['pids']) && !isset(self::$vars['pid'])) {
            self::$join[] = AppsMap::join('prop', self::$vars['pids'], self::$appid, 'pid', $PropMapAs);
            $PropMapAs++;
        }
    }

    protected static function keywords($concat = 'title,keywords,description')
    {
        if ($keyword = self::$vars['keyword']) {
            $kwExp = 'LIKE';
            if (strpos($keyword, ',') === false) {
                $keywords = str_replace(['%', '_'], ['\%', '\_'], $keyword);
                $keywords = "%{$keywords}%";
            } else {
                $pieces = explode(',', $keyword);
                $keywords = implode('|', array_filter($pieces));
                $kwExp = 'REGEXP';
            }
            self::$vars['keyword_field'] && $concat = self::$vars['keyword_field'];
            self::$vars['keyword_exp'] && $kwExp = self::$vars['keyword_exp'];
            self::$where[] = ['CONCAT(' . $concat . ')', $kwExp, $keywords];
        }
    }

    protected static function orderby($mapArray = [], $default = 'id', $by = 'DESC')
    {
        if (self::$vars['by']) {
            $by = strtoupper(self::$vars['by']) == 'ASC' ? 'ASC' : 'DESC';
        }
        $field = $default;
        if (is_array(self::$vars['orderby'])) {
            self::$model->orderBy(self::$vars['orderby']);
        } elseif (self::$vars['orderby'] && (strpos(self::$vars['orderby'], ',') !== false || strpos(self::$vars['orderby'], ' ') !== false)) {
            $orderbys = explode(',', self::$vars['orderby']);
            $orderByArr = [];
            foreach ($orderbys as $value) {
                list($f, $_by) = explode(' ', $value);
                $_by && $by = $_by;
                $orderByArr[$f] = $by;
            }
            self::$model->orderBy($orderByArr);
        } elseif (self::$vars['orderby'] == 'rand') {
            self::$model->orderBy('rand()', '');
        } else {
            $mapArray = array_merge([
                'id'      => 'id',
                'hits'    => 'hits',
                'hot'     => 'hits',
                'date'    => 'pubdate',
                'pubdate' => 'pubdate',
                'weight'  => 'weight',
                'sortnum' => 'sortnum',
                'sort'    => 'sortnum'
            ], $mapArray);
            $field = $mapArray[self::$vars['orderby']] ?: $default;
            self::$model->field($field)->orderBy($field, $by);
        }
    }

    protected static function where()
    {
        if (isset(self::$vars['lastId'])) {
            self::$where[] = ['id', '>', self::$vars['lastId']];
        }
        if (isset(self::$vars['id'])) {
            $expArray = explode(',', '=,<,>,<>,!=,<=,>=,like,not like');
            $id = self::$vars['id'];
            if (is_array($id) && in_array($id[0], $expArray)) {
                self::$where[] = ['id', $id[0], $id[1]];
            } else {
                is_string($id) && $id = explode(',', $id);
                self::$where[] = ['id', $id];
            }
        }
        if (isset(self::$vars['id!'])) {
            $id = self::$vars['id!'];
            is_string($id) && $id = explode(',', $id);
            if (is_array($id)) {
                self::$whereNot[] = ['id', 'not in', $id];
            } else {
                self::$whereNot[] = ['id', '<>', $id];
            }
        }
        if (self::$vars['where']) {
            if (is_array(self::$vars['where'])) {
                self::$where = array_merge(self::$where, self::$vars['where']);
            } else {
                // 存在SQL注入风险
                self::$where[] = DB::raw(trim(self::$vars['where']));
            }
        }
        self::$whereNot && self::$model->where(self::$whereNot);
        self::$where && self::$model->where(self::$where);
        self::$join && self::$model->alias(self::$app)->join(self::$join);
        count(self::$join) > 0 && $distinct = 'id';
        self::$vars['distinct'] && $distinct = self::$vars['distinct'];
        $distinct && self::$model->distinct($distinct);
        // echo self::$model->getLastSql();
    }

    protected static function getCache($cacheName = null)
    {
        if (self::$vars['cache']) {
            isset(self::$vars['cache_name']) && $cacheName = self::$vars['cache_name'];
            // is_null($cacheName) && $cacheName = self::$cacheName;
            $resource = Cache::get($cacheName);
            if (is_array($resource)) {
                return $resource;
            }
        }
    }

    protected static function getResource($calledMethod, $call = null, $data = null)
    {
        $methodKey = strtolower(str_replace(['Func', '::'], ['', '_'], $calledMethod));
        $sql = self::$model->getSql();
        self::$debug['resource']['sql'] = $sql;
        View::assign($methodKey . '_sql', $sql);
        $hash = md5($sql);
        self::paging($hash, $calledMethod);
        self::$vars['db_debug'] && DB::getQueryLog(1);
        list($class, $method) = explode('::', $calledMethod);
        if (is_null($call)) {
            // $rcall = [get_called_class(),'resource'];
            $rcall = [$class, 'resource'];
            is_callable($rcall) && $call = $rcall;
        }

        $resource = self::getCache(self::$cacheName);
        if (empty($resource)) {
            if ($call === true) {
                $resource = self::$model->select();
            } elseif ($call) {
                $idsArray = self::getIds('id', self::$vars['optimize']);
                if ($idsArray) {
                    self::$vars['ids'] && $idsArray = self::$vars['ids'];
                    View::assign($methodKey . '_ids', $idsArray);
                    $resource = call_user_func_array($call, [$idsArray, $data]);
                }
            }
            $cacheTime = isset(self::$vars['time']) ? (int) self::$vars['time'] : -1;
            self::$vars['cache'] && Cache::set(self::$cacheName, $resource, $cacheTime);
        }
        if (empty($resource)) {
            return [];
        }
        // DB::getQueryLog(1);
        self::$vars['keys'] && pluck($resource, self::$vars['keys'], isset(self::$vars['is_remove_keys']) ? false : true);
        if (self::$vars['col']) {
            return array_column($resource, null, self::$vars['col']);
        }
        if (self::$vars['callItem']) {
            $resource = array_map(self::$vars['callItem'], $resource);
        }
        if (!isset(self::$vars['loop']) && self::$vars['row']==1) {
            return $resource[0] ?? [];
        }
        return array_values($resource);
    }
}
