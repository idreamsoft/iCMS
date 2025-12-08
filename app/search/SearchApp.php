<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class SearchApp extends AppsBase
{
    public $methods = [iPHP_APP];
    public static $route = null;
    public static $data = [];
    public static $callback = [];

    public function do_index()
    {
        return $this->display();
    }
    public function api_search()
    {
        return $this->display();
    }

    public function api_index()
    {
        return $this->display();
    }

    public function done_sign()
    {
        $query = Request::post('query');
        if ($query) {
            extract($query);
            $time = time();
            $once = random(8);
            $keyword = auth_encode($query['keyword'] . '@#@' . $once);
            $params = compact('app', 'do', 'keyword', 'time');
            $params['sign'] = Utils::sign($params);
            $url = Route::routing();
            return Route::make($params, $url);
        }
    }

    public function display($tpl = false, $app = 'search')
    {
        // $params = Request::param();
        // var_dump($params);
        // $decode = auth_decode($params['keyword']);
        // $sign = $params['sign'];
        // unset($params['sign']);
        // list($keyword,$once) = explode('@#@',$decode);
        // // $keyword = auth_encode($_keyword.'@#@'.$once);
        // // $params['keyword'] = $keyword;
        // // var_dump($params);
        // $_sign = Utils::sign($params);
        // if($sign!=$_sign){
        //     AppsBase::alert('Signature error', 60000);
        // }
        $keyword = Request::param('keyword');
        $keyword = rawurldecode($keyword);
        $keyword = Security::encoding($keyword);

        if (class_exists('Filter')) {
            Filter::$disable = Cache::get('search/disable');
            Filter::disable($keyword) && self::alert('iCMS:search:Illegal', 60002);
        }

        $data['keyword'] = $keyword;
        $data['title'] = $keyword;
        $data['iurl'] = (array) self::iurl($keyword, $app);
        $keyword && $this->log($keyword);
        $appData = Apps::getData(Search::APPID);
        $data['SAPP'] = Apps::getDataLite($appData);
        $tpl === false && $tpl = sprintf('%s/search.htm', View::TPL_FLAG_1);
        return self::render($data, $tpl, 'search', $app);
    }

    public static function iurl($keyword = null, $app = 'search', $page = true)
    {
        $query = ['app' => $app, 'do' => 'search', 'keyword' => $keyword];
        $i = new stdClass();
        empty(self::$route) && self::$route = Route::routing('api');
        $i->url = Route::make($query, self::$route);
        $i->pageurl = Route::make('page={P}', $i->url);
        $i->href = $i->url;
        if (self::$callback['iurl'] && is_callable(self::$callback['iurl'])) {
            $i = call_user_func_array(self::$callback['iurl'], [$i, $query]);
        }
        if (Route::$callback['device']) {
            $d = call_user_func_array(Route::$callback['device'], [$i]);
            $i = (object)array_merge((array)$i, $d);
        }
        if (Route::$callback['url']['data']) {
            call_user_func_array(Route::$callback['url']['data'], [&$i]);
        }
        $page && Route::setPageUrl($i);
        return $i;
    }

    private function log($search)
    {
        // $interval = 30;
        // $ip    = Request::ip();
        $time = time();
        // $key   = 'search/'.$ip;
        // $stime = Cache::get($key);

        // if($stime && $time-$stime<$interval){
        //     Application::alert('您搜索太快休息下,'.format_time($interval,'cn').'之后再继续', 60003);
        // }
        // Cache::set($key,$time,$interval);

        $sid = SearchLogModel::field('id')->where(compact('search'))->value();
        if ($sid) {
            SearchLogModel::where($sid)->inc('times');
        } else {
            $data = compact('search');
            $data['times'] = '1';
            $data['create_time'] = $time;
            SearchLogModel::create($data, true);
        }
    }
}
