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
    public static $config   = null;
    public static $callback   = array();

    public static $domain       = null;
    public static $route_url   = null;

    public static $device       = array();
    public static $device_name  = null;
    public static $device_tpl   = null;
    public static $device_index = null;

    public static $IS_MOBILE = false;
    public static $IS_IDENTITY_URL = true;

    public static function init($config = null, $common = array())
    {
        self::$route_url = iPHP_URL;
        self::$config = $config;

        if (isset(self::$config['callback']['init'])) {
            iPHP::callback(self::$config['callback']['init'], array(&self::$config));
        }
        if (empty(self::$device)) {
            //有设置其它设备
            if (self::$config['device']) {
                Request::param('device') && $device = self::check('device');   //判断指定设备
                empty($device) && $device = self::check('domain');   //无指定设备 判断域名模板
                empty($device) && $device = self::check('agent');       //无指定域名 判断USER_AGENT
                $device && list($device_name, $device_tpl, $device_index, self::$domain) = $device;
            }

            self::$IS_MOBILE = false;
            if (empty($device_tpl)) {
                //检查是否移动设备 USER_AGENT 或者 域名
                $is_m_domain = (self::$config['mobile']['domain'] == iPHP_REQUEST_HOST
                    &&
                    self::$config['mobile']['domain'] != self::$route_url);
                if (self::agent(self::$config['mobile']['agent']) || $is_m_domain) {
                    self::$IS_MOBILE = true;
                    $device_name  = 'mobile';
                    $device_tpl   = self::$config['mobile']['tpl'];
                    $device_index = self::$config['mobile']['index'];
                    self::$domain = self::$config['mobile']['domain'];
                }
            }

            if (empty($device_tpl)) {
                $device_name  = 'desktop';
                $device_tpl   = self::$config['desktop']['tpl'];
                $device_index = self::$config['desktop']['index'];
                self::$domain = self::$route_url;
            }
        } else {
            list($device_name, $device_tpl, $device_index, self::$domain, self::$IS_MOBILE) = self::$device;
        }
        self::$device_name  = $device_name;
        self::$device_tpl   = $device_tpl;
        self::$device_index = $device_index;

        self::$IS_IDENTITY_URL = (self::$domain == self::$route_url);

        // define('iPHP_DEFAULT_TPL', $device_tpl);
        // define('iPHP_INDEX_TPL', $device_index);
        // define('iPHP_DEVICE', $device_name);

        // return array($device_name, $device_tpl,$device_index);

        // self::$IS_IDENTITY_URL OR self::route($config['route']);
        // self::$IS_IDENTITY_URL OR self::route($config['FS']);

        $common['redirect'] && self::redirect();
    }
    /**
     * [set description]
     * @param [type]  $domain       [访问域名]
     * @param [type]  $device_tpl   [设备模板]
     * @param [type]  $device_name  [设备名称]
     * @param [type]  $device_index [首页模板]
     * @param boolean $IS_MOBILE    [是否标识移动端]
     */
    public static function set($domain, $device_tpl, $device_name, $device_index = 'index.htm', $IS_MOBILE = false)
    {
        self::$device = array($device_name, $device_tpl, $device_index, $domain, $IS_MOBILE);
    }
    public static function output(&$content)
    {
        if (!self::$IS_IDENTITY_URL) {
            // var_dump(self::$domain,self::$route_url);
            // var_dump($content);
            $content = str_replace(self::$route_url, self::$domain, $content);
        }
    }
    public static function identity(&$array)
    {
        self::$IS_IDENTITY_URL or self::route($array);
    }
    public static function domain(&$domain = array())
    {
        if (self::$config['desktop']['domain']) {
            $domain['desktop'] = $domain['durl'] = self::$config['desktop']['domain'];
        }
        if (self::$config['mobile']['domain']) {
            $domain['mobile'] = $domain['murl'] = self::$config['mobile']['domain'];
        }
        if (self::$config['device']) {
            foreach (self::$config['device'] as $key => $value) {
                if ($value['domain']) {
                    $name = trim($value['name']);
                    $domain[$name] = $value['domain'];
                }
            }
        }
        return $domain;
    }
    public static function route(&$route)
    {
        $callback = self::$config['callback']['route'] ?: function (&$item, $key) {
            $item = str_replace(self::$route_url, self::$domain, $item);
        };
        array_walk_recursive($route, $callback);
        return $route;
    }
    //所有设备网址
    public static function urls($array = null)
    {
        $urls = array();
        if ($array) {
            $array = (array)$array;
            $iurl = array(
                'url' => $array['href']
            );
            $array['pageurl'] && $iurl['pageurl'] = $array['pageurl'];

            if (self::$config['desktop']['domain']) {
                $urls['desktop'] = str_replace(self::$domain, self::$config['desktop']['domain'], $iurl);
            }
            if (self::$config['mobile']['domain']) {
                $urls['mobile'] = str_replace(self::$domain, self::$config['mobile']['domain'], $iurl);
            }
            if (self::$config['device']) foreach (self::$config['device'] as $key => $value) {
                if ($value['domain']) {
                    $name = trim($value['name']);
                    $urls[$name] = str_replace(self::$domain, $value['domain'], $iurl);
                }
            }
        }
        return $urls;
    }

    private static function redirect()
    {
        if (defined('iPHP_DEVICE_REDIRECT') || iPHP_SHELL) return false;

        if (stripos(iPHP_REQUEST_URL, self::$domain) === false) {
            $redirect_url = str_replace(iPHP_REQUEST_HOST, self::$domain, iPHP_REQUEST_URL);
            header("Expires:1 January, 1970 00:00:01 GMT");
            header("Cache-Control: no-cache");
            header("Pragma: no-cache");
            header("X-iPHP-DOMAIN: " . self::$domain);
            header("X-REDIRECT-REF: " . iPHP_REQUEST_URL);
            header("X-REDIRECT-URL: " . $redirect_url);
            Request::status(301);
            Helper::redirect($redirect_url);
        }
    }
    private static function check($flag = false)
    {
        foreach ((array) self::$config['device'] as $key => $device) {
            if ($device['tpl']) {
                $check = false;
                if ($flag == 'agent') {
                    $device['agent'] && $check = self::agent($device['agent']);
                } elseif ($flag == 'device') {
                    $_device = Request::param('device');
                    if ($device['agent'] == $_device || $device['name'] == $_device) {
                        $check = true;
                    }
                } elseif ($flag == 'domain') {
                    if ($device['domain'] == iPHP_REQUEST_HOST && empty($device['agent'])) {
                        $check = true;
                    }
                }
                if ($check) {
                    return array($device['name'], $device['tpl'], $device['index'], $device['domain']);
                }
            }
        }
    }
    private static function agent($user_agent)
    {
        $user_agent = str_replace(',', '|', preg_quote($user_agent, '/'));
        return ($user_agent && preg_match('@' . $user_agent . '@i', $_SERVER["HTTP_USER_AGENT"]));
    }
}
