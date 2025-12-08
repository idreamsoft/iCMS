<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class ContentFunc extends AppsFuncCommon implements AppsFuncInterface
{
    public static $tables = null; //应用表信息
    public static $table = null;
    public static $primary = null;
    public static $relation = null;

    public static function value($vars)
    {
    }

    public static function field($vars)
    {
        self::boot($vars, 'list');

        !isset($vars['judge']) && $vars['judge'] = Request::get();
        $judge = $vars['judge'];
        $field = $vars['field'];
        $separator = $vars['separator'] ?: ',';
        $whereAs = $vars['whereAs'] ?: 'WHERE';
        $selected = [];
        if (isset($judge[$field])) {
            $selected = $judge[$field];
            $selected = explode($separator, $selected);
        }

        $urlVals = [];
        $resource = [];
        if($vars['all']!==false){
            $text = $vars['all'] ?: Lang::get('All');
            $resource[] = [
                'id'     => $field . '-null',
                'value'  => null,
                'title'  => $text,
                'text'   => $text,
                'url'    => Route::make([$field=> null], $vars['url']),
                'active' => $selected ? false : true,
            ];
        }
        $apps = Content::$APP_DATA;
        $fields = Former::getField($apps['fields'], $field);
        $options = Former::optionData($fields['option']);

        foreach ($options as $value => $text) {
            if (isset($text['items'])) {
                $optionItem = Former::optionItem($text['text'], $text['value'], $where, $selected, $fields, $separator, $vars);
                $items = [];
                foreach ($text['items'] as $k => $v) {
                    $items[] = Former::optionItem($v['text'], $v['value'], $where, $selected, $fields, $separator, $vars);
                }
                $actives = array_column($items, 'active');
                $optionItem['active'] = array_search(true,$actives)!==false;
                $optionItem['items'] = $items;
                $resource[] = $optionItem;
            } else {
                $resource[] = Former::optionItem($text, $value, $where, $selected, $fields, $separator, $vars);
            }
        }

        View::append($whereAs, $where, true);

        return $resource;
    }

    private static function boot($vars, $func = 'list')
    {
        if ((empty($vars['app']) || $vars['app'] == 'content') && Content::$APP_DATA) {
            $vars['app'] = Content::$APP_DATA['app'];
        }
        if (isset($vars['apps']) && is_array($vars['apps'])) {
            $vars['app'] = $vars['apps']['app'];
            Content::$APP_DATA = $vars['apps'];
        }
        if (isset($vars['appid'])) {
            $ap = $vars['appid'];
            if (Content::$APP_DATA && $ap != Content::$APP_DATA['id']) {
                Content::$APP_DATA = null;
            }
        } elseif (isset($vars['app'])) {
            $ap = $vars['app'];
            if (Content::$APP_DATA && $ap != Content::$APP_DATA['app']) {
                Content::$APP_DATA = null;
            }
        } elseif (Content::$APPID) {
            $ap = Content::$APPID;
        }

        if (empty($ap) || $ap == 'content') {
            self::msg(['func_param', ['iCMS:content:' . $func, 'app']]);
        }
        if (empty(Content::$APP_DATA)) {
            Content::$APP_DATA = Apps::getData($ap);
        }
        empty(Content::$APP_DATA) && self::msg(['func_param', ['iCMS:content:' . $func, 'app']]);
        Content::$APP = Content::$APP_DATA['app'];
        Content::$APPID = Content::$APP_DATA['id'];

        if ($rootid = Content::$APP_DATA['rootid']) {
            $fields = Content::$APP_DATA['fields'];
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
        $vars['apps'] = Content::$APP_DATA['app'];
        return nodeFunc::lists($vars);
    }

    public static function lists($vars)
    {
        self::boot($vars, 'list');

        $whereNot = [];
        $resource = [];
        $model = Content::model(Content::$APP_DATA)->field('id');
        $status = isset($vars['status']) ? (int) $vars['status'] : 1;
        $where = [['status', $status]];

        $vars['call'] == 'user' && $where[] = ['postype', 0];
        $vars['call'] == 'admin' && $where[] = ['postype', 1];
        isset($vars['userid']) && $where[] = ['userid', $vars['userid']];
        isset($vars['weight']) && $where[] = ['weight', $vars['weight']];
        isset($vars['ucid']) && $where[] = ['ucid', $vars['ucid']];
        isset($vars['pic']) && $where[] = ['haspic', '1'];
        isset($vars['nopic']) && $where[] = ['haspic', '0'];
        isset($vars['startdate']) && $where[] = ['pubdate', '>=', str2time($vars['startdate'] . (strpos($vars['startdate'], ' ') !== false ? '' : ' 00:00:00'))];
        isset($vars['enddate']) && $where[] = ['pubdate', '<=', str2time($vars['enddate'] . (strpos($vars['enddate'], ' ') !== false ? '' : ' 00:00:00'))];

        self::relation($vars, $where);
        self::inited($vars, $model, $where, $whereNot);
        self::setApp(Content::$APPID, Content::$APP);
        self::nodes('cid');
        self::tags(Content::$APP_DATA['fields']);
        self::props(Content::$APP_DATA['fields']);
        self::keywords('title');
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

        $resource = Content::model(Content::$APP_DATA)->field('*')
            ->where($idsArray)
            ->orderBy('id', $idsArray)
            ->select();

        $resource = self::many($vars, $resource);
        return $resource;
    }

    public static function many($vars, $resource)
    {
        if ($resource) {
            $appClassName = sprintf('%sApp', ucfirst(Content::$APP));
            if (class_exists($appClassName)) {
                $ContentApp = new $appClassName;
            }else{
                $ContentApp = new ContentApp(Content::$APP);
            }
            
            $idArray = array_column($resource, 'id');
            if ($vars['data'] || $vars['body_pics']) {
                $idArray && $DATAS = (array) $ContentApp->data($idArray);
            }
            if ($vars['meta'] && $idArray) {
                $metaData = [];
                AppsMeta::data(Content::$APP, $idArray, $metaData);
            }

            if ($vars['tag']) {
                $tagArray = array_column($resource, 'tags', 'id');
                $tagArray && $tagsData = (array) TagApp::many($tagArray);
                unset($tagArray);
                $vars['tag'] = false; //ContentApp::values 里不调用
            }

            foreach ($resource as $key => &$value) {
                try {
                    $ContentApp->values($value, $vars);
                } catch (\FalseEx $fex) {
                    $value = [];
                    continue;
                }

                if (($vars['data'] || $vars['body_pics']) && $DATAS) {
                    $value['data'] = (array) $DATAS[$value['id']];
                    if ($vars['body_pics']) {
                        $value['body_pics'] = FilesPic::findImgUrl($value['data']['body']);
                        if (!$value['data']) {
                            unset($value['data']);
                        }
                    }
                }

                if ($vars['tag'] && $tagsData) {
                    $value += (array) $tagsData[$value['id']];
                }
                if ($vars['meta'] && $metaData) {
                    $value += (array) $metaData[$value['id']];
                }

                if ($vars['page']) {
                    $value['page'] = isset($GLOBALS['page']) ? $GLOBALS['page'] : 1;
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
        self::boot($vars, 'next');
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
        $model = Content::model(Content::$APP_DATA)->field($field)->where($where);
        $hash = md5($model->getSql());
        if ($vars['cache']) {
            $cacheName = sprintf('%s/%s/%s', iPHP_DEVICE, Content::$APP, $hash);
            $resource = Cache::get($cacheName);
            if (is_array($resource)) {
                return $resource;
            }
        }
        $id = $model->value();
        $data = Content::model(Content::$APP_DATA)->field('*')->get($id);
        if ($data) {
            $node = NodeCache::getId($data['cid']);
            $resource = [
                'id'    => $data['id'],
                'title' => $data['title'],
                'pic'   => FilesPic::getArray($data['pic']),
                'url'   => Route::get(Content::$APP, [(array) $data, $node])->href,
            ];
        }
        $cacheTime = isset($vars['time']) ? (int) $vars['time'] : -1;
        $vars['cache'] && Cache::set($cacheName, $resource, $cacheTime);

        return $resource;
    }
}
