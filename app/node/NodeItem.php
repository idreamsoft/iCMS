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

    public static function url(&$C){
        if ($C['url']) {
            $C['iurl'] = ['href' => $C['url']];
            $C['outurl'] = $C['url'];
        } else {
            $C['iurl'] = (array) Route::get('node', $C);
            $C['outurl'] = '';
        }
        if(strpos($C['iurl']['href'],'{iCMS_URL}')!==false){
            $base_url = rtrim(iCMS_URL,'/');
            $C['iurl']['href'] = str_replace('{iCMS_URL}', $base_url, $C['iurl']['href']);
        }
        $C['url'] = $C['href'] = $C['iurl']['href'];
        $C['link'] = sprintf('<a href="%s">%s</a>', $C['url'], $C['name']);
    }
    public static function make($C)
    {
        self::url($C);
        // empty($C['title']) && $C['title'] = $C['name'];
        $C['sname'] = $C['subname'];
        $C['subid'] = NodeCache::child($C['id']);
        $C['counts'] = $C['count'];
        foreach ((array) $C['subid'] as $skey => $snid) {
            $sc = NodeCache::getId($snid);
            $C['counts'] += $sc['count'];
        }

        $C['child'] = $C['subid'] ? true : false;
        $C['childNum'] = count($C['subid']);
        $C['subids'] = implode(',', (array) $C['subid']);
        $C['dirs'] = self::dirs($C['id']);

        self::pic($C);
        self::parent($C);
        self::nav($C);

        AppsMeta::data('node', $C['id'],$C);

        //node 应用信息
        $C['SAPPID'] = iCMS_APP_NODE;
        $appData = Apps::getData($C['SAPPID']);
        $C['SAPP'] = Apps::getDataLite($appData);
        $appData['fields'] && FormerApp::data($C['id'], $appData, 'node', $C, null, $C);
        //node 绑定的应用
        try {
            $C['appid'] && $C['APP'] = Apps::getData($C['appid']);
        } catch (\FalseEx $fex) {
            $C['APP'] = [];
        }

        empty($C['rule']) && $C['rule'] = [];
        empty($C['template']) && $C['template'] = [];
        empty($C['config']) && $C['config'] = [];

        return $C;
    }

    public static function dirs($id = '0')
    {
        $dir = '';
        $C = NodeCache::getId($id);
        $C['rootid'] && $dir .= self::dirs($C['rootid']);
        $dir .= '/' . $C['dir'];
        return $dir;
    }

    public static function pic(&$C)
    {
        $C['pic']  = is_array($C['pic'])  ? $C['pic']  : FilesPic::getArray($C['pic']);
        $C['bpic'] = is_array($C['bpic']) ? $C['bpic'] : FilesPic::getArray($C['bpic']);
        $C['mpic'] = is_array($C['mpic']) ? $C['mpic'] : FilesPic::getArray($C['mpic']);
        $C['spic'] = is_array($C['spic']) ? $C['spic'] : FilesPic::getArray($C['spic']);
    }

    public static function parent(&$C)
    {
        if ($C['rootid']) {
            $C['parent'] = NodeCache::getId($C['rootid']);
        }
    }

    public static function nav(&$C)
    {
        $nav = '';
        $navArray = [];
        self::navArr($C, $navArray);
        krsort($navArray);
        foreach ((array) $navArray as $key => $value) {
            $nav .= "<li>
            <a href='{$value['url']}'>{$value['name']}</a>
            <span class=\"divider\">" . Lang::get('msg:nav_divider') . '</span>
            </li>';
        }
        $C['nav'] = $nav;
        $C['navArray'] = $navArray;
    }

    public static function navArr($C, &$navArray = [])
    {
        if ($C) {
            if($C['status']===1){
                $navArray[] = [
                    'name' => $C['name'],
                    'url'  => $C['iurl']['href'],
                ];                
            }
            if ($C['rootid']) {
                $rc = NodeCache::getId($C['rootid']);
                self::navArr($rc, $navArray);
            }
        }
    }
    public static function data(&$node)
    {
        self::url($node);
        if (defined('ADMINCP')) {
            $node['ACCESS'] = [
                'node'=> [
                    'add'   => AdmincpAuth::app('add') && AdmincpAuth::node($node['id'], 'a'),
                    'edit'  => AdmincpAuth::app('edit') && AdmincpAuth::node($node['id'], 'e'),
                    'delete'=> AdmincpAuth::app('delete') && AdmincpAuth::node($node['id'], 'd'),
                ],
                'content'=> [
                    'manage'=> AdmincpAuth::node($node['id'], 'cm'),
                    'add'   => AdmincpAuth::node($node['id'], 'ca'),
                    'edit'  => AdmincpAuth::node($node['id'], 'ce'),
                    'delete'=> AdmincpAuth::node($node['id'], 'cd'),
                ],
            ];
        }

        return $node;
    }
    public static function route(&$node)
    {
        if(empty($node)){
            return;
        }
        if ($call = self::$callback['route']) {
            return call_user_func_array($call, [&$node]);
        }
        
        $node['url'] = $node['outurl'];
        self::url($node);

        // if ($node && !$node['iDevice']) {
        //     if (!Adapter::$IS_IDENTITY_URL) {
        //         Adapter::route($node);
        //         // Adapter::route($node['iurl']);
        //         // Adapter::route($node['navArray']);
        //         $node['parent'] && self::route($node['parent']);
        //         $node['iDevice'] = true;
        //     }
        // }
    }

    // public static function get($node)
    // {
    //     $keyArray = ['sortnum', 'password', 'mode', 'domain', 'config', 'addtime'];
    //     foreach ($keyArray as $i => $key) {
    //         unset($node[$key]);
    //     }
    //     self::route($node);
    //     return $node;
    // }
}
