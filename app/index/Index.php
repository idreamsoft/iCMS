<?php
class Index
{
    const APP = 'index';
    const APPID = iCMS_APP_INDEX;

    public static function init()
    {
        self::install();
        self::domain();
        self::rewrite();
    }
    /**
     * rewrite
     *
     * @return void
     */

    public static function rewrite()
    {
        if(View::$gateway == "html")return;

        $REQ = parse_url(iPHP_REQUEST_URI);
        $path = $REQ['path'];
        /* 
        不匹配根目录 / 
        不匹配默认首页 /index.html 
        */
        if ($path == '/' || stripos($path, '/index.') === 0) return;

        require_once __DIR__ . '/Rewrite.php';
        $rewrite = new Rewrite;
        $rewrite->run();
    }
    /**
     * 判断是否安装
     *
     * @return void
     */
    public static function install()
    {
        $path = sprintf('%s/install.lock', iPHP_CONFIG_DIR);
        @is_file($path) or Helper::redirect('./install/index.php');
    }
    /**
     * 节点绑定域名解析
     *
     * @return void
     */
    public static function domain()
    {
        if (View::$gateway == "html") {
            return false;
        }
        $domain = Config::get('node.domain');
        if ($domain) {
            $host = Request::get('host');
            empty($host) && $host = iPHP_REQUEST_HOST;
            $cid = $domain[$host];
            $scheme = Request::scheme();
            $haystack = array('http://', 'https://');
            if (empty($cid) && in_array($scheme, $haystack)) {
                $host = str_replace($haystack, '', $host); //兼容无协议域名
                $cid = $domain[$host];
            }
            if ($cid) {
                nodeApp::node($cid);
                exit;
            }
        }
        return false;
    }
}
