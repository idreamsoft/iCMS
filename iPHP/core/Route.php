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
class Route
{
    const PAGE_FMT = '{P}';

    public static $config = [];
    public static $callback = [
        'url'    => [],
        'domain' => [],
        'routing'=> [],
    ];
    public static $data = [];
    public static $rewrite = false;

    protected static $params = null;

    public static function init($config = [], $_config = [])
    {
        self::$config = array_merge($config, $_config);
        self::$callback = array_merge(self::$callback, (array)$config['callback']);
        self::setRewrite(self::$config['rewrite']);
    }

    public static function setRewrite($flag)
    {
        self::$rewrite = $flag;
    }

    /**
     * 生成链接
     *
     * @param string $uri
     * @param array $params
     * @return string
     */
    public static function makeUrl($uri, $params = [])
    {
        $url = iCMS_API_URL;
        if (is_array($uri)) {
            foreach ($uri as $key => $value) {
                $url .= '/' . $value;
            }
        } else {
            $uri && $url .= '/' . ltrim($uri, '/');
        }

        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $url .= sprintf('/%s/%s', $key, $value);
            }
        } else {
            is_string($params) && $url .= '/' . ltrim($params, '/');
        }
        return rtrim($url, '/');
    }

    public static function url($key, $params = [])
    {
        $url = self::create($key);
        $params && $url = self::make($params, $url);
        return $url;
    }

    /**
     * 根据 etc/route/*.json 配置生成链接
     *
     * @param string $key
     * @param array $var
     * @return string
     */
    public static function create($key = '', $var = [])
    {
        return self::routing($key, $var);
    }

    public static function routing($key = '', $var = '')
    {
        $key && $key = str_replace(':', '/', $key);
        $url = self::$config['routing'][$key] ?: $key;
        self::$rewrite && $url = $key;

        if (empty($url)) {
            return iCMS_API_URL ;
        }
        $api_name = iCMS_API_NAME;
        if (self::$rewrite) {
            $api_name = self::$config['routing']['api'];
        }

        $base = $APIURL = rtrim(iCMS_API_BASE, '/') . '/' . ltrim($api_name, '/');
        if (empty($url) || $key == 'api') {
            return rtrim($APIURL, '/') ;
        }

        if (Request::isUrl($url)) {
            return $url;
        }

        $urls = parse_url($url);
        $queryArr = [];
        if (isset($urls['path']) && isset($urls['query'])) {
            $base = rtrim(iCMS_API_BASE, '/') . '/' . ltrim($urls['path'], '/');
            $query = $urls['query'];
        } else {
            $base = $APIURL;
            $query = $urls['path'];
        }
        parse_str($query, $queryArr);

        if ($queryArr) {
            $url = $queryArr['app'];
            $queryArr['do'] && $url .= '/' . $queryArr['do'];
            unset($queryArr['app'],$queryArr['do']);
            if (is_array($queryArr)) {
                $querys = [];
                foreach ($queryArr as $key => $value) {
                    $querys[] = ($key === 's' || $key === 'sdo')
                    ? $value
                    : ($key . ($value === '' ? '' : '/' . $value));
                }
                $querys && $url .= '/' . implode('/', $querys);
            }
        }

        if (self::$rewrite) {
            $base = rtrim(self::$config['url'], '/');
            //{userid}开头，使用用户域名
            if (stripos($url, '{userid}') === 1) {
                $base = iCMS_USER_URL;
            }
        }

        if (stripos($url, '/{API}/') === 0) {
            $url = str_replace('/{API}', '', $url);
            $base = $APIURL;
        }

        // 匹配{} 例:/{userid}/{}/
        if (is_array($var) && isNumArr($var)) {
            preg_match_all('/\{(\w+)\}/i', $url, $matches);
            $url = str_replace($matches[0], $var, $url);
        }

        if ($base) {
            $url = ltrim($url, '/');
            $base = rtrim($base, '/');
            $url = $base . '/' . $url;
        }

        if ($var && !is_array($var)) {
            $url = self::make($var, $url);
        }
        if (self::$callback['routing']['data']) {
            call_user_func_array(self::$callback['routing']['data'], [&$url]);
        }
        return $url;
    }

    public static function Hashids($salt = '', $len = 8)
    {
        empty($len) && $len = 8;
        self::$config['hash']['len'] && $len = self::$config['hash']['len'];
        self::$config['hash']['salt'] && $salt = self::$config['hash']['salt'];
        return Vendor::run('Hashids', ['salt' => $salt, 'len' => $len]);
    }

    public static function setData($app, $data)
    {
        self::$data[$app] = $data;
    }

    public static function getRule()
    {
        return self::$config['urlRule'];
    }

    public static function getRuleValue($key)
    {
        $urlRule = self::getRule();
        //{@random,8}
        if ($key[0] == '@') {
            $args = explode(',', substr($key, 1));
            if (in_array($args[0], ['random'])) {
                return call_user_func_array($args[0], array_slice($args, 1));
            }
        }
        $func = null;
        //{0xID,3,2}
        if (strpos($key, ',') !== false) {
            list($key, $start, $len) = explode(',', $key);
            $func = function (&$string, $start = 0, $len = 0) {
                if ($len === null) {
                    $len = $start;
                    $start = 0;
                }
                $string = substr($string, $start, $len);
            };
        }
        if ($rule = $urlRule[$key]) {
            $rule['call'] && $ret = call_user_func_array($rule['call'], [$key, self::$params]);
        }
        $func && call_user_func_array($func, [&$ret, $start, $len]);
        return $ret;
    }

    public static function rule($matches)
    {
        $rule = $matches[1];
        //{AUTHID@time}
        //{AUTHID}
        //{AUTHID@86400}
        //{AUTHCID@86400}
        if (strpos($rule, 'AUTHID') !== false || strpos($rule, 'AUTHCID') !== false) {
            list($rule, $_time) = explode('@', $rule);
            self::$params[] = $_time;
        }

        //{BOOK:ID}
        if (strpos($rule, ':') !== false) {
            list($app, $_rule) = explode(':', $rule);
            $app = strtolower($app);
            self::$data[$app] && self::$params[2] = self::$data[$app];
        }
        //兼容
        $rule == '0x3ID' && $rule = '0xID,0,3';
        $rule == '0x3,2ID' && $rule = '0xID,3,2';

        //HASH@ID
        //HASH@ID@8@aaa
        //HASH@0xID
        //HASH@0xCID
        if (stripos($rule, 'HASH@') !== false) {
            list($si, $rule, $len, $salt) = explode('@', $rule);
            $Hashids = self::Hashids($salt, $len);
            $id = (int)self::getRuleValue($rule);
            return $Hashids->encode($id);
        }
        return self::getRuleValue($rule);
    }

    public static function ruleData($C, $key)
    {
        if (empty($C['mode']) || $C['password']) {
            return '{PHP}';
        } else {
            is_object($C['rule']) && $C['rule'] = (array)$C['rule'];
            is_array($C['rule']) or $C['rule'] = json_decode($C['rule'], true);
            $rule = $C['rule'][$key];
            // $rule OR $rule = $key;
            return $rule;
        }
    }

    /**
     * 生成应用内容链接
     *
     * @param string $route
     * @param array $a
     * @param mixed $type
     * @return object
     */
    public static function get($route, $a = [], $type = null)
    {
        $i = new stdClass();
        $default = [];
        $node = [];
        $array = (array)$a;

        $app = $route;
        if ($route && strpos($route, ':') !== false) {
            list($app, $do) = explode(':', $route);
        }

        $appRule = self::$config['appRule'][$app];
        $type === null && $type = (isset($appRule['type']) ? $appRule['type'] : $appRule['rule']);
        switch ($type) {
            case '0':
                $i->href = $array['url'];
                $url = $array['rule'];
                $array['page'] && $purl = $array['page'];
                break;
            case '1': //分类
                $node = $array;
                $i->href = $node['url'];
                $url = self::ruleData($node, 'index');
                $purl = self::ruleData($node, 'list');
                empty($purl) && $purl = rtrim($url, '/') . '/index_{P}{EXT}';
                break;
            case '2': //内容
                $array = (array)$a[0];
                $node = (array)$a[1];
                $i->href = $array['url'];
                $url = self::ruleData($node, $route);
                break;
            case '3': //标签
                $array = (array)$a[0];
                $node = (array)$a[1];
                $_node = (array)$a[2];
                $i->href = $array['url'];
                $node && $url = self::ruleData($node, $app);
                if ($_node['rule'][$app]) {
                    $url = self::ruleData($_node, $app);
                }
                break;
            case '4': //自定义
                $array = (array)$a[0];
                $node = (array)$a[1];
                $i->href = $array['url'];
                $url = self::ruleData($node, $route);
                $href = 'index.php?app=' . $app;
                break;
            default:
                $url = '{PHP}';
                $href = 'index.php?app=' . $app;
                break;
        }
        if (empty($url) && $array['rule']) {
            $url = $array['rule'];
        }
        //获取应用的默认设置
        if ($default = self::$config[$app]) {
            $route_dir = $default['dir'];
            $route_url = $default['url'];
            empty($url) && $url = $default['rule'];
        }
        //设备配置
        if ($device = self::$config['device']) {
            $route_dir = $device['dir'];
            $route_url = $device['domain'];
        }

        empty($route_url) && $route_url = self::$config['url'];
        empty($route_dir) && $route_dir = self::$config['dir'];

        if (strpos($route_url, '*') !== false) {
            $route_url = str_replace('*', random(6), $route_url);
        }
        //[xxxxx]类自定链接优先
        if ($array['clink']) {
            preg_match('/\[(.+)\]/', $array['clink'], $match);
            isset($match[1]) && $url = $match[1];
            // $clink = self::ruleData($node,'clink');
            // $clink && $url = $clink;
        }
        if (self::$callback['url']['rule']) {
            $url = self::$callback['url']['rule'];
        }
        $i->app = $app;
        if ($url == '{PHP}') {
            $primary = $appRule['primary'];
            empty($href) && $href = $app . '.php';
            $query = [];
            $do && $query['do'] = $do;
            $primary && $query[$primary] = $array[$primary];
            $href = self::make($query, $href);
            if ($appRule['page']) {
                $i->pageurl = self::make([$appRule['page'] => self::PAGE_FMT], $href);
                Request::isUrl($i->pageurl) or $i->pageurl = rtrim($route_url, '/') . '/' . $i->pageurl;
            }
            Request::isUrl($href) or $href = rtrim($route_url, '/') . '/' . $href;
            $i->href = $href;
        } elseif ($url && strpos($url, '{PHP}') === false) {
            self::$params = [$array, $node, $_node];

            $node['htmlext'] && self::$config['ext'] = $node['htmlext'];

            $i = self::build($url, $route_dir, $route_url);

            if (strpos($i->href, self::PAGE_FMT) !== false) {
                $purl = $i->href;
            }

            self::pagefmt($i);

            if ($purl) {
                $ii = self::build($purl, $route_dir, $route_url);
                $i->pageurl = $ii->href;
                $i->pagepath = $ii->path;
                unset($ii);
            } else {
                $pfile = $i->file;
                if (strpos($pfile, self::PAGE_FMT) === false) {
                    if(strpos($url, '{LINK}') !== false && $i->name=='index'){
                        $pfile = 'p' . self::PAGE_FMT . $i->ext;
                    }else{
                        $pfile = $i->name . '_' . self::PAGE_FMT . $i->ext;
                    }
                }
                $i->pageurl = $i->hdir . '/' . $pfile;
                $i->pagepath = $i->dir . '/' . $pfile;
            }
            // call_user_func_array(self::$callback, array($app,$i,self::$ARRAY,$appRule));
        }
        $i->url = $i->href;
        if ($node['id'] && self::$callback['domain']) {
            //nodeApp::domain
            $i = call_user_func_array(self::$callback['domain'], [$i, $node, $route_url, $type]);
        }

        if (self::$callback['device']) {
            //Adapter::urls
            $d = call_user_func_array(self::$callback['device'], [$i]);
            $i = (object)array_merge((array)$i, $d);
        }
        if (self::$callback['url']['data']) {
            call_user_func_array(self::$callback['url']['data'], [&$i]);
        }
        return $i;
    }

    public static function build($url, $dir, $domain = '', $ext = '')
    {
        if ($url && strpos($url, '{') !== false && strpos($url, '}') !== false) {
            $url = preg_replace_callback("/\{(.*?)\}/", [__CLASS__, 'rule'], $url);
        }

        $i = new stdClass();
        $i->href = $url;
        $dir = rtrim($dir, '/') . '/';
        if (!Security::safePath($dir)) {
            $i->href = $dir . $url;
        }
        $i->href = ltrim(File::path($i->href), '/');
        $i->path = rtrim(File::path(iPHP_APP_PATH . $dir . $url), '/');

        if (Request::isUrl($i->href) === false) {
            $i->href = rtrim($domain, '/') . '/' . $i->href;
        }
        $pathA = pathinfo($i->path);
        $i->hdir = pathinfo($i->href, PATHINFO_DIRNAME);
        $i->dir = $pathA['dirname'];
        $i->file = $pathA['basename'];
        $i->name = $pathA['filename'];
        $i->ext = '.' . $pathA['extension'];
        $i->name or $i->name = $i->file;

        if (empty($i->file) || substr($url, -1) == '/' || empty($pathA['extension'])) {
            $i->name = 'index';
            $i->ext = self::$config['ext'];
            $ext && $i->ext = $ext;
            $i->file = $i->name . $i->ext;
            $i->path = $i->path . '/' . $i->file;
            $i->dir = dirname($i->path);
            $i->hdir = dirname($i->href . '/' . $i->file);
        }

        return $i;
    }

    public static function pagefmt(&$i)
    {
        // $i->pfile = $i->file;
        // if(strpos($i->file,self::PAGE_FMT)===false) {
        //     $i->pfile = $i->name.'_'.self::PAGE_FMT.$i->ext;
        // }
        // $i->pageurl  = $i->hdir.'/'.$i->pfile ;
        // $i->pagepath = $i->dir.'/'.$i->pfile;
        $i->href = str_replace(self::PAGE_FMT, 1, $i->href);
        $i->path = str_replace(self::PAGE_FMT, 1, $i->path);
        $i->file = str_replace(self::PAGE_FMT, 1, $i->file);
        $i->name = str_replace(self::PAGE_FMT, 1, $i->name);
    }

    public static function pageNum($path, $page = false)
    {
        $page === false && $page = $GLOBALS['page'];
        is_null($path) && $path = '';
        if ($page < 2) {
            return str_replace(['_' . self::PAGE_FMT, '&p=' . self::PAGE_FMT], '', $path);
        }
        return str_replace(self::PAGE_FMT, $page, $path);
    }

    public static function setPageUrl($param)
    {
        return Paging::url($param);
    }

    public static function getUrls(&$data, $flag = 'iurl')
    {
        $hrefs = $urls = [];
        $devices = iCMS::$config['template']['device'] ?: [];
        $devices = array_column($devices, null, 'name');
        $translates = iCMS::$config['devices'] ?: [];
        $list = $data[$flag];
        $active = false;
        if ($data['iurl']['w']) {
            $active = rtrim($data['iurl']['w']['url'], '/') == rtrim(iPHP_REQUEST_URL, '/');
        }
        $deflang = 'zh';
        if (isset(iCMS::$config['translate'])) {
            $translate = iCMS::$config['translate'];
            $deflang = $translate['default_lang'];
        }
        $hrefs[$deflang] = [
            'title'   => Lang::$languages[$deflang][0],
            'lang'    => $deflang,
            'url'     => $data['iurl']['w']['url'],
            'active'  => $active,
        ];

        foreach ($list as $key => $value) {
            $device = $devices[$key];
            $trans = $translates[$key];
            if (is_array($value) && $value['url']) {
                $url = $value['url'];
            } else {
                $url = $value;
            }
            $active = $active ? false : rtrim($url, '/') == rtrim(iPHP_REQUEST_URL, '/');
            if ($device) {
                $urls[$key] = [
                    'key'  => $key,
                    '_title'=> $device['title'],
                    'title'=> Lang::$languages[$key][0]?:$device['title'],
                    'lang' => $device['lang'],
                    'url'  => $url,
                    'active'  => $active,
                ];
            }
            if ($trans) {
                $row = [
                    'title'   => Lang::$languages[$trans['target']][0],
                    'lang'    => $trans['target'],
                    'url'     => $url,
                    'active'  => $active,
                ];
                $hrefs[$row['lang']] = $row;
            }
        }
        $data['devices'] = $urls;
        $data['hreflangs'] = $hrefs;
        View::assign('W_URL', $data['iurl']['w']['url']);
        View::assign('M_URL', $data['iurl']['m']['url']);
        View::assign('hreflangs', $hrefs);
    }

    public static function make($param = null, $url = null)
    {
        $url or $url = $_SERVER['REQUEST_URI'];
        $parse = parse_url($url);
        $query = [];
        isset($parse['query']) && parse_str($parse['query'], $query);
        is_array($param) or $param = parse_url_qs($param);
        foreach ($param as $key => $value) {
            //这个null是字符
            if ($value === null || strtolower($value) === 'null') {
                unset($param[$key], $query[$key]);
            }
        }
        $query = array_merge((array)$query, (array)$param);
        $parse['query'] = http_build_query($query);

        $PAGE_FMT = urlencode(self::PAGE_FMT);
        if (strpos($parse['query'], $PAGE_FMT) !== false) {
            $parse['query'] = str_replace($PAGE_FMT, self::PAGE_FMT, $parse['query']);
        }
        // if(strpos($parse['path'],'.php')===false) {
        //     $path = '';
        //     foreach ($query as $key => $value) {
        //         $path.= $key.'-'.$value;
        //     }
        //     $parse['path'].= $path.self::$config['ext'];
        // }
        if ($parse['query'] && empty($parse['path'])) {
            $parse['path'] = '/';
        }
        $nurl = self::glue($parse);
        return $nurl ? $nurl : $url;
    }

    public static function glue($parsed)
    {
        if (!is_array($parsed)) {
            return false;
        }

        $uri = isset($parsed['scheme']) ? $parsed['scheme'] . ':' . ((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : '';
        $uri .= isset($parsed['user']) ? $parsed['user'] . ($parsed['pass'] ? ':' . $parsed['pass'] : '') . '@' : '';
        $parsed['host'] && $uri .= $parsed['host'];
        $parsed['port'] && $uri .= ':' . $parsed['port'];
        $parsed['path'] && $uri .= $parsed['path'];
        $parsed['query'] && $uri .= '?' . $parsed['query'];
        $parsed['fragment'] && $uri .= '#' . $parsed['fragment'];
        return $uri;
    }

    public static function URI($qs = null, $url = null)
    {
        $url === null && $url = $_SERVER['REQUEST_URI'];
        $arr = parse_url($url);
        $arr['query'] = self::merge_query($arr['query'], $qs, true);
        return self::glue($arr);
    }

    public static function merge_query($q1 = null, $q2 = null, $build = false)
    {
        is_string($q1) && $q1 = parse_url_qs($q1);
        is_string($q2) && $q2 = parse_url_qs($q2);
        $query = array_merge($q1, $q2);
        return $build ? http_build_query($query) : $query;
    }

    public static function defaults($key, $data)
    {
        $map = [
            'ID' => function ($a, $node, $_node) {
                return $a['id'];
            },
            '0xID' => function ($a, $node, $_node) {
                return sprintf('%08s', $a['id']);
                ;
            },
            'AUTHID' => function ($a, $node, $_node, $time) {
                return rawurlencode(auth_encode($a['id'], $time ?: 0));
            },
            'MD5' => function ($a, $node, $_node) {
                return substr(md5($a['id']), 8, 16);
            },
            'TMD5' => function ($a, $node, $_node) {
                return substr(md5(time() . uniqid()), 8, 16);
            },
            'CID' => function ($a, $node, $_node) {
                return $node['id'];
            },
            'CMD5' => function ($a, $node, $_node) {
                return substr(md5($node['id']), 8, 16);
            },
            '0xCID' => function ($a, $node, $_node) {
                return sprintf('%08s', $node['id']);
            },
            'AUTHCID' => function ($a, $node, $_node, $time) {
                return rawurlencode(auth_encode($node['id'], $time ?: 0));
            },
            'PAGES' => function ($a, $node, $_node) {
                return $node['dir'];
            },
            'CDIR' => function ($a, $node, $_node) {
                return $node['dir'];
            },
            'CDIRS' => function ($a, $node, $_node) {
                return $node['dirs'];
            },
            'TIME' => function ($a, $node, $_node) {
                return $a['pubdate'];
            },
            'YY' => function ($a, $node, $_node) {
                return get_date($a['pubdate'], 'y');
            },
            'YYYY' => function ($a, $node, $_node) {
                return get_date($a['pubdate'], 'Y');
            },
            'M' => function ($a, $node, $_node) {
                return get_date($a['pubdate'], 'n');
            },
            'MM' => function ($a, $node, $_node) {
                return get_date($a['pubdate'], 'm');
            },
            'D' => function ($a, $node, $_node) {
                return get_date($a['pubdate'], 'j');
            },
            'DD' => function ($a, $node, $_node) {
                return get_date($a['pubdate'], 'd');
            },
            'NAME' => function ($a, $node, $_node) {
                return rawurlencode($a['name']);
            },
            'TITLE' => function ($a, $node, $_node) {
                return rawurlencode($a['title']);
            },
            'ZH_CN' => function ($a, $node, $_node) {
                return ($a['name'] ?: $a['title']);
            },
            'TKEY' => function ($a, $node, $_node) {
                return $a['tkey'];
            },
            'LINK' => function ($a, $node, $_node) {
                return $a['clink'];
            },
            'TCID' => function ($a, $node, $_node) {
                return $_node['t'];
            },
            'TCDIR' => function ($a, $node, $_node) {
                return $_node['dir'];
            },
            'EXT' => function ($a, $node, $_node) {
                return self::$config['ext'];
            },
            'P' => function ($a, $node, $_node) {
                return self::PAGE_FMT;
            }
        ];
        if ($fun = $map[$key]) {
            return call_user_func_array($fun, $data);
        } else {
            $key = strtolower($key);
            if (isset($data[0][$key])) {
                return $data[0][$key];
            }
        }
    }
}
