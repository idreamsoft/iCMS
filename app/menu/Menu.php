<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class Menu
{
    const APP = 'menu';
    const APPID = iCMS_APP_MENU;

    public static $SET = [
        'nav'=> []
    ];
    public static $DATA = [];
    public static $HREFS = [];
    public static $TITLES = [];
    public static $callback = [];
    public static $url = null;

    const CACHE_DATA_KEY = iPHP_APP_SITE . '/menu/data';
    const CACHE_HREF_KEY = iPHP_APP_SITE . '/menu/hrefs';

    public static function init()
    {
        self::get(1);
    }

    // public static function set($d = 'manage', $a = null)
    // {
    //     $a === null && $a = Admincp::$APP_NAME;
    //     self::$url = Admincp::url([$a, $d]);
    // }
    // public static function active($d = 'manage', $a = null)
    // {
    //     $a === null && $a = Admincp::$APP_NAME;
    //     self::$SET['nav']['link'] = Admincp::url([$a, $d]);
    // }
    public static function setData($k, $v)
    {
        self::$SET[$k] = $v;
        return new self();
    }

    public static function makeJson($apps, $menuData = null)
    {
        $menuData === null && $menuData = Request::post('menuData');
        if (is_array($menuData)) {
            MenuHelper::vars($menuData, $apps);
            MenuHelper::sortData($menuData);
        }
        if ($menuJson = Request::post('menuJson')) {
            $menuJson = AppsHelper::replace($menuJson, $apps);
            $menuData = json_decode($menuJson, true);
        }

        if ($menuData) {
            $app = null;
            if ($apps['apptype'] == Apps::CONTENT_TYPE) {
                $app = 'content';
                $name = 'menu/admincp.' . $apps['app'];
            } else {
                $name = 'menu/admincp';
            }
            $app && Etc::set($app, $name, $menuData);
        }
    }

    public static function get($flag = false)
    {
        if ($flag) {
            return Menu::cache();
        }
        $Cache = Cache::file();
        self::$DATA = $Cache->get(self::CACHE_DATA_KEY);
        self::$HREFS = $Cache->get(self::CACHE_HREF_KEY);
        if (empty(self::$DATA) || empty(self::$HREFS)) {
            Menu::cache();
        }
    }

    public static function cache()
    {
        $variable = [];
        $rs = Apps::getMenuArray();
        $admincp = [];
        foreach ($rs as $app) {
            $result = MenuHelper::get($app);
            if ($app['app'] == 'admincp') {
                $admincp = $result;
            }else{
                $variable = array_replace_recursive($variable, $result);
            }
        }
        $variable = array_replace_recursive($variable, $admincp);
        self::$HREFS = [];
        self::$TITLES = [];
        if ($variable) {
            // MenuHelper::itemUnique($variable);
            MenuHelper::itemSort($variable);

            self::itemData($variable);
            self::$DATA = $variable;
            unset($variable);
            $Cache = Cache::file();
            $Cache->add(self::CACHE_DATA_KEY, self::$DATA, 0);
            // self::$HREFS = array_map('array_filter', self::$HREFS);
            $HREFS = [];
            if (self::$HREFS) {
                foreach (self::$HREFS as $key => $value) {
                    $HREFS[$key] = array_filter($value);
                }
            }
            self::$HREFS = $HREFS;
            unset($HREFS);
            $Cache->add(self::CACHE_HREF_KEY, self::$HREFS, 0);
            // put_php_file(__DIR__ . '/menu.array.php', var_export(self::$DATA, true));
            // $iCache->add(iPHP_APP_SITE.'/menu/caption',$caption,0);
        }
    }

    public static function itemData($variable, $id = null)
    {
        // $array = array();
        foreach ($variable as $value) {
            // $id = $value['id'];
            if(is_array($value)){
                empty($id) && $id = $value['id'];
                if ($value['href']) {
                    self::$HREFS[$value['href']][] = $id;
                    self::$TITLES[$value['href']] = $value['caption'];
                } else {
                    self::$HREFS[$value['id']][] = $id;
                }
                if ($value['children']) {
                    self::itemData($value['children'], $value['id']);
                }
            }
        }
    }

    public static function url($a)
    {
        $a['href'] && $url = Admincp::url($a['href']);
        $a['href'] == 'ADMINCP_URL' && $url = ADMINCP_URL;
        $a['href'] or $url = 'javascript:;';
        Request::isUrl($a['href']) && $url = $a['href'];
        return $url;
    }

    public static $nav_item_active = '';
    public static $nav_link_active = '';

    public static function navPaths($path, $array, $id, &$navArray)
    {
        $_array = $array[$id];
        $_array && $array = $_array;
        if (isset($array['children'])) {
            $navArray[] = self::arr($array);
            return self::navPaths($path, $array['children'], $id, $navArray);
        }
        if ($array) {
            foreach ($array as $idx => $value) {
                if (isset($value['children'])) {
                    $navArray[] = self::arr($value);
                    self::navPaths($path, $value['children'], $id, $navArray);
                } else {
                    $href = $value['href'];
                    if ($path == $href || ($href && strstr($href, $path))) {
                        $navArray[] = self::arr($value);
                        return true;
                    }
                }
            }
        }
    }
    public static function findMenuAncestors($menu, $href, &$path = []) {
        $href = ltrim($href, '/');
        foreach ($menu as $key => $item) {
            // 如果当前项是数组并且有children属性
            if (is_array($item) && isset($item['children'])) {
                // 将当前节点ID添加到路径中
                $path[] = $item['id'];
                
                // 递归检查children
                if (self::findMenuAncestorsInChildren($item['children'], $href, $path)) {
                    return true;
                }
                
                // 如果在这个分支中没有找到目标，移除当前节点
                array_pop($path);
            }
        }
        return false;
    }
    
    public static function findMenuAncestorsInChildren($children, $href, &$path = []) {
        foreach ($children as $child) {
            // 检查当前项是否是目标链接
            if (isset($child['href']) && ltrim($child['href'], '/') === $href) {
                return true;
            }
            
            // 如果当前项有children，递归检查
            if (isset($child['children'])) {
                $path[] = $child['id'];
                if (self::findMenuAncestorsInChildren($child['children'], $href, $path)) {
                    return true;
                }
                array_pop($path);
            }
        }
        return false;
    }

    public static function getNav($app = null, $path = null)
    {
        // $app = Admincp::$APP_NAME;
        $app = $app?:(self::$SET['getNav.APP'] ?: Admincp::$APP);
        $path = $path ?: Admincp::urlPath();
        $appMenuArray = MenuHelper::app($app);

        $navArray = [];
        if ($appMenuArray && is_array($appMenuArray)) {
            self::findMenuAncestors($appMenuArray, $path,$result);
            $fkey = $result[0];
            $mArray = $appMenuArray[$fkey];
            $navArray = [self::arr(self::$DATA[$fkey])];
        }
        $hrefkey = self::findHref();
        // var_dump($hrefkey);
        // if (!$fkey) {
        //     $mArray = self::$DATA[$hrefkey];
        //     $navArray = [self::arr(self::$DATA[$hrefkey])];
        // }
        // var_dump($mArray['children']);
        // array_walk($mArray['children'], function ($a, $b) {
        //     var_dump($a, $b);
        // });
        self::navPaths($path, $mArray['children'], $hrefkey, $navArray);

        // if (self::$SET['nav']['parent']) {
        //     $id = self::$SET['nav']['id'];
        //     var_dump(self::$SET['nav']);
        //     $navArray[] = self::arr(self::$DATA[$id]);
        // }
        self::$SET['breadcrumb'] && $navArray[] = self::$SET['breadcrumb'];
        if($navArray){
            $navArray = array_filter($navArray);
            $last = (array)end($navArray);
            $navlink = self::$SET['nav']['link'] ?: $last['url'];
        }
        return [$navArray, $last, $navlink];
    }

    public static function arr($item)
    {
        if (!$item['caption']) {
            return null;
        }
        return [
            'name'  => Lang::get($item['caption']),
            'title' => $item['title'],
            'url'   => self::url($item)
        ];
    }

    public static function findHref($url = null, $array = null)
    {
        $url === null or self::$url = $url;
        empty($url) && self::$url = Admincp::urlPath();
        $path = str_replace(ADMINCP_URL . '/', '', self::$url);

        empty($array) && $array = self::$HREFS;
        $value = $array[$path];
        if ($value) {
            return $value[0];
        }
        foreach ($array as $href => $value) {
            if (strstr($href, $path)) {
                return $value[0];
            }
        }
    }

    public static function navTabs($app)
    {
        $menuArray = current(MenuHelper::app($app));
        if ($menuArray['id'] == $app) {
            $array = $menuArray['children'];
        } elseif ($menuArray['children']) {
            $array = $menuArray['children'][$app]['children'];
        }
        $tabs = '';
        $uri = Admincp::urlPath();
        if ($array) {
            foreach ((array) $array as $node) {
                if ($node['caption'] == '-') {
                    continue;
                }
                $tabs .= sprintf(
                    '<li class="nav-item"><a class="nav-link %s" href="%s">%s</a></li>',
                    ($uri == $node['href'] ? 'active' : 'js-tabs-link'),
                    self::url($node),
                    Lang::get($node['caption'])
                );
            }
        }
        return $tabs;
    }

    // public static function findUrl($data = null, $url = null)
    // {
    //     $url = Admincp::urlPath($url);
    //     foreach ($data as $item) {
    //         if ($item['href']) {
    //             $murl = 'app=' . $item['href'];
    //             if ($url == $murl) {
    //                 return $item;
    //             } else {
    //                 $a1 = parse_url_qs($url);
    //                 // ksort($a1);
    //                 // var_dump(http_build_query($a1));
    //                 $a2 = parse_url_qs($murl);
    //                 // ksort($a2);
    //                 // var_dump(http_build_query($a2));
    //                 if ($a1['app'] == $a2['app']) {
    //                     if ((empty($a1['do']) && in_array($a2['do'], array(iPHP_APP, 'manage'))) ||
    //                         (empty($a2['do']) && in_array($a1['do'], array(iPHP_APP, 'manage'))) ||
    //                         ($a1['do'] == $a2['do'])
    //                     ) {
    //                         return $item;
    //                     }
    //                 }
    //             }
    //         }
    //     }
    // }
    public static function buildNav($key = null)
    {
        is_null($key) && $key = Request::sget('menu');
        if ($key) {
            self::$nav_item_active = $key;
            $node = self::$DATA[$key];
            $node && $nav_html = self::navNode($node);
        } else {
            $nav_html = self::buildNavArray(self::$DATA);
        }
        return $nav_html;
    }

    public static function buildNavArray($nav_array, $level = 0)
    {
        $nav_html = '';
        foreach ((array) $nav_array as $node) {
            $nav_html .= self::navNode($node, $level);
        }
        return $nav_html;
    }

    public static function navNode($node, $level = 0)
    {
        if(empty($node)){
            return;
        }
        if (!self::access($node)) {
            return false;
        }

        if (empty($node['caption'])) {
            return;
        }
        if ($node['caption'] == '-') {
            return '<li class="nav-main-divider"></li>';
        }
        self::nodeArray($node);
        // Get all vital link info
        $link_name = sprintf(
            '<span class="nav-main-link-name">%s</span>',
            Lang::get($node['caption'])
        );
        isset($node['icon']) && $link_icon = sprintf(
            '<i class="nav-main-link-icon %s"></i>',
            $node['icon']
        );

        if (empty($node['id'])) {
            // var_dump($node['href']);
            $node['id'] = str_replace('/', '-', ltrim($node['href'], '/'));
            // $link_array = parse_url_qs('app=' . $node['href']);
            // if ($node['href'][0] == '/') {
            //
            // } else {
            //     $link_array = array_values($link_array);
            //     $node['id'] = implode('-', $link_array);
            // }
            // var_dump($link_array);
        }
        $sub_active = ($node['id'] && $node['id'] === self::$nav_item_active) ? true : false;
        ;
        $link_active = (isset($node['href']) && $node['href'] == self::$nav_link_active) ? true : false;

        // Set menu properties
        $attr = [];
        // $root && $attr['root'] = $root;
        $attr['class'] = ['nav-main-link'];
        $link_active && $attr['class'][] = 'active';
        if ($node['hasChild']) {
            $attr['data-toggle'] = 'submenu';
            $attr['aria-expanded'] = ($sub_active ? 'true' : 'false');
            $node['badge'] && $link_badge = sprintf(
                '<span class="nav-main-link-badge badge rounded-pill bg-%s">%d</span>',
                $node['badge'],
                count($node['children'])
            );
        }
        $link_attr = self::navLinkAttr($node, $attr);
        // Add the link
        $link = sprintf(
            '<a %s>%s</a>',
            $link_attr,
            $link_icon . $link_name . $link_badge
        );

        // If it is a submenu, call the function again
        if ($node['hasChild']) {
            $subNav = self::buildNavArray($node['children'], $level + 1);
            empty($node['id']) && $node['id'] = md5($subNav);
            $submenu = sprintf(
                '<ul class="nav-main-submenu" root="%s">%s</ul>',
                $node['id'],
                $subNav
            );
        }
        return sprintf(
            '<li id="Menu-%s" class="nav-main-item %s %s" data-sort="%d">%s</li>',
            $node['id'],
            ($sub_active ? 'open' : ''),
            'nav-main-level-' . $level,
            $node['sort'],
            $link . $submenu
        );
    }

    public static function nodeArray(&$node)
    {
        if ($node['icon'] && is_string($node['icon']) && strpos($node['icon'], ' ') === false) {
            $node['icon'] = 'fa fa-' . $node['icon'];
        }
        if ($node['icon'] == 'fa fa-pencil-square-alt') {
            $node['icon'] = 'si si-grid';
        }
        $node['icon'] .= ' fa-fw';
        $node['hasChild'] = isset($node['children']) && is_array($node['children']) ? true : false;
    }

    public static function navLinkAttr($node, $attr = [])
    {
        $attr['href'] = self::url($node);
        $node['target'] && $attr['target'] = $node['target'];
        $node['title'] && $attr['title'] = $node['title'];
        $node['class'] && $attr['class'][] = $node['class'];
        $node['ui'] && $attr['i'] = $node['ui'];
        // if ($node['data-bs-toggle'] == 'modal') {
        //     $attr['data-bs-target'] = '#iCMS-MODAL';
        //     if ($node['data-params']) {
        //         if (is_array($node['data-params'])) {
        //             $node['data-params'] = json_encode($node['data-params']);
        //         }
        //         $attr['data-params'] = $node['data-params'];
        //     }
        // }
        foreach ($node as $key => $value) {
            if (strpos($key, 'data-') === 0) {
                if (is_array($value)) {
                    $attr[$key] = json_encode($value);
                } else {
                    $attr[$key] = $value;
                }
            }
        }
        $node['attr'] && $attr = array_merge($attr, $node['attr']);

        if ($attr['data-bs-toggle'] == 'dropdown-submenu') {
            $attr['class'][] = 'dropdown-toggle';
            $attr['id'] = 'dropdown-' . $node['id'];
            $attr['aria-haspopup'] = 'true';
            $attr['aria-expanded'] = 'false';
            $attr['href'] = 'javascript:;';
        }
        if ($attr['data-bs-toggle'] == 'submenu' || $attr['data-toggle'] == 'submenu') {
            $attr['class'][] = 'nav-main-link-submenu';
            $attr['aria-haspopup'] = 'true';
        }
        if (strpos($attr['href'], '/') === 0) {
            $attr['target'] = 'main-iframe';
        }
        $prop = '';
        foreach ($attr as $key => $value) {
            is_array($value) && $value = implode(' ', $value);
            $value = str_replace('"', '&quot;', $value);
            $prop .= sprintf(' %s="%s"', $key, $value);
        }
        return $prop;
    }

    public static function dropdown_menu($array)
    {
        if ($array['children']) {
            print '<div class="dropdown-menu fs-sm" aria-labelledby="dropdown-' . $array['id'] . '">';
            foreach ($array['children'] as $node) {
                if (!self::access($node)) {
                    continue;
                }
                if (empty($node['caption'])) {
                    continue;
                }
                if ($node['caption'] == '-') {
                    print '<div class="dropdown-divider"></div>';
                    continue;
                }

                self::nodeArray($node);
                $attr = [];
                $attr['class'] = ['dropdown-item'];
                $node['hasChild'] && $attr['data-toggle'] = 'dropdown-submenu';
                $link_attr = self::navLinkAttr($node, $attr);
                $node['hasChild'] && print('<div class="dropdown-submenu">');
                printf(
                    '<a %s><i class="%s"></i> %s</a>',
                    $link_attr,
                    $node['icon'],
                    Lang::get($node['caption'])
                );
                $node['hasChild'] && self::dropdown_menu($node);
                $node['hasChild'] && print('</div>');
            }
            print '</div>';
        }
    }

    // public static function access_data($pk, $key)
    public static function getAccessData($app = null)
    {
        $data = Menu::$DATA;
        $app && $data = Menu::$DATA[$app]['children'];
        return self::access_data_recursive($data);
    }

    public static function access_data_recursive($data = [])
    {
        $data = array_map(function ($node) {
            if ($node['children']) {
                if (self::access($node)) {
                    $children = self::access_data_recursive($node['children']);
                    $children = array_filter($children);
                    $node['children'] = $children;
                    return $node;
                }
            } else {
                if (self::access($node)) {
                    return $node;
                }
            }
        }, (array)$data);
        return array_filter($data);
    }

    /**
     * 验证菜单权限，是否显示
     * @param  [array] $node [菜单数组]
     * @return [bool]       [false 无权限]
     * @return [bool]       [true 拥有权限]
     */
    public static function access($node)
    {
        if (isset($node['show']) && !$node['show']) {
            return false;
        }

        if (!is_callable(self::$callback['access'])) {
            return false;
        }
        return call_user_func_array(self::$callback['access'], [$node]);
    }

    public static function callfunc($array = null, $level = 1, $pid = 0)
    {
        $array === null && $array = self::$DATA;
        //self::$callback['mode'] 0字符1数组2二维数组
        $data = self::$callback['mode'] > 0 ? [] : '';

        if ($array) {
            foreach ($array as $key => $M) {
                $child = $M['children'] ? true : false;
                empty($M['id']) && $M['id'] = substr(md5(json_encode($M)), 8, 16);
                if (self::$callback['func'] && is_callable(self::$callback['func'])) {
                    $param = [$M, $level, $child, $pid];
                    self::$callback['param'] && $param = array_merge($param, self::$callback['param']);
                    $result = call_user_func_array(self::$callback['func'], $param);
                    if (!empty($result)) {
                        if (self::$callback['mode']) {
                            $data[] = $result;
                        } else {
                            $data .= $result;
                        }
                    }
                }
                //self::$callback['recursive'] = false 不递归,
                //回调方法 self::$callback['func'] 里自行处理
                if (self::$callback['recursive'] !== false) {
                    if ($child) {
                        $result = self::callfunc($M['children'], $level + 1, $M['id']);
                        if (!empty($result)) {
                            if (self::$callback['mode']) {
                                $data[] = $result;
                            } else {
                                $data .= $result;
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }
}
