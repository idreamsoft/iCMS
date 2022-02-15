<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class NodeApp
{
    public $methods = array(iPHP_APP, 'node', 'list');
    public function __construct($appid = iCMS_APP_ARTICLE)
    {
        // $this->appid = iCMS_APP_ARTICLE;
        // $appid && $this->appid = $appid;
        // $_GET['appid'] && $this->appid	= (int)$_GET['appid'];
    }
    public function do_iCMS($tpl = 'index', $isList = null)
    {
        $id = (int) $_GET['id'];
        $dir = Request::get('dir');
        if (empty($id) && $dir) {
            $id = NodeCache::get('dir2nid', $dir);
            $id or AppsApp::throwError(['node:not_found', ['dir', $dir]], 20002);
        }
        return $this->node($id, $tpl, $isList);
    }
    public function do_list($tpl = 'index')
    {
        return $this->do_iCMS($tpl, true);
    }
    public function API_iCMS()
    {
        return $this->do_iCMS();
    }
    /**
     * [hooked 钩子]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    // public static function hooked(&$data){
    //     iPHP::hook('node',$data,Config::get('hooks.node'));
    // }
    /**
     * 该方法多次调用 禁止SQL查询
     */
    public static function node($id, $tpl = 'index', $isList = null)
    {
        $node = NodeCache::getId($id);
        if (empty($node)) {
            $msg = Lang::get('node:not_found', ['id', $id]);
            $tpl ?
                AppsApp::throwError($msg, 20001) :
                throwFalse($msg);
        }
        if ($node['status'] == 0) {
            $msg = Lang::get('node:status_0', [$id]);
            $tpl ?
                AppsApp::throwError($msg, 20002) :
                throwFalse($msg);
        }

        if ($tpl) {
            if (View::$gateway == "html") {
                $isphp = strpos($node['rule']['index'], '{PHP}');
                if ($isphp !== false || $node['outurl'] || !$node['mode']) {
                    throwFalse("Node [id={$id}] URL mode must be HTML mode");
                }
            }
            $node['outurl'] && Helper::redirect($node['outurl']);
            $node['mode'] == '1' && AppsApp::redirectToHtml($node['iurl']);
        }

        NodeItem::route($node);

        $node['param'] = array(
            "SAPPID" => $node['SAPPID'],
            "appid"  => $node['appid'],
            "iid"    => $node['id'],
            "id"    => $node['rootid'],
            "suid"   => $node['userid'],
            "title"  => $node['name'],
            "url"    => $node['url']
        );
        // self::hooked($node);

        if (!$tpl) return $node;

        View::setGlobal($node['iurl'], 'iURL');
        $node['mode'] && Route::getPageUrl($node['iurl']);
        $view_app = "node";
        if ($APP = $node['app']) {
            $APP['type'] == "2" && ContentFunc::interfaced($APP); //自定义应用模板信息
            View::assign('APP', $APP); //绑定的应用信息
            $view_app = $APP['app'];
        }
        View::assign('node', $node);
        View::assign('category', $node); //兼容v7

        if (strpos($tpl, '.htm') !== false) {
            return View::render($tpl, $view_app);
        }
        (int)Request::param('page') > 1 && $isList = true;
        $isList && $tpl = 'list';
        if ($rtpl = $node['template'][$tpl]) {
            $view = View::render($rtpl, $view_app);
        } else {
            AppsApp::throwError(['node:not_template', ['id', $id, $tpl]], 20002);
        }
        if ($view) return array($view, $node);
    }

    //--------------------------------------------------
    //绑定域名 Route::$callback['domain'] 回调函数
    public static function domain($i, $id, $base_url)
    {
        $domainArray = (array) Config::get('node.domain');
        if ($domainArray) {
            $domainArray = array_flip($domainArray);
            $domain = $domainArray[$id];
            if (empty($domain)) {
                $rootid_array = NodeCache::get("domain_rootid");
                if ($rootid_array) {
                    $rootid = $rootid_array[$id];
                    $rootid && $domain = $domainArray[$rootid];
                }
            }
        }
        if ($domain) {
            $scheme = parse_url($base_url, PHP_URL_SCHEME) . '://';
            Request::isUrl($domain) or $domain = $scheme . $domain;

            $i->href    = str_replace($base_url, $domain, $i->href);
            $i->hdir    = str_replace($base_url, $domain, $i->hdir);
            $i->pageurl = str_replace($base_url, $domain, $i->pageurl);
        }
        return $i;
    }
}
