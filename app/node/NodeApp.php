<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class NodeApp extends AppsBase
{
    public function index($tpl = 'index', $isList = null)
    {
        $id = (int) Request::sget('id');
        $dir = Request::get('dir');
        if (empty($id) && $dir) {
            $id = NodeCache::get('dir2nid', $dir);
            $id or self::alert(['node:errors:not_found', ['dir', $dir]], 20002);
        }
        return $this->node($id, $tpl, $isList);
    }

    public function do_index()
    {
        return $this->index('index');
    }

    public function do_list()
    {
        return $this->index('list', true);
    }

    /**
     * hooked 钩子
     * @param  [type] $data [description]
     * @return void
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
            self::alert(['node:errors:not_found', ['id', $id]], 20001, $tpl);
        }
        if ($node['status'] == 0) {
            self::alert(['node:errors:status_0', [$id]], 20002, $tpl);
        }
        $node['params'] = [
            'SAPPID' => $node['SAPPID'],
            'appid'  => $node['appid'],
            'app'    => Node::APP,
            'iid'    => $node['id'],
            'id'     => $node['id'],
            'userid'  => $node['userid'],
            'title'  => $node['name'],
            'url'    => $node['url']
        ];

        // self::hooked($node);
        if (!$tpl) {
            return $node;
        }
        $node['config']['role'] && self::checkRole($node, $tpl);

        if (View::$gateway == 'html') {
            $isphp = strpos($node['rule']['index'], '{PHP}');
            if ($isphp !== false || $node['outurl'] || !$node['mode']) {
                self::alert("Node [id={$id}] URL mode must be HTML mode");
            }
        }
        $node['outurl'] && Utils::jumpto($node['outurl']);
        $node['mode'] == '1' && self::redirectToHtml($node['iurl']);
        $node['mode'] && Route::setPageUrl($node['iurl']);
        Route::getUrls($node);
        $view_app = 'node';
        if ($APP_DATA = $node['APP']) {
            Content::$APPID = $APP_DATA['id'];
            $APP_DATA['apptype'] == Apps::CONTENT_TYPE && Content::setAppData($APP_DATA); //自定义应用模板信息
            View::assign('APP', $APP_DATA); //绑定的应用信息
            $view_app = $APP_DATA['app'];
        }
        View::assign('node', $node);
        View::assign('category', $node); //兼容v7

        if (strpos($tpl, '.htm') !== false) {
            return View::render($tpl, $view_app, $node['iurl']);
        }
        (int)Request::param('page') > 1 && $isList = true;
        $isList && $tpl = 'list';
        if ($rtpl = $node['template'][$tpl]) {
            return View::render($rtpl, $view_app, $node['iurl']);
        } else {
            self::alert(['node:errors:not_template', ['id', $id, $tpl]], 20003);
        }
    }

    //--------------------------------------------------
    //绑定域名 Route::$callback['domain'] 回调函数
    public static function domain($i, $node, $base_url, $type)
    {
        $id = $node['id'];
        $domain = null;
        $root_domain = false;
        $domainMap = (array) Config::get('node.domain');
        if ($domainMap) {
            $domainMap = array_flip($domainMap);
            $domain = isset($domainMap[$id]) ? $domainMap[$id] : null;
            if (empty($domain)) {
                $rootIdMap = NodeCache::get('domain_rootid');
                if (!empty($rootIdMap) && isset($rootIdMap[$id])) {
                    $rootId = $rootIdMap[$id];
                    $domain = isset($domainMap[$rootId]) ? $domainMap[$rootId] : null;
                    $root_domain = true;
                }
            }
        }
        if (empty($domain)) {
            return $i;
        }

        $scheme = parse_url($base_url, PHP_URL_SCHEME) . '://';
        if (!Request::isUrl($domain)) {
            $domain = $scheme . $domain;
        }

        if ($type == 1) { //节点
            if ($root_domain) {
                $i->href = Adapter::replace($base_url, $domain, $i->href);
            } else {
                $i->href = $domain;
                $i->url = $domain;
            }
            if (isset($i->hdir) && isset($i->pageurl)) {
                $i->pageurl = Adapter::replace($i->hdir, $domain, $i->pageurl);
            }
        } else {
            $i->href = Adapter::replace($base_url, $domain, $i->href);
            isset($i->hdir) && $i->hdir = Adapter::replace($base_url, $domain, $i->hdir);
            isset($i->pageurl) && $i->pageurl = Adapter::replace($base_url, $domain, $i->pageurl);
        }


        return $i;
    }
}
