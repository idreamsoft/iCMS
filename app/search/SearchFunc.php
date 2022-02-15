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
        $hashids = Vendor::run('Hashids', array("len" => '16'));
        $hash    = $hashids->encode($vars['id'], time());
        $token   = md5(sha1(md5(iPHP_KEY) . $salt) . $salt) . '_' . $hash;
        $token   = urlencode($token);
        return $token;
    }

    public static function lists($vars = null)
    {
        $whereNot  = array();
        $resource  = array();
        $model     = SearchLogModel::field('id');
        $where = [];
        // $where[] = ['userid', $vars['userid']];
        self::init($vars, $model, $where, $whereNot);
        self::setApp(Search::APPID,Search::APP);

        self::orderby([], 'id');
        self::where();
        return self::getResource(__METHOD__, [__CLASS__, 'resource']);
    }
    public static function resource($vars, $idsArray = null)
    {
        $vars['ids'] && $idsArray = $vars['ids'];
        $resource = SearchLogModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        if ($resource) foreach ($resource as $key => $value) {
            $value['name']  = $value['search'];
            $value['url']   = self::url(array(
                'query' => $value['name'],
                'ret' => true
            ));
            $resource[$key] = $value;
        }
        return $resource;
    }
    public static function url($vars)
    {
        $q = rawurlencode($vars['q']);
        $vars['query'] && $q = rawurlencode($vars['query']); //兼容
        if (empty($q)) {
            return;
        }
        $query = array('app' => 'search', 'q' => $q);
        if (isset($vars['_app'])) {
            $query['app'] = $vars['_app'];
            $query['do']  = 'search';
        }
        $iURL = SearchApp::iurl($q, $query, false);
        if ($vars['ret']) {
            return $iURL->url;
        }
        echo $iURL->url;
    }
}
