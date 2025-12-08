<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class IndexRewrite
{
    public $URI = null;
    public $dir = null;
    public $ext = null;
    public $rewrite = false;
    public $request = null;
    public $path = null;

    public $EXTS = [
        'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'psd', 'tif',
        'flv', 'swf', 'mkv', 'avi', 'rm', 'rmvb', 'mpeg', 'mpg', 'mp4',
        'ogg', 'ogv', 'mov', 'wmv', 'webm', 'mp3', 'wav', 'mid', 'amr',
        'rar', 'zip', 'tar', 'gz', '7z', 'bz2', 'cab', 'iso',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'txt', 'md', 'xml',
        'apk', 'ipa',
        'css', 'js',
    ];

    public function __construct($path)
    {
        $route = Config::get('route');
        $this->dir = '/';
        $this->ext = $route['ext'];
        $this->rewrite = (bool)$route['rewrite'];
        $this->request = parse_url(iPHP_REQUEST_URI);
        $this->path = $path;
    }

    public function run()
    {
        $this->exclude();
        //应用路由匹配
        $this->rewrite && $this->routing();
        //首页匹配
        if (empty($this->URI) && $this->index()) {
            return;
        }

        //内容/节点等链接匹配
        empty($this->URI) && $flag = $this->rules();
        //带[]的自定义链接
        empty($this->URI) && $flag = $this->clink();

        if (!($this->rewrite || $flag)) {
            $this->debug();
            return;
        }
        if ($this->URI) {
            $this->make();
            if (strpos($this->URI, iCMS_API_NAME) !== false) {
                return iCMS::api();
            } else {
                $path = parse_url($this->URI, PHP_URL_PATH);
                $app = pathinfo($path, PATHINFO_FILENAME);

                return iCMS::run($app);
            }
            exit;
        } else {
            $this->debug();
        }
    }

    public function debug()
    {
        if (iPHP_DEBUG || iPHP_TEMPLATE_DEBUG) {
            throw new sException(sprintf(
                '未找到与链接<b>%s</b>相匹配的规则.',
                $this->path
            ));
        } else {
            Utils::http404();
        }
    }

    public function exclude()
    {
        $ext = pathinfo($this->path, PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        if (in_array($ext, $this->EXTS)) {
            if (preg_match('@.*?/avatar/\d+/\d+/\d+.jpg@i', $this->path)) {
                Utils::jumpto(iCMS_PUBLIC_URL . '/assets/img/avatar.jpg');
            }
            if (preg_match('@.*?/coverpic/\d+/\d+/\d+.jpg@i', $this->path)) {
                Utils::jumpto(iCMS_PUBLIC_URL . '/assets/img/coverpic.jpg');
            }
            Request::status(404, $this->path);
            // Utils::http404();
            exit;
        }
    }

    public function routing()
    {
        $routing = Config::get('route.routing');
        $routing = str_replace('{API}', iCMS_API_NAME, $routing);
        $routing0 = $routing1 = [];

        foreach ($routing as $key => $value) {
            if (strpos($key, '{') === false && strpos($key, '}') === false) {
                $routing0[$key] = $value;
            } else {
                if (stripos($key, 'uid:') === 0) {
                    $url = iCMS_USER_URL . $key;
                    $key = parse_url($url, PHP_URL_PATH);
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
        $rs = NodeCache::get('rules');
        $rules = [];
        $result = [];
        foreach ((array)$rs as $rule) {
            if ($rule) {
                foreach ($rule as $k => $v) {
                    $v = str_replace('{EXT}', $this->ext, $v);
                    $v = rtrim($this->dir, '/') . '/' . ltrim($v, '/');
                    if ($k == 'index' || $k == 'list') {
                        $k = 'node';
                    } else {
                        $pi = pathinfo($v);
                        $pi['dirname']=='\\' && $pi['dirname'] = '/';
                        if (substr($v, -1) == '/' || empty($pi['extension'])) {
                            $pi['extension'] = ltrim($this->ext, '.');
                            if (strpos($v, '{LINK}') !== false) {
                                $pi['basename'] .= '/p{P}' . $this->ext;
                            }else{
                                $pi['basename'] .= '/index_{P}' . $this->ext;
                            }
                        } else {
                            $pi['basename'] = $pi['filename'] . '_{P}.' . $pi['extension'];
                        }
                        $pv = rtrim($pi['dirname'], '/') . '/' . $pi['basename'];
                        $rules[$k][$pv] = strlen($pv);
                    }
                    $rules[$k][$v] = strlen($v);
                }
            }
        }
        if (!$rules) {
            return false;
        }

        if ($rules) {
            foreach ($rules as $key => $rvalue) {
                foreach ($rvalue as $k => $v) {
                    $result[] = $this->builder($key, $k);
                }
            }
        }
        if (!$result) {
            return false;
        }

        usort($result, function ($a, $b) {
            $al = strlen($a[0]);
            $bl = strlen($b[0]);
            if ($al == $bl) {
                return  0;
            }
            return ($al < $bl) ? -1 : 1;
        });
        krsort($result);
        foreach ($result as $key => $value) {
            // preg_match_all('@^' . $value[0] . '$@i', $this->path, $matches);
            // if ($matches[0]) {
            //     $this->URI = preg_replace('@' . $value[0] . '@i', $value[1], $this->path);
            //     return true;
            // }
            $pattern = '#^' . $value[0] . '$#i';
            if (preg_match($pattern, $this->path, $matches)) {
                // 完全匹配验证（确保整个路径被匹配）
                if ($matches[0] === $this->path) {
                    $this->URI = preg_replace(
                        $pattern,
                        $value[1],
                        $this->path,
                        1
                    );
                    return true;
                }
            }
        }
    }

    public function clink()
    {
        $hooks = isset(iCMS::$config['hooks']['rewrite']) ? iCMS::$config['hooks']['rewrite'] : [];
        if (empty($hooks['clink'])) {
            return;
        }
        if (preg_match('@' . $this->dir . '.*?' . preg_quote($this->ext) . '@', $this->path)) {
            $clink = '[' . ltrim($this->path, '/') . ']';
            $params = [$clink, &$this];
            foreach ($hooks['clink'] as $key => $call) {
                iPHP::callback($call, $params, E_USER_ERROR);
                if ($this->URI) {
                    return true;
                }
            }
        }
    }

    public function index()
    {
        if (preg_match('@^/index_(\d+)' . preg_quote($this->ext) . '@', $this->path, $match)) {
            $_GET['page'] = intval($match[1]);
            return true;
        }
        if (strpos($this->path, iCMS_API_NAME) !== false) {
            return true;
        }
    }

    public function route0($routeArray)
    {
        $path = trim($this->path, '/');
        if (isset($routeArray[$path])) {
            $this->URI = $routeArray[$path];
            return;
        } else {
            //匹配 /user/aa/bb
            if (preg_match('@^[a-zA-Z]+/[a-zA-Z]+/[a-zA-Z]+$@', $path)) {
                list($app, $do, $s, $ss) = explode('/', ltrim($path, '/'));
                //匹配 /api/article/comment
                if ($app == 'api') {
                    $app = $do;
                    $do = $s;
                    $s = $ss;
                }
                $_app = Application::get($app, $sub);
                if (iCMS::$config['apps'][$_app]) {
                    $query = compact('app', 'do');
                    $s && $query['s'] = $s;
                    $this->URI = Route::make($query, iCMS_API_NAME);
                }
            }
        }
    }

    public function route1($routeArray)
    {
        $path = ltrim($this->path, '/');
        $user_base = parse_url(iCMS_USER_URL, PHP_URL_PATH);
        foreach ($routeArray as $key => $value) {
            // 处理带有 {userid} 占位符的路径
            if (strpos($key, '{userid}') === 0) {
                $key = ($user_base ? rtrim($user_base, '/') : '') . '/' . $key;
            }
            $key = ltrim($key, '/');
            // 设置替换规则
            $replacement = '(?<\\1>\w+)';
            // asd/{aa}*.html  贪婪模式
            if(strpos($key, '}*') !== false){
                $key = str_replace('}*', '}', $key);
                $replacement = '(?<\\1>.*?)';
            }elseif(strpos($key, 'id}') !== false){
                $replacement = '(?<\\1>\d+)';
            }
            $pattern = preg_replace('/\{(\w+)\}/i', $replacement, $key);
            // 匹配路径
            if (preg_match_all('@^' . $pattern . '/?$@i', $path, $matches)) {
                $this->URI = $value;
                $search = [];
                $replace = [];
                // 替换占位符
                foreach ($matches as $key => $match) {
                    if (!is_numeric($key)) {
                        $search[] = '{' . $key . '}';
                        $replace[] = $match[0];
                    }
                }

                $this->URI = str_replace($search, $replace, $this->URI);
            }
        }
    }

    public function make()
    {
        if (Request::isUrl($this->URI)) {
            Utils::jumpto($this->URI);
        }

        $urls = parse_url($this->URI);
        $reqs = [];
        if (isset($urls['path']) && isset($urls['query'])) {
            // $base = rtrim(self::$config['public'], '/') . '/' . ltrim($urls['path'], '/');
            $query = $urls['query'];
            $base = $urls['path'];
        } else {
            // $base = $APIURL;
            $query = $urls['path'];
            $base = iCMS_API_NAME;
        }

        parse_str($query, $reqs);

        if (isset($reqs[$query]) && $reqs[$query] == '') {
            unset($reqs[$query]);
        }

        $query = [];
        $this->request['query'] && parse_str($this->request['query'], $query);
        $_GET = array_merge($reqs, $query);
        Security::addslash($_GET);
        Waf::check($_GET);
        $this->URI = Route::make($_GET, $base);
        // var_dump($this->URI,$_GET);
        // exit;
    }

    public function builder($key, $value)
    {
        preg_match_all("/\{(.*?)\}/", $value, $matches);
        $url = preg_replace("/\{.*?\}/", '%s', $value);
        $urlRule = Route::getRule();
        $regular = $value;
        $query = [];
        $app = $key;
        if (strpos($key, ':') !== false) {
            list($app, $do) = explode(':', $key);
            $do && $query[] = 'do=' . $do;
        }
        $i = 1;
        $param[] = $url;

        foreach ($matches[1] as $k => $v) {
            if (stripos($v, 'HASH@') !== false) {
                list($v, $t) = explode('@', $v);
            }
            if (stripos($v, 'AUTHID@') !== false) {
                list($v, $tt) = explode('@', $v);
            }
            $reg = $urlRule[$v]['regular'];
            $reg && $reg = str_replace('/', '@@', $reg);
            $param[] = $reg;
            if ($urlRule[$v]['query']) {
                $query[] = sprintf('%s=$%d', $urlRule[$v]['query'], $i);
                $i++;
            }
        }
        $regular = call_user_func_array('sprintf', $param);
        // $regular = preg_quote($regular, '@');
        $rewrite = $app . '.php?' . implode('&', $query);
        $appRule = isset(Route::$config['appRule'][$key]) ? Route::$config['appRule'][$key] : [];
        if ($p = $appRule['page']) {
            $rewrite = str_replace('page=', $p . '=', $rewrite);
        }
        return [$regular, $rewrite];
    }
}
