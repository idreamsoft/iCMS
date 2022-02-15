<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class ContentFunc extends AppsFuncCommon implements AppsFuncBase
{
    public static $APPDATA  = null; //应用信息接口
    public static $tables   = null; //应用表信息
    public static $APP      = null;
    public static $APPID    = 0;
    public static $table    = null;
    public static $primary  = null;
    public static $relation = null;
    public static function value($vars)
    {
    }

    /**
     * 已在 nodeApp contentApp 设置数据回调,
     * 在应用范围内可以不用设置 app="应用名/应用ID"
     **/
    public static function interfaced($data = null)
    {
        self::$APPDATA = $data;
    }
    private static function inited($vars, $func = 'list')
    {
        if ((empty($vars['app']) || $vars['app'] == 'content') && self::$APPDATA) {
            $vars['app'] = self::$APPDATA['app'];
        }
        if (isset($vars['apps']) && is_array($vars['apps'])) {
            $vars['app'] = $vars['apps']['app'];
            self::$APPDATA = $vars['apps'];
        }
        if (isset($vars['appid'])) {
            $ap = $vars['appid'];
            if (self::$APPDATA && $ap != self::$APPDATA['id']) {
                self::$APPDATA = null;
            }
        } else {
            $ap = $vars['app'];
            if (self::$APPDATA && $ap != self::$APPDATA['app']) {
                self::$APPDATA = null;
            }
        }
        if (empty($ap) || $ap == 'content') {
            Script::warning('iCMS&#x3a;content&#x3a;' . $func . ' 标签出错! 缺少参数"app"或"app"值为空.');
        }
        if (empty(self::$APPDATA)) {
            self::$APPDATA = Apps::getData($ap);
        }
        empty(self::$APPDATA) && Script::warning('iCMS&#x3a;content&#x3a;' . $func . ' 标签出错! 缺少参数"app"或"app"值为空.');
        self::$APP = self::$APPDATA['app'];
        self::$APPID = self::$APPDATA['id'];

        if ($rootid = self::$APPDATA['rootid']) {
            $fields = self::$APPDATA['fields'];
            if ($fArray = Former::fields($fields)) {
                $column = array_column($fArray, 'type', 'id');
                self::$relation = array_search('relation:id', $column);
            }
        }
    }
    public static function relation($vars, &$where)
    {
        if (self::$relation && isset($vars[self::$relation])) {
            $where[] = [self::$relation, $vars[self::$relation]];
        }
    }
    public static function node($vars)
    {
        $vars['apps'] = self::$APPDATA['app'];
        return nodeFunc::lists($vars);
    }
    public static function lists($vars)
    {
        self::inited($vars, 'list');

        $whereNot  = array();
        $resource  = array();
        $model     = Content::model(self::$APPDATA)->field('id');
        $status    = isset($vars['status']) ? (int) $vars['status'] : 1;
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

        self::relation($vars, $where);
        self::init($vars, $model, $where, $whereNot);
        self::setApp(self::$APPID, self::$APP);
        self::nodes('cid');
        self::tags(self::$APPDATA['fields']);
        self::props(self::$APPDATA['fields']);
        self::keywords('title');
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

        $resource =  Content::model(self::$APPDATA)->field('*')
            ->where($idsArray)
            ->orderBy('id', $idsArray)
            ->select();

        $resource = self::many($vars, $resource);
        return $resource;
    }
    public static function many($vars, $resource)
    {

        if ($resource) {
            $contentApp = new contentApp(self::$APP);
            $idArray = array_column($resource, 'id');
            if ($vars['data'] || $vars['pics']) {
                $idArray && $DATAS = (array) $contentApp->data($idArray);
            }
            if ($vars['meta'] && $idArray) {
                $metaData = (array) AppsMeta::data(self::$APP, $idArray);
            }

            if ($vars['tags']) {
                $tagArray = array_column($resource, 'tags', 'id');
                $tagArray && $tagsData = (array) TagApp::many($tagArray);
                unset($tagArray);
                $vars['tag'] = false; //ContentApp::values 里不调用
            }

            foreach ($resource as $key => &$value) {
                try {
                    $contentApp->values($value, $vars);
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
                $resource[$key] = $value;
            }
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
        self::inited($vars, 'next');

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
        self::relation($vars, $where);
        $model = Content::model(self::$APPDATA)->field($field)->where($where);
        $hash = md5($model->getSql());
        if ($vars['cache']) {
            $cacheName = sprintf("s%/s%/s%", iPHP_DEVICE, self::$APP, $hash);
            $resource = Cache::get($cacheName);
            if (is_array($resource)) {
                return $resource;
            }
        }
        $id = $model->value();
        $data = Content::model(self::$APPDATA)->field('*')->get($id);
        if ($data) {
            $node = NodeCache::getId($data['cid']);
            $resource = array(
                'id'    => $data['id'],
                'title' => $data['title'],
                'pic'   => FilesPic::getArray($data['pic']),
                'url'   => Route::get(self::$APP, array((array) $data, $node))->href,
            );
        }
        $cacheTime = isset($vars['time']) ? (int) $vars['time'] : -1;
        $vars['cache'] && Cache::set($cacheName, $resource, $cacheTime);

        return $resource;
    }
}
