<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class TagApp extends AppsApp
{
    public function __construct()
    {
        parent::__construct('tag');
    }

    public function do_iCMS($a = null)
    {
        if ($value = Request::get('name')) {
            $field = 'name';
        } elseif ($value = Request::get('tkey')) {
            $field = 'tkey';
        } elseif ($value = (int)Request::get('id')) {
            $field = 'id';
        } else {
            self::throwError('tag:error:request', 30001);
        }
        return $this->display($value, $field);
    }

    public static function display($value, $field = 'name', $tpl = 'tag')
    {
        $value or self::throwError('tag:error:empty', 30002);
        if (strpos($value, '\\x') !== false) {
            $value = str_replace('\\x', '%x', $value);
            $value = urldecode($value);
        };
        is_array($value) or $tag = TagModel::where($field, $value)->get();

        if (empty($tag)) {
            $msg = '找不到标签: <b>[' . $field . '=' . $value . ']</b>';
            $tpl ?
                self::throwError($msg, 30003) :
                throwFalse($msg, 30003);
        }

        $vars = ['page_url' => true];
        self::values($tag, $vars);
        self::getCustomData($tag, $vars);
        self::hooked($tag);

        $view_tpl = $tpl;
        $view_app = "tag";

        if ($tpl) {
            $view_tpl = $tag['tpl'];
            $view_tpl or $view_tpl = $tag['tag_node']['template']['tag'];
            $view_tpl or $view_tpl = $tag['node']['template']['tag'];
            $view_tpl or $view_tpl = self::$config['tpl'];
            $view_tpl or $view_tpl = sprintf('%s/tag.htm', View::TPL_FLAG_1);
            strstr($tpl, '.htm') && $view_tpl = $tpl;
            $tag['node']['app']['app'] && $view_app = $tag['node']['app']['app'];
            View::assign('tag_node', $tag['tag_node']);
            unset($tag['tag_node']);
        }
        return self::render($tag, $view_tpl, 'tag', $view_app);
    }
    public static function values(&$data, $vars = null)
    {
        $data['appid'] = iCMS_APP_TAG;
        if ($data['cid']) {
            //多选只用第一个
            if (strpos($data['cid'], ',') !== false) {
                $cidArray = explode(',', $data['cid']);
                $data['cid'] = $cidArray[0];
            }
            $node = NodeApp::node($data['cid'], false);
            $data['node'] = $node;
            $data['app']  = $node['app'];
            if ($data['app']['type'] == "2") {
                //自定义应用模板信息
                iPHP::callback(array("contentFunc", "interfaced"), array($data['app']));
            }
        }
        if ($data['tcid']) {
            $tNode = NodeApp::node($data['tcid'], false);
            $data['tag_node'] = $tNode;
            if ($tNode['app']['type'] == "2") {
                //自定义应用模板信息
                iPHP::callback(array("contentFunc", "interfaced"), array($tNode['app']));
            }
        }

        $data['iurl'] = (array)Route::get('tag', array($data, $node, $tNode));

        if ($vars['url'] == 'self') {
            $fkey = 'tids';
            $vars['field'] && $fkey = $vars['field'];
            $nurl = Route::make(array($fkey => $data['id']), null);
            $data['iurl']['href'] = $nurl;
            $data['iurl']['url']  = $nurl;
            foreach ($data['iurl'] as $key => $value) {
                is_array($value) && $data['iurl'][$key]['url'] = $nurl;
            }
        }
        $data['url'] or $data['url'] = $data['iurl']['href'];

        if (stripos($data['url'], '.php?') === false && isset($vars['page_url']) && $vars['page_url']) {
            Route::getPageUrl($data['iurl']);
        }

        $data['related']  && $data['relatedArray'] = explode(',', $data['related']);

        AppsCommon::init($data, $vars)
            ->link()
            ->comment()
            ->pic()
            ->hits()
            ->param();
        return $data;
    }
    public static function getArray(&$data = array(), $fname = null, $key = 'tags', $value = null, $id = 'id')
    {
        $data[$key . '_fname'] = $fname;
        $value === null && $value = $data[$key];
        if ($value) {
            $many = self::many(array($data[$id] => $value), $key);
            $many && $data += (array)$many[$data[$id]];
        }
    }

    public static function many($tags = null, $tkey = 'tags')
    {
        if (empty($tags)) return array();

        if (!is_array($tags) && strpos($tags, ',') !== false) {
            $tags = explode(',', $tags);
        }
        foreach ($tags as $id => $value) {
            if ($value) {
                $a = explode(',', $value);
                foreach ($a as $ak => $av) {
                    $tMap[$av][] = 't:' . $id; //self::map 中array_merge 必需以字符串合并 才不会重建索引
                    $tArray[] = $av;
                }
                $tArray = array_unique($tArray);
            }
        }
        if ($tArray) {
            $result = Tag::get($tArray, 'name');
            if ($result) foreach ($result as $key => $value) {
                try {
                    $result[$key] = self::values($value);
                } catch (\FalseEx $fex) {
                    unset($result[$key]);
                    continue;
                }
            }
            $result && self::map($result, $tMap);
            $result && self::vars($result, $tkey);
            return $result;
        }
        return false;
    }

    private static function vars(&$data, $tk)
    {
        $array = array();
        foreach ((array) $data as $iid => $tag) {
            $iid = substr($iid, 2);
            $arr = array_column($tag, null, 'id');
            $linkArr = array_column($tag, 'link');
            $data = [
                $tk . '_array' => $arr,
                $tk . '_link' => implode(' ', $linkArr)
            ];
            $tmp = $arr;
            if (is_array($tmp)) {
                sort($tmp);
                $data[$tk . '_array'] = $tmp;
                $data[$tk . '_fname'] = $tmp[0]['name'];
                $data[$tk . '_ftid']  = $tmp[0]['id'];
                $data[$tk . '_furl']  = $tmp[0]['url'];
                $data[$tk . '_farray']  = array(
                    'id'   => $tmp[0]['id'],
                    'url'  => $tmp[0]['url'],
                    'name' => $tmp[0]['name'],
                );
            }
            $array[$iid] = $data;
        }
        $array && $data = $array;
    }
    private static function map(&$data, $tMap, $field = 'name')
    {
        $array = array();
        foreach ((array)$data as $tid => $tag) {
            $iidArray = $tMap[$tag[$field]];
            if (is_array($iidArray)) {
                $a = array_fill_keys($iidArray, array($tid => $tag));
                $array = array_merge_recursive($array, $a);
                unset($a);
            }
        }
        $data = $array;
    }
}
