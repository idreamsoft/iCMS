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
    const PAGE_SIGN = '{P}';

    public static $config   = array();
    public static $callback = array();
    public static $data     = array();
    public static $rewrite  = null;

    protected static $params = null;

    public static function init($config = array(), $_config = array())
    {
        self::$config   = array_merge($config, $_config);
        self::$callback = array_merge((array)self::$config['callback'], self::$callback);
    }

    public static function setRewrite($flag){
        self::$rewrite = $flag;
    }
    public static function routing($key, $var = null)
    {
        $key = str_replace(':','/',$key);
        $url = self::$config['routing'][$key];
        $routing = [$key,$url];
        if (empty($url)) {
            list($app, $do, $s) = explode('/', $key);
            $query = compact('app', 'do');
            $s && $query['s'] = $s;
            $routing[0] = str_replace(':', '/', $key);
            $routing[1] = Route::make($query, 'api.php');
        }

        $rewrite = is_null(self::$rewrite) ? iPHP_ROUTE_REWRITE : self::$rewrite;


        $url = $rewrite ? $routing[0] : $routing[1];

        if ($rewrite && stripos($key, 'uid/') === 0) {
            $url = rtrim(self::$config['user'], '/') . $url;
        }

        if (is_array($var)) {
            // 匹配{} 例:/{uid}/{}/ 
            preg_match_all('/\{(\w+)\}/i', $url, $matches);
            $url = str_replace($matches[0], $var, $url);
        } else {
            $var && $url = Route::make($var, $url);
        }
        $url = ltrim($url, '/');
        if (!$rewrite) {
            $base = rtrim(self::$config['public'], '/');
        } else {
            if (!Request::isUrl($url)) {
                $base = rtrim(self::$config['url'], '/');
            }
        }
        $base && $url = sprintf('%s/%s', $base, $url);

        if (self::$callback['routing']['data']) {
            call_user_func_array(self::$callback['routing']['data'], array(&$url));
        }
        return $url;
    }
    public static function Hashids($salt = '', $len = 8)
    {
        empty($len) && $len = 8;
        self::$config['hash']['len'] && $len = self::$config['hash']['len'];
        self::$config['hash']['salt'] && $salt = self::$config['hash']['salt'];
        return Vendor::run('Hashids', array("salt" => $salt, "len" => $len));
    }
    public static function setData($app, $data)
    {
        self::$data[$app] = $data;
    }
    public static function getRuleEtc()
    {
        empty($GLOBALS['urlRule']) && $GLOBALS['urlRule'] = Etc::many('*', 'urlRule*', true);
        return $GLOBALS['urlRule'];
    }
    public static function getRuleValue($key)
    {
        $urlRule = self::getRuleEtc();
        //{@random,8}
        if ($key[0] == '@') {
            $args = explode(',', substr($key, 1));
            if (in_array($args[0], array('random'))) {
                return call_user_func_array($args[0], array_slice($args, 1));
            }
        }
        $func = null;
        //{0xID,3,2}
        if (strpos($key, ',') !== false) {
            list($key, $start, $len) = explode(',', $key);
            $func = function (&$string, $start = 0, $len = 0) {
                if ($len === null) {
                    $len   = $start;
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
            list($app, $rule) = explode(':', $rule);
            $app = strtolower($app);
            self::$data[$app] && self::$params[0] = self::$data[$app];
        }
        //兼容
        $rule == '0x3ID'   && $rule = '0xID,0,3';
        $rule == '0x3,2ID' && $rule = '0xID,3,2';

        //Hash@ID
        //Hash@ID@8@aaa
        //Hash@0xID
        //Hash@0xCID
        if (stripos($rule, 'Hash@') !== false) {
            list($si, $rule, $len, $salt) = explode('@', $rule);
            $Hashids = self::Hashids($salt, $len);
            $id = (int)self::getRuleValue($rule);
            return $Hashids->encode($id);
        }
        return self::getRuleValue($rule);
    }
    public static function rule_data($C, $key)
    {
        if (empty($C['mode']) || $C['password']) {
            return '{PHP}';
        } else {
            is_object($C['rule']) && $C['rule'] = (array)$C['rule'];
            is_array($C['rule'])  or $C['rule'] = json_decode($C['rule'], true);
            $rule = $C['rule'][$key];
            // $rule OR $rule = $key;
            return $rule;
        }
    }

    public static function get($route, $a = array(), $type = null)
    {
        $i       = new stdClass();
        $default = array();
        $node    = array();
        $array   = (array)$a;

        $app = $route;
        if (strpos($route, ':') !== false) {
            list($app, $do) = explode(':', $route);
        }

        $urlType = self::$config['iurl'][$app];
        $type === null && $type = (isset($urlType['type']) ? $urlType['type'] : $urlType['rule']);

        switch ($type) {
            case '0':
                $i->href = $array['url'];
                $url     = $array['rule'];
                break;
            case '1': //分类
                $node = $array;
                $i->href  = $node['url'];
                $url      = self::rule_data($node, 'index');
                $purl     = self::rule_data($node, 'list');
                empty($purl) && $purl = rtrim($url, '/') . '/index_{P}{EXT}';
                break;
            case '2': //内容
                $array    = (array)$a[0];
                $node = (array)$a[1];
                $i->href  = $array['url'];
                $url      = self::rule_data($node, $route);
                break;
            case '3': //标签
                $array     = (array)$a[0];
                $node  = (array)$a[1];
                $_node = (array)$a[2];
                $i->href   = $array['url'];
                $node && $url = self::rule_data($node, $app);
                if ($_node['rule'][$app]) {
                    $url = self::rule_data($_node, $app);
                }
                break;
            case '4': //自定义
                $array    = (array)$a[0];
                $node = (array)$a[1];
                $i->href  = $array['url'];
                $url      = self::rule_data($node, $route);
                $href     = 'index.php?app=' . $app;
                break;
            default:
                $url  = '{PHP}';
                $href = 'index.php?app=' . $app;
                break;
        }
        if (empty($url) && $array['rule']) {
            $url = $array['rule'];
        }

        $default  = self::$config[$app];
        if ($default) {
            $route_dir = $default['dir'];
            $route_url = $default['url'];
            empty($url) && $url = $default['rule'];
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
            // $clink = self::rule_data($node,'clink');
            // $clink && $url = $clink;
        }
        if (self::$callback['url']['rule']) {
            $url = self::$callback['url']['rule'];
        }
        $i->app = $app;
        if ($url == '{PHP}') {
            $primary = $urlType['primary'];
            empty($href) && $href = $app . '.php';
            $query = array();
            $do && $query['do'] = $do;
            $primary && $query[$primary] = $array[$primary];
            $href = self::make($query, $href);
            if ($urlType['page']) {
                $i->pageurl = self::make(array($urlType['page'] => self::PAGE_SIGN), $href);;
                Request::isUrl($i->pageurl) or $i->pageurl = rtrim($route_url, '/') . '/' . $i->pageurl;
            }
            Request::isUrl($href) or $href = rtrim($route_url, '/') . '/' . $href;
            $i->href = $href;
        } else if (strpos($url, '{PHP}') === false) {
            self::$params = array($array, $node, $_node);

            $node['htmlext'] && self::$config['ext'] = $node['htmlext'];

            $i = self::build($url, $route_dir, $route_url);

            if (strpos($i->href, self::PAGE_SIGN) !== false) {
                $purl = $i->href;
            }

            self::page_sign($i);

            if ($purl) {
                $ii = self::build($purl, $route_dir, $route_url);
                $i->pageurl  = $ii->href;
                $i->pagepath = $ii->path;
                unset($ii);
            } else {
                $pfile = $i->file;
                if (strpos($pfile, self::PAGE_SIGN) === false) {
                    $pfile = $i->name . '_' . self::PAGE_SIGN . $i->ext;
                }
                $i->pageurl  = $i->hdir . '/' . $pfile;
                $i->pagepath = $i->dir . '/' . $pfile;
            }
            // call_user_func_array(self::$callback, array($app,$i,self::$ARRAY,$urlType));
        }
        $i->url = $i->href;
        if ($node['id'] && self::$callback['domain']) {
            $i = call_user_func_array(self::$callback['domain'], array($i, $node['id'], $route_url));
        }
        if (self::$callback['device']) {
            $d = call_user_func_array(self::$callback['device'], array($i));
            $i = (object)array_merge((array)$i, $d);
        }
        if (self::$callback['url']['data']) {
            call_user_func_array(self::$callback['url']['data'], array(&$i));
        }
        return $i;
    }

    public static function build($url, $_dir, $_host = null, $_ext = null)
    {
        if (strpos($url, '{') !== false && strpos($url, '}') !== false) {
            $url = preg_replace_callback("/\{(.*?)\}/", array(__CLASS__, 'rule'), $url);
        }

        $i = new stdClass();
        $i->href = $url;
        if (strpos($_dir, '..') === false) {
            $i->href = $_dir . $url;
        }
        $i->href = ltrim(File::path($i->href), '/');
        $i->path = rtrim(File::path(iPHP_PATH . $_dir . $url), '/');

        if (Request::isUrl($i->href) === false) {
            $i->href = rtrim($_host, '/') . '/' . $i->href;
        }
        $pathA = pathinfo($i->path);
        $i->hdir = pathinfo($i->href, PATHINFO_DIRNAME);
        $i->dir  = $pathA['dirname'];
        $i->file = $pathA['basename'];
        $i->name = $pathA['filename'];
        $i->ext  = '.' . $pathA['extension'];
        $i->name or $i->name = $i->file;

        if (empty($i->file) || substr($url, -1) == '/' || empty($pathA['extension'])) {
            $i->name = 'index';
            $i->ext  = self::$config['ext'];
            $_ext && $i->ext = $_ext;
            $i->file = $i->name . $i->ext;
            $i->path = $i->path . '/' . $i->file;
            $i->dir  = dirname($i->path);
            $i->hdir = dirname($i->href . '/' . $i->file);
        }

        return $i;
    }
    public static function page_sign(&$i)
    {
        // $i->pfile = $i->file;
        // if(strpos($i->file,self::PAGE_SIGN)===false) {
        //     $i->pfile = $i->name.'_'.self::PAGE_SIGN.$i->ext;
        // }
        // $i->pageurl  = $i->hdir.'/'.$i->pfile ;
        // $i->pagepath = $i->dir.'/'.$i->pfile;
        $i->href = str_replace(self::PAGE_SIGN, 1, $i->href);
        $i->path = str_replace(self::PAGE_SIGN, 1, $i->path);
        $i->file = str_replace(self::PAGE_SIGN, 1, $i->file);
        $i->name = str_replace(self::PAGE_SIGN, 1, $i->name);
    }
    public static function pageNum($path, $page = false)
    {
        $page === false && $page = $GLOBALS['page'];
        if ($page < 2) {
            return str_replace(array('_' . self::PAGE_SIGN, '&p=' . self::PAGE_SIGN), '', $path);
        }
        return str_replace(self::PAGE_SIGN, $page, $path);
    }
    public static function getPageUrl($param)
    {
        return Paging::url($param);
    }
    public static function make($param = null, $url = null)
    {
        $url or $url = $_SERVER["REQUEST_URI"];
        if (strpos($url, 'route::') !== false) {
            $rkey = substr($url, 8);
            $url  = self::routing($rkey);
        }
        $parse  = parse_url($url);
        parse_str($parse['query'], $query);
        is_array($param) or $param = parse_url_qs($param);
        foreach ($param as $key => $value) {
            //这个null是字符
            if (strtolower($value) === 'null' || $value === null) {
                unset($param[$key]);
                unset($query[$key]);
            }
        }
        $query = array_merge((array)$query, (array)$param);
        $parse['query'] = http_build_query($query);

        $PAGE_SIGN = urlencode(self::PAGE_SIGN);
        if (strpos($parse['query'], $PAGE_SIGN) !== false) {
            $parse['query'] = str_replace($PAGE_SIGN, self::PAGE_SIGN, $parse['query']);
        }
        // if(strpos($parse['path'],'.php')===false) {
        //     $path = '';
        //     foreach ($query as $key => $value) {
        //         $path.= $key.'-'.$value;
        //     }
        //     $parse['path'].= $path.self::$config['ext'];
        // }
        $nurl = self::glue($parse);
        return $nurl ? $nurl : $url;
    }
    public static function glue($parsed)
    {
        if (!is_array($parsed)) return false;

        $uri = isset($parsed['scheme']) ? $parsed['scheme'] . ':' . ((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : '';
        $uri .= isset($parsed['user']) ? $parsed['user'] . ($parsed['pass'] ? ':' . $parsed['pass'] : '') . '@' : '';
        $parsed['host']    && $uri .= $parsed['host'];
        $parsed['port']    && $uri .= ':' . $parsed['port'];
        $parsed['path']    && $uri .= $parsed['path'];
        $parsed['query']   && $uri .= '?' . $parsed['query'];
        $parsed['fragment'] && $uri .= '#' . $parsed['fragment'];
        return $uri;
    }
    public static function URI($qs = null, $url = null)
    {
        $url === null && $url = $_SERVER["REQUEST_URI"];
        $arr = parse_url($url);
        $arr["query"] = self::merge_query($arr["query"], $qs, true);
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
                return sprintf("%08s", $a['id']);;
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
                return sprintf("%08s", $node['id']);
            },
            'AUTHCID' => function ($a, $node, $_node, $time) {
                return rawurlencode(auth_encode($node['id'], $time ?: 0));
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
                return self::PAGE_SIGN;
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
