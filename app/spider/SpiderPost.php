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
    public static $callback = [];

    public static function get($id)
    {
        $key = 'spider:post:' . $id;
        $data = Cache::temp($key, function () use ($id) {
            $data = SpiderPostModel::get($id);
            return $data;
        });
        $data['post'] && self::data($data);
        return $data;
    }

    /**
     * field=value
     * aaa[kk]=vvv 设置数组
     * @name 设置主键
     */
    public static function data(&$arr)
    {
        $postArray = explode("\n", $arr['post']);
        $postArray = array_filter($postArray);
        foreach ($postArray as $key => $pstr) {
            if ($pstr[0] === '@') {
                $arr['primary'] = substr(trim($pstr), 1);
                continue;
            }
            list($pkey, $pval) = explode('=', $pstr);
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
        $output = [];
        if (is_array($rs)) {
            foreach ($rs as $post) {
                $output[$post['id']] = $post['name'];
                $selected = ($id == $post['id'] ? 'selected' : '');
                $opt .= sprintf(
                    '<option value="%s" %s>%s:%s[id:="%s"]</option>',
                    $post['id'],
                    $selected,
                    $post['name'],
                    $post['app'],
                    $post['id']
                );
            }
        }
        return $opt;
    }

    public static function commit($urlId = 0, $spo = null)
    {
        is_numeric($spo) && $spo = self::get($spo);
        Spider::$callback['result'] = [];
        if (Spider::$isShell) {
            SpiderTools::prints('使用规则[%s, poid=%s],内容将发布至[%s]应用', [$spo['name'], $spo['id'], $spo['app']], 's');
        }
        if (Request::isUrl($spo['fun'])) {
            return self::remotePost($urlId, $spo);
        } else {
            return self::callPost($urlId, $spo);
        }
    }

    public static function getClassName($method)
    {
        $agrs = (array)SpiderPost::$callback['agrs'];
        //aaaAdmincp::done_add
        $params = [];
        if (strpos($method, '::') !== false) {
            //aaaAdmincp::done_add(aa,bbb)
            list($className, $method) = explode('::', $method);
            if (strpos($method, '(') !== false) {
                list($method, $params) = explode('(', $method);
                $params = explode(',', trim($params, ')'));
                foreach ($params as $key => &$value) {
                    $v = strtolower($value);
                    $v == 'null' && $value = null;
                    $v == 'true' && $value = true;
                    $v == 'false' && $value = false;
                }
            }
        }
        $agrs = array_merge($agrs, $params);
        $prefix = null;
        if (strpos($method, '_') !== false) {
            list($prefix, $do) = explode('_', $method);
            $prefix = sprintf('%s_', rtrim($prefix));
        }
        return [$className, $method, $do, $prefix, $agrs];
    }


    public static function callPost($urlId, $spo)
    {
        list($className, $method, $do, $prefix, $agrs) = self::getClassName($spo['fun']);
        $spo['app'] && $className = Admincp::init($spo['app'], $do, $prefix);
        $admincp = iPHP::getInstance($className);

        if ($urlId === false) {
            //子采集发布不回调
        } else {
            Spider::setCallback($urlId, null, $admincp);
        }

        if (Spider::$isShell) {
            SpiderTools::prints('调用方法[%s::%s]完成发布操作', [$className, $method], 's');
        }
        try {
            self::setPost($admincp::$MODEL);
            call_user_func_array([$admincp, $method], [&$agrs]);
            return $admincp;
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            throw new FalseEx($msg);
        }
    }
    public static function setPost($MODEL)
    {
        $postData = $MODEL::postData();
        $rs = $_POST['rs']?:[];
        $pd = array_intersect_key($_POST, $postData);
        $rs = array_merge($rs, $pd);
        $_POST = array_diff_key($_POST, $rs);
        $_POST['rs'] = $rs;
        // var_dump($_POST);
        // exit;
    }
    public static function remotePost($urlId, $spo)
    {
        $url = $spo['fun'];

        if (Spider::$isShell) {
            SpiderTools::prints('执行远程发布 %s ', [$url], 's');
        }
        $json = self::postUrl($url, $_POST);
        $result = json_decode($json);
        if ($result) {
            if ($id = $result->id) {
                if ($urlId === false) {
                    //子采集发布不回调
                } else {
                    Spider::setCallback($urlId, $id);
                }
                return $result;
            } else {
                throw new FalseEx('远程发布失败');
            }
        } else {
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
        $options = [
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
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $responses = curl_exec($ch);
        curl_close($ch);
        return $responses;
    }
}
