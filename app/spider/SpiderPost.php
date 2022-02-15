<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');
// class SpiderPostModel extends Model
// {
// }
class SpiderPost
{
    public static $callback = array();
    public static function get($id)
    {
        $key = 'spider:post:' . $id;
        $data = $GLOBALS[$key];
        if (!isset($GLOBALS[$key])) {
            $data = SpiderPostModel::get($id);
            $GLOBALS[$key] = $data;
        }
        $data['post'] && self::data($data);
        return $data;
    }
    public static function data(&$arr)
    {
        $postArray = explode("\n", $arr['post']);
        $postArray = array_filter($postArray);
        foreach ($postArray as $key => $pstr) {
            if ($pstr[0] === '@') {
                $arr['primary'] = substr(trim($pstr), 1);
                continue;
            }
            list($pkey, $pval) = explode("=", $pstr);
            if (strpos($pkey, '[') !== false && strpos($pkey, ']') !== false) {
                preg_match('/(.+)\[(.+)\]/', $pkey, $match);
                $_POST[$match[1]][$match[2]] = trim($pval);
            } else {
                $_POST[$pkey] = trim($pval);
            }
        }
        return $arr;
    }
    public static function option($id = 0, &$output = null)
    {
        $rs = SpiderPostModel::select();
        $opt = '';
        $output = array();
        if (is_array($rs)) foreach ($rs as $post) {
            $output[$post['id']] = $post['name'];
            $selected = ($id == $post['id'] ? "selected" : '');
            $opt .= sprintf(
                '<option value="%s" %s>%s:%s[id:="%s"]</option>',
                $post['id'],
                $selected,
                $post['name'],
                $post['app'],
                $post['id']
            );
        }
        return $opt;
    }
    public static function commit($urlId = 0, $spo = null)
    {
        is_numeric($spo) && $spo = self::get($spo);
        Spider::$callback['result'] = [];
        if (Spider::$isShell) {
            SpiderTools::prints('使用%s[poid=%s]发布规则,发布到[%s]应用', [$spo['name'], $spo['id'], $spo['app']], 's');
        }
        $method = $spo['fun'];
        if (Request::isUrl($method)) {
            self::remotePost($method, $urlId);
        } else {
            self::callPost($method, $urlId, $spo);
        }
    }

    public static function getClassName($method, $app)
    {
        $agrs = SpiderPost::$callback['agrs'];

        if (strpos($method, '::') === false) {
            $className = sprintf("%sAdmincp", ucfirst($app));
        } else {
            list($className, $method) = explode('::', $method);
            if (strpos($method, '(') !== false) {
                list($method, $agrs) = explode('(', $method);
                $agrs = explode(',', trim($agrs, ')'));
                foreach ($agrs as $key => &$value) {
                    $v = strtolower($value);
                    $v == "null" && $value = null;
                    $v == "true" && $value = true;
                    $v == "false" && $value = false;
                }
            }
        }
        return [$className, $method, $agrs];
    }
    public static function callPost($method, $urlId, $spo)
    {
        list($className, $method, $agrs) = self::getClassName($method, $spo['app']);
        $prefix = null;
        if(strpos($method,'_')!==false){
            list($prefix, $do) = explode('_', $method);
            $prefix = sprintf("%s_",rtrim($prefix));
        }

        $className = Admincp::init($spo['app'], $do, $prefix);
        $admincp = new $className;

        if ($urlId === false) {
            //子采集发布不回调
        } else {
            Spider::setCallback($urlId, null, $admincp);
        }
        $rc = new ReflectionClass($className);
        if (!$rc->hasMethod($method)) {
            throw new FalseEx(sprintf("发布规则设置出错，%s::%s 方法不存在", $className, $method), 0);
        }
        if (Spider::$isShell) {
            SpiderTools::prints('发布执行 %s::%s ', [$className, $method], 's');
        }
        $func = array($admincp, $method);
        try {
            call_user_func_array($func, (array) $agrs);
        } catch (\AdmincpException $ex) {
            $msg = $ex->getMessage();
            throw new FalseEx($msg);
        }
    }
    public static function remotePost($url, $urlId)
    {
        if (Spider::$isShell) {
            SpiderTools::prints('执行远程发布 %s ', [$url], 's');
        }
        $json = self::postUrl($url, $_POST);
        $result = json_decode($json, true);
        if($result){
            if ($id = $result['id']) {
                if ($urlId === false) {
                    //子采集发布不回调
                } else {
                    Spider::setCallback($urlId, $id);
                }
            }else{
                throw new FalseEx('远程发布失败');
            }
        }else{
            throw new sException('远程发布失败:返回格式错误(非json格)');
        }

    }
    public static function postUrl($url, $data)
    {
        if (!Request::isUrl($url, true)) {
            if (Spider::$isTest) {
                echo "<b>{$url} 请求错误:非正常URL格式,因安全问题只允许提交到 http:// 或 https:// 开头的链接</b>";
            }
            return false;
        }
        is_array($data) && $data = http_build_query($data);
        $options = array(
            CURLOPT_URL                  => $url,
            CURLOPT_REFERER              => $_SERVER['HTTP_REFERER'],
            CURLOPT_USERAGENT            => $_SERVER['HTTP_USER_AGENT'],
            CURLOPT_POSTFIELDS           => $data,
            // CURLOPT_HTTPHEADER           => array(
            //     'Content-Type:application/x-www-form-urlencoded',
            //     'Content-Length:'.strlen($data),
            //     'Host: www.icmsdev.com'
            // ),
            CURLOPT_POST                 => 1,
            CURLOPT_TIMEOUT              => 10,
            CURLOPT_CONNECTTIMEOUT       => 10,
            CURLOPT_RETURNTRANSFER       => 1,
            CURLOPT_FAILONERROR          => 1,
            CURLOPT_HEADER               => false,
            CURLOPT_NOBODY               => false,
            CURLOPT_NOSIGNAL             => true,
            // CURLOPT_DNS_USE_GLOBAL_CACHE => true,
            // CURLOPT_DNS_CACHE_TIMEOUT    => 86400,
            CURLOPT_SSL_VERIFYPEER       => false,
            CURLOPT_SSL_VERIFYHOST       => false
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $responses = curl_exec($ch);
        curl_close($ch);
        return $responses;
    }
}
