<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class NodeItem
{
    public static $callback = [];
    public static function make($C)
    {
        if ($C['url']) {
            $C['iurl']   = array('href' => $C['url']);
            $C['outurl'] = $C['url'];
        } else {
            $C['iurl'] = (array) Route::get('node', $C);
        }

        $C['url']    = $C['iurl']['href'];
        $C['link']   = "<a href='{$C['url']}'>{$C['name']}</a>";
        $C['sname']  = $C['subname'];

        $C['subid']  = NodeCache::child($C['id']);
        $C['counts'] = $C['count'];
        foreach ((array) $C['subid'] as $skey => $snid) {
            $sc = NodeCache::get($snid);
            $C['counts'] += $sc['count'];
        }

        $C['child']  = $C['subid'] ? true : false;
        $C['childNum'] = count($C['subid']);
        $C['subids'] = implode(',', (array) $C['subid']);
        $C['dirs']   = self::dirs($C['id']);

        self::pic($C);
        self::parent($C);
        self::nav($C);
        
        $C += (array) AppsMeta::data('node', $C['id']);

        //node 应用信息
        $C['SAPPID'] = iCMS_APP_NODE;
        $appData = Apps::getData($C['SAPPID']);
        $C['SAPP'] = Apps::getDataLite($appData);
        $appData['fields'] && FormerApp::data($C['id'], $appData, 'node', $C, null, $C);
        //node 绑定的应用
        $C['appid'] && $C['app'] = Apps::getData($C['appid']);

        empty($C['rule'])    && $C['rule']     = array();
        empty($C['template']) && $C['template'] = array();
        empty($C['config'])  && $C['config']   = array();

        return $C;
    }
    public static function dirs($id = "0")
    {
        $dir = '';
        $C = NodeCache::get($id);
        $C['rootid'] && $dir .= self::dirs($C['rootid']);
        $dir .= '/' . $C['dir'];
        return $dir;
    }
    public static function pic(&$C)
    {
        $C['pic']  = is_array($C['pic']) ? $C['pic'] : FilesPic::getArray($C['pic']);
        $C['bpic'] = is_array($C['mpic']) ? $C['mpic'] : FilesPic::getArray($C['mpic']);
        $C['mpic'] = is_array($C['mpic']) ? $C['mpic'] : FilesPic::getArray($C['mpic']);
        $C['spic'] = is_array($C['spic']) ? $C['spic'] : FilesPic::getArray($C['spic']);
    }
    public static function parent(&$C)
    {
        if ($C['rootid']) {
            $root = NodeCache::get($C['rootid']);
            $C['parent'] = self::make($root);
        }
    }
    public static function nav(&$C)
    {
        $nav      = '';
        $navArray = array();
        self::navArr($C, $navArray);
        krsort($navArray);
        foreach ((array) $navArray as $key => $value) {
            $nav .= "<li>
            <a href='{$value['url']}'>{$value['name']}</a>
            <span class=\"divider\">" . Lang::get('iCMS:navTag') . "</span>
            </li>";
        }
        $C['nav'] = $nav;
        $C['navArray'] = $navArray;
    }

    public static function navArr($C, &$navArray = array())
    {
        if ($C) {
            $navArray[] = array(
                'name' => $C['name'],
                'url'  => $C['iurl']['href'],
            );
            if ($C['rootid']) {
                $rc = (array) NodeCache::get($C['rootid']);
                $rc['iurl'] = (array) Route::get('node', $rc);
                self::navArr($rc, $navArray);
            }
        }
    }
    public static function route(&$node)
    {
        if($call = self::$callback['route']){
            return call_user_func_array($call,[&$node]);
        }
        if ($node && !$node['iDevice']) {
            if (!Adapter::$IS_IDENTITY_URL) {
                Adapter::route($node);
                // Adapter::route($node['iurl']);
                // Adapter::route($node['navArray']);
                $node['parent'] && self::route($node['parent']);
                $node['iDevice'] = true;
            }
        }
    }
    public static function get($node)
    {
        $keyArray = array('sortnum', 'password', 'mode', 'domain', 'config', 'addtime');
        foreach ($keyArray as $i => $key) {
            unset($node[$key]);
        }
        self::route($node);
        return $node;
    }
}
