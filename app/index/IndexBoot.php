<?php

class IndexBoot
{
    const APP = 'index';
    const APPID = iCMS_APP_INDEX;
    public static $PATH = '';

    public static function init()
    {
        self::install();
        self::start();
    }

    public static function path()
    {
        if (stripos(iPHP_REQUEST_URL, iCMS_URL) === 0) {
            $path = str_replace(iCMS_URL, '', iPHP_REQUEST_URL);
        } else {
            $path = iPHP_REQUEST_URI;
            // $request_host = parse_url(iPHP_REQUEST_URL, PHP_URL_HOST);
            // $adapter_host = parse_url(iCMS_URL, PHP_URL_HOST);
            // if ($request_host != $adapter_host) {
            //     $redirect_url = str_replace($request_host, $adapter_host, iPHP_REQUEST_URL);
            //     // Request::status(301);
            //     Utils::jumpto($redirect_url);
            // }
        }
        $query = parse_url($path, PHP_URL_QUERY);
        $query && $path = str_replace($query, '', $path);
        $path = rtrim($path, '?&');
        self::$PATH = sprintf('/%s', ltrim($path, '/'));
        return self::$PATH;
    }

    /**
     * start ? rewrite : run
     *
     * @return void
     */
    public static function start()
    {
        // $rewrite = Config::get('route.rewrite') || Request::param('rewrite');

        // if (empty($rewrite)) {
        //     return iCMS::run();
        // }
        if(isset($_GET['app']) && isset($_GET['do'])){
            return iCMS::run();
        }
        if (View::$gateway == 'html') {
            return;
        }

        $path = self::path();
        /*
        不匹配根目录 /
        不匹配默认首页 /index.html
        */
        if ($path == '/' || stripos($path, '/index.') === 0 || stripos($path, '/index_') === 0) {
            return self::nodeDomain() or iCMS::run();
        }

        $rewrite = new IndexRewrite($path);
        return $rewrite->run();
    }

    public static function exception($ex)
    {
        $code = $ex->getCode();
        if ($code == '-899') {
            if (self::install() === true) {
                throw new sException('You have already installed it, but cannot find the database configuration file');
            }
        } else {
            throw $ex;
        }
    }

    /**
     * 判断是否安装
     *
     * @return void
     */
    public static function install()
    {
        $path = sprintf('%s/install.lock', iPHP_CONFIG_DIR);
        @file_exists($path) or Utils::jumpto('./install/index.php');
        return true;
    }

    /**
     * 节点绑定域名解析
     *
     * @return bool
     */
    public static function nodeDomain()
    {
        if (View::$gateway == 'html') {
            return ;
        }

        $domain = Config::get('node.domain');
        $host = Request::get('host', iPHP_REQUEST_HOST);
        if ($domain) {
            $nodeId = $domain[$host];
            $scheme = Request::scheme();
            $haystack = ['http://', 'https://'];
            if (empty($nodeId) && in_array($scheme, $haystack)) {
                $host = str_replace($haystack, '', $host); //兼容无协议域名
                $nodeId = $domain[$host];
            }
            if ($nodeId) {
                $_GET['id'] = $nodeId;
                return iCMS::run('node', 'index');
            } else {
                // $host = str_replace($haystack, '', $host);
                // if (stripos(iCMS_URL, $host) === false) {
                //     Request::status(404, '404');
                //     exit;
                // }
            }
        }
        return false;
    }
}
