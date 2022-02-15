<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
class PluginDownloadHook{
    /**
     * [插件:正文文件下载]
     * @param [type] $content  [参数]
     * @param [type] $resource [返回替换过的内容]
     */
    public static function run($content) {
        Plugin::init(__CLASS__);
        preg_match_all('#<a\s*class="attachment".*?href=["|\'](.*?)["|\'].*?</a>#is',$content, $variable);
        foreach ((array)$variable[1] as $key => $path) {
           $urlArray[$key]= FilesApp::getUrl(basename($path));
        }
        if($urlArray){
            $content = str_replace($variable[1], $urlArray, $content);
        }
        return $content;
    }
    public static function markdown($content) {
        $_content = str_replace('![', "\n![", $content);
        preg_match_all('/!\[(.*?)\]\((.+)\)/', $_content, $matches);

        foreach ((array)$matches[2] as $key => $url) {
            $path = FilesClient::getPath($url,'-http');
            // $rootpath = FilesClient::getPath($url,'http2root');
            // list($w,$h,$type) = @getimagesize($rootpath);
            // if(empty($type)){
            $ext = File::getExt($path);
            if (!in_array($ext, FilesPic::$EXTS)) {
                $name = basename($path);
                $title = trim($matches[1][$key]);
                empty($title) && $title = $name;
                $durl = FilesApp::getUrl($name);
                $title = Security::escapeStr(addslashes($title));
                $replace[$key] = '<a class="attachment" href="'.$durl.'" target="_blank" title="点击下载['.$title.']">'.$title.'</a>';
                $search [$key] = trim($matches[0][$key]);
            }
        }

        if($replace && $search){
            $content = str_replace($search, $replace, $content);
        }
        return $content;
    }
}
