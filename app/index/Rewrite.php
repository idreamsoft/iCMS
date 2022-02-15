<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class Rewrite
{
    public $URI = null;
    public $dir = null;
    public $ext = null;
    public $rewrite = false;
    public $request = null;
    public $path = null;

    public $EXTS = array(
        "png", "jpg", "jpeg", "gif", "bmp", "webp", "psd", "tif",
        "flv", "swf", "mkv", "avi", "rm", "rmvb", "mpeg", "mpg", "mp4",
        "ogg", "ogv", "mov", "wmv", "webm", "mp3", "wav", "mid", "amr",
        "rar", "zip", "tar", "gz", "7z", "bz2", "cab", "iso",
        "doc", "docx", "xls", "xlsx", "ppt", "pptx", "pdf", "txt", "md", "xml",
        "apk", "ipa",
        "css", "js",
    );
    public function __construct()
    {
        $route = Config::get('route');
        $this->dir = $route['dir'];
        $this->ext = $route['ext'];
        $this->rewrite = (bool)$route['rewrite'];
        $this->request = parse_url(iPHP_REQUEST_URI);
        $this->path = $this->request['path'];
    }

    public function run()
    {
        $this->exclude();

        $this->rewrite && $this->routing();
        if (empty($this->URI) && $this->pregIndex()) return;

        empty($this->URI) && $flag = $this->rules(); //应用路由匹配
        empty($this->URI) && $flag = $this->pregClink(); //文章类自定义链接

        if (!($this->rewrite || $flag)) return;

        if ($this->URI) {
            $this->make();
            $name  = basename(parse_url($this->URI, PHP_URL_PATH), '.php');
            $name == 'api' ? iCMS::API() : iCMS::run($name);
            exit;
        } else {
            if (iPHP_DEBUG && iPHP_TPL_DEBUG) {
                throw new sException(sprintf(
                    "未找到与链接<b>%s</b>相匹配的规则.",
                    $this->path
                ));
            } else {
                Request::status(404, $this->path . ',rewrite 404');
            }
        }
    }
    public function exclude()
    {
        $ext  = pathinfo($this->path, PATHINFO_EXTENSION);
        $ext  = strtolower($ext);
        if (in_array($ext, $this->EXTS)) {
            if (preg_match('@.*?/avatar/\d+/\d+/\d+.jpg@i', $this->path)) {
                Helper::redirect(iCMS_PUBLIC_URL . '/img/avatar.jpg');
            }
            if (preg_match('@.*?/coverpic/\d+/\d+/\d+.jpg@i', $this->path)) {
                Helper::redirect(iCMS_PUBLIC_URL . '/img/coverpic.jpg');
            }
            Request::status(404, $this->path . ',rewrite 404');
            exit;
        }
    }
    public function routing()
    {
        $routing = Config::get('routing');
        $routing0 = $routing1 = array();
        foreach ($routing as $key => $value) {
            if (strpos($value[0], '{') === false && strpos($value[0], '}') === false) {
                $routing0[$value[0]] = $value[1];
            } else {
                if (stripos($key, 'uid:') === 0) {
                    $url = rtrim(Route::$config['user'], '/') . $value[0];
                    $value[0] = parse_url($url, PHP_URL_PATH);
                }
                $routing1[$key] = $value;
            }
        }
        //一般路由匹配
        empty($this->URI) && $this->route0($routing0);
        empty($this->URI) && $this->route1($routing1); //带{}类路由匹配
    }
    public function rules()
    {
        $rs    = NodeCache::get('rules');
        $rules = array();
        $result = array();
        foreach ((array)$rs as $rule) {
            if ($rule) foreach ($rule as $k => $v) {
                $v = str_replace('{EXT}', $this->ext, $v);
                $v = rtrim($this->dir, '/') . '/' . ltrim($v, '/');
                if ($k == 'index' || $k == 'list') {
                    $k = 'node';
                } else {
                    $pi = pathinfo($v);
                    if (substr($v, -1) == '/' || empty($pi['extension'])) {
                        $pi['extension'] = ltrim($this->ext, '.');
                        $pi['basename'] .= '/index_{P}' . $this->ext;
                    } else {
                        $pi['basename'] = $pi['filename'] . '_{P}.' . $pi['extension'];
                    }
                    $pv = rtrim($pi['dirname'], '/') . '/' . $pi['basename'];
                    $rules[$k][$pv] = strlen($pv);
                }
                $rules[$k][$v] = strlen($v);
            }
        }
        if (!$rules) return false;

        if ($rules) foreach ($rules as $key => $rvalue) {
            foreach ($rvalue as $k => $v) {
                $result[] = $this->builder($key, $k);
            }
        }
        if (!$result) return false;

        usort($result, function ($a, $b) {
            $al = strlen($a[0]);
            $bl = strlen($b[0]);
            if ($al  ==  $bl) {
                return  0;
            }
            return ($al  <  $bl) ? -1  :  1;
        });
        krsort($result);

        foreach ($result as $key => $value) {
            preg_match_all('@' . $value[0] . '@i', $this->path, $matches);
            if ($matches[0]) {
                $this->URI = preg_replace('@' . $value[0] . '@i', $value[1], $this->path);
                return true;
            }
        }
    }
    public function pregClink()
    {
        if (preg_match('@' . $this->dir . '.*?' . preg_quote($this->ext) . '@', $this->path)) {
            $clink = '[' . ltrim($this->path, '/') . ']';
            //如果太多请求 可以把检测移移出以免影响性能
            $check = Article::check($clink, 0, 'clink');
            $check && $this->URI = Route::make(compact('clink'), 'article.php');
            if ($this->URI) return true;
        }
    }
    public function pregIndex()
    {
        if (preg_match('@^/index_(\d+)' . preg_quote($this->ext) . '@', $this->path, $match)) {
            $_GET['page'] = intval($match[1]);
            return true;
        }
        if (strpos($this->path, 'api.php') !== false) {
            return true;
        }
    }
    public function route0($routeArray)
    {
        $path = $this->path;
        if ($this->URI = $routeArray[$path]) {
            return;
        } else {
            //匹配 /user/aa/bb
            if (preg_match('@^/\w+/\w+/\w+$@', $path)) {
                list($app, $do, $s) = explode('/', ltrim($path, '/'));
                $_app = iApp::get($app, $sub);
                if (iCMS::$config['apps'][$_app]) {
                    $query = compact('app', 'do');
                    $s && $query['s'] = $s;
                    $this->URI = Route::make($query, 'api.php');
                }
            }
        }
    }
    public function route1($routeArray)
    {
        $path = $this->path;
        foreach ($routeArray as $key => $value) {
            $uri = $value[0];
            $replacement = '(?<\\1>\d+)';
            if (strpos($value[0], 'id}') === false) {
                $replacement = '(?<\\1>\w+)';
            }
            $pattern = preg_replace('/\{(\w+)\}/i', $replacement, $uri);
            preg_quote($pattern, '@');
            preg_match_all('@' . $pattern . '@i', $path, $matches);
            if ($matches[1][0]) {
                $this->URI =  $value[1];
                foreach ($matches as $mkey => $mval) {
                    // var_dump($mkey,$mval);
                    $this->URI = str_replace('{' . $mkey . '}', $mval[0], $this->URI);
                }
            }
        }
    }
    public function make()
    {
        $rq = parse_url($this->URI, PHP_URL_QUERY);
        parse_str($rq, $reqs);
        parse_str($this->request['query'], $query);
        $_GET = array_merge($reqs, $query);
        Security::addslash($_GET);
        Waf::check($_GET);
    }

    public function builder($key, $value)
    {
        // var_dump($value);
        preg_match_all("/\{(.*?)\}/", $value, $matches);
        $url = preg_replace("/\{.*?\}/", "%s", $value);
        $urlRule = Route::getRuleEtc();
        $regular   = $value;
        $query = array();
        $app    = $key;
        if (strpos($key, ':') !== false) {
            list($app, $do) = explode(':', $key);
            $do && $query[] = 'do=' . $do;
        }
        $i = 1;
        $param[] = $url;

        foreach ($matches[1] as $k => $v) {
            if (stripos($v, 'Hash@') !== false) {
                list($v, $t) = explode('@', $v);
            }
            if (stripos($v, 'AUTHID@') !== false) {
                list($v, $tt) = explode('@', $v);
            }
            $reg = $urlRule[$v]['regular'];
            $reg = str_replace("/", "@@", $reg);
            $param[] = $reg;
            if ($urlRule[$v]['query']) {
                $query[] = sprintf('%s=$%d', $urlRule[$v]['query'], $i);
                $i++;
            }
        }
        // var_dump($param,$query);
        $regular = call_user_func_array('sprintf', $param);
        $rewrite = $app . '.php?' . implode('&', $query);
        if ($p = Route::$config['iurl'][$key]['page']) {
            $rewrite = str_replace('page=', $p . '=', $rewrite);
        }
        return array($regular, $rewrite);
    }
}
