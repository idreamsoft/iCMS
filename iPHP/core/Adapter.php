<?php

// namespace iPHP\core;
/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
/**
 * 多终端适配
 */
class Adapter
{
    public static $config = null;
    public static $callback = [];

    public static $domain = null;
    public static $device = [];
    public static $main_url = null;

    public static $IS_MOBILE = false;
    public static $IS_IDENTITY_URL = true;

    public static function init($config = null)
    {
        self::$config = &$config;
        if (isset($config['callback']['init'])) {
            iPHP::callback($config['callback']['init'], [&$config]);
        }
        self::$main_url = $config['desktop']['domain'];
        $device = self::$device;

        if (empty($device)) {
            //有设置其它设备
            self::$IS_MOBILE = false;

            if (isset($config['device'])) {
                $device = self::check('device');   //判断指定设备
                empty($device) && $device = self::check('domain');   //无指定设备 判断域名模板
                empty($device) && $device = self::check('agent');       //无指定域名 判断USER_AGENT
                if ($device) {
                    self::$IS_MOBILE = $device['mobile'] ? true : false;
                }
            }
            if (empty($device)) {
                //检查是否移动设备 USER_AGENT 或者 域名
                $is_m_domain = (
                    $config['mobile']['domain'] == iPHP_REQUEST_HOST
                    &&
                    $config['mobile']['domain'] != $config['desktop']['domain']
                );
                if (self::agent($config['mobile']['agent']) || $is_m_domain) {
                    self::$IS_MOBILE = true;
                    $device = $config['mobile'];
                }
            }
            if (empty($device)) {
                $device = self::$config['desktop'];
            }
        }
        self::$device = $device;
        self::$domain = $device['domain'];

        $config['redirect'] && self::redirect();
    }

    /**
     * set description
     * @param [type]  $domain       [访问域名]
     * @param [type]  $device_tpl   [设备模板]
     * @param [type]  $device_name  [设备名称]
     * @param [type]  $device_index [首页模板]
     * @param boolean $IS_MOBILE    [是否标识移动端]
     */
    public static function set(array $device)
    {
        self::$device = $device;
    }

    public static function output(&$content)
    {
        if (!self::$IS_IDENTITY_URL) {
            // var_dump(self::$domain,self::$main_url);
            // var_dump($content);
            $content = str_replace(self::$main_url, self::$domain, $content);
        }
    }

    public static function identity(&$array)
    {
        self::$IS_IDENTITY_URL or self::route($array);
    }

    public static function site()
    {
        $urls = [];
        if (self::$config['desktop']['domain']) {
            $urls['desktop'] = $urls['w'] = [];
            $urls['desktop']['url'] = $urls['w']['url'] = self::$config['desktop']['domain'];
        }
        if (self::$config['mobile']['domain']) {
            $urls['mobile'] = $urls['m'] = [];
            $urls['mobile']['url'] = $urls['m']['url'] = self::$config['mobile']['domain'];
        }
        if (self::$config['device']) {
            foreach (self::$config['device'] as $key => $value) {
                if ($value['domain']) {
                    $name = trim($value['name']);
                    $urls[$name] = ['url'=>$value['domain']];
                }
            }
        }
        return $urls;
    }

    public static function route(&$route)
    {
        $callback = self::$config['callback']['route'] ?: function (&$item, $key) {
            $item = str_replace(self::$main_url, self::$domain, $item);
        };
        array_walk_recursive($route, $callback);
        return $route;
    }

    //所有设备网址
    public static function replace($s, $r, $urls)
    {
        $s = rtrim($s, '/');
        $r = rtrim($r, '/');
        return str_replace($s, $r, $urls);
    }

    public static function urls($array = null, $domain = null, $devices = [])
    {
        $urls = [];
        if ($array) {
            $array = (array)$array;
            $iurl = [
                'url' => $array['href']
            ];
            $array['pageurl'] && $iurl['pageurl'] = $array['pageurl'];
            $domain or $domain = self::$domain;
            $domain = rtrim($domain, '/');
            if (self::$config['desktop']['domain']) {
                $urls['desktop'] = $urls['w'] = self::replace($domain, self::$config['desktop']['domain'], $iurl);
            }
            if (self::$config['mobile']['domain']) {
                $urls['mobile'] = $urls['m'] = self::replace($domain, self::$config['mobile']['domain'], $iurl);
            }
            // $devices or $devices = self::$config['device'];
            if (self::$config['device']) {
                foreach (self::$config['device'] as $key => $value) {
                    if ($value['domain']) {
                        $name = trim($value['name']);
                        $urls[$name] = self::replace($domain, $value['domain'], $iurl);
                    }
                }
            }
        }
        return $urls;
    }

    private static function redirect()
    {
        if (defined('iPHP_DEVICE_REDIRECT') || iPHP_SHELL) {
            return false;
        }
        if (stripos(iPHP_REQUEST_URL, self::$domain) !== 0) {
            // $request_host = parse_url(iPHP_REQUEST_URL, PHP_URL_HOST);
            // $host = parse_url(self::$domain, PHP_URL_HOST);
            // $redirect_url = str_replace($request_host, $host, iPHP_REQUEST_URL);
            $redirect_url = str_replace(iPHP_REQUEST_HOST, self::$domain, iPHP_REQUEST_URL);
            header('Expires:1 January, 1970 00:00:01 GMT');
            header('Cache-Control: no-cache');
            header('Pragma: no-cache');
            // header('X-iPHP-DOMAIN: ' . self::$domain);
            header('X-REDIRECT-REF: ' . iPHP_REQUEST_URL);
            header('X-REDIRECT-URL: ' . $redirect_url);
            // Request::status(301);
            Utils::jumpto($redirect_url);
        }
    }

    private static function check($flag = false)
    {
        foreach ((array) self::$config['device'] as $key => $device) {
            if ($device['tpl']) {
                $check = false;
                if ($flag == 'agent' && $device['agent']) {
                    $check = self::agent($device['agent']);
                } elseif ($flag == 'device') {
                    $_device = Session::get('device');
                    empty($_device) && $_device = Request::param('device');
                    if ($device['agent'] === $_device || $device['name'] === $_device) {
                        $check = true;
                    }
                } elseif ($flag == 'domain') {
                    if ($device['agent'] === 'DIR' && stripos(iPHP_REQUEST_URL, $device['domain']) === 0) {
                        $check = true;
                    } elseif (rtrim($device['domain'], '/') === iPHP_REQUEST_HOST && empty($device['agent'])) {
                        $device['domain'] = rtrim($device['domain'], '/');
                        $check = true;
                    }
                }
                if ($check) {
                    return $device;
                }
            }
        }
    }

    private static function agent($user_agent)
    {
        if ($user_agent && $_SERVER['HTTP_USER_AGENT']) {
            $user_agent = str_replace(',', '|', preg_quote($user_agent, '/'));
            return ($user_agent && preg_match('@' . $user_agent . '@i', $_SERVER['HTTP_USER_AGENT']));
        }
    }
}
