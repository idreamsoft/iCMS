<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class SearchFunc extends AppsFuncCommon
{
    public static function token($vars)
    {
        $salt = $vars['salt'] ?: random(8);
        $hashids = Vendor::run('Hashids', ['len' => '16']);
        $hash = $hashids->encode($vars['id'], time());
        $token = md5(sha1(md5(iPHP_KEY) . $salt) . $salt) . '_' . $hash;
        $token = urlencode($token);
        return $token;
    }

    public static function lists($vars = null)
    {
        $whereNot = [];
        $resource = [];
        $model = SearchLogModel::field('id');
        $where = [];
        // $where[] = ['userid', $vars['userid']];
        self::inited($vars, $model, $where, $whereNot);
        self::props();
        self::orderby([
            'hot' => 'times',
            'new' => 'id',
        ], 'id');
        self::where();
        return self::getResource(__METHOD__);
    }

    public static function resource($idsArray = null)
    {
        $vars = self::$vars;
        $resource = SearchLogModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        if ($resource) {
            foreach ($resource as $key => $value) {
                $value['title'] = $value['search'];
                $value['name'] = $value['search'];
                $value['url'] = self::url([
                    'keyword' => $value['name'],
                    'ret'   => true
                ]);
                $resource[$key] = $value;
            }
        }
        return $resource;
    }

    public static function url($vars)
    {
        $keyword = rawurlencode($vars['keyword']);
        if (empty($keyword)) {
            return;
        }
        $app = isset($vars['_app']) ? $vars['_app'] : 'search';
        $iURL = SearchApp::iurl($keyword, $app, false);
        if ($vars['ret']) {
            return $iURL->url;
        }
        echo $iURL->url;
    }
}
