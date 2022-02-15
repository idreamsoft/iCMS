<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class SearchApp
{
    public $methods = array(iPHP_APP);
    public static $route   = null;
    public static $data     = array();
    public static $callback = array();
    public function do_iCMS()
    {
        return $this->display();
    }
    public function API_iCMS()
    {
        return $this->display();
    }
    public function display($tpl = false, $app = 'search')
    {
        $keyword = Request::param('keyword');
        $keyword = rawurldecode($keyword);
        $keyword = Security::encoding($keyword);

        if(class_exists('Filter')){
            Filter::$disable = Cache::get('search/disable');
            $fwd = iPHP::callback('Filter::run', array(&$keyword), false);
            $fwd && AppsApp::throwError('iCMS:search:Illegal', 60002);
        }

        $data['keyword'] = $keyword;
        $data['title']   = $keyword;
        $data['iurl']    = (array) self::iurl($keyword);
        $keyword && $this->search_log($keyword);

        $appData = Apps::getData(Search::APPID);
        $data['SAPP'] = Apps::getDataLite($appData);

        $tpl === false && $tpl = sprintf('%s/search.htm', View::TPL_FLAG_1);
        return AppsApp::render($data, $tpl, 'search', $app);
    }
    public static function iurl($q, $query = null, $page = true)
    {
        $query === null && $query = array('app' => 'search', 'q' => $q);
        $iURL           =  new stdClass();
        $iURL->url      = Route::make($query, self::$route ?: 'route::api');
        $iURL->pageurl  = Route::make('page={P}', $iURL->url);
        $iURL->href     = $iURL->url;
        if (self::$callback['iurl'] && is_callable(self::$callback['iurl'])) {
            $iURL = call_user_func_array(self::$callback['iurl'], array($iURL, $query));
        }
        $page && Route::getPageUrl($iURL);
        return $iURL;
    }
    private function search_log($search)
    {
        // $interval = 30;
        // $ip    = Request::ip();
        $time  = time();
        // $key   = 'search/'.$ip;
        // $stime = Cache::get($key);

        // if($stime && $time-$stime<$interval){
        //     iAPP::throwError('您搜索太快休息下,'.format_time($interval,'cn').'之后再继续', 60003);
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
