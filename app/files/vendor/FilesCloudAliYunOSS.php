<?php

/**
 * 阿里云oss iPHP接口 统一
 */
// define('ALI_DISPLAY_LOG', 1);
defined('iPHP') or exit('What are you doing?');

require dirname(__FILE__) . '/library/AliYunOSS.class.php';

class FilesCloudAliYunOSS extends ALIOSS
{
    public $conf;
    public function __construct($conf)
    {
        $this->conf = $conf;
        parent::__construct($conf['AccessKey'], $conf['SecretKey'], $conf['BucketDomain']);
        $this->set_debug_mode(FALSE);
    }
    /**
     * [_upload_file 上传文件接口]
     * @param  [type] $fileRootPath [文件绝对路径]
     * @param  [type] $filePath [文件路径]
     * @return [type]           [description]
     */
    public function _upload_file($fileRootPath, $filePath)
    {
        $options = array(
            ALIOSS::OSS_HEADERS => array(
                'Cache-control' => 'max-age=864000',
            )
        );
        $this->path($filePath);
        $response = $this->upload_file_by_file(
            $this->conf['Bucket'],
            $filePath,
            $fileRootPath,
            $options
        );
        unset($response->header);
        $response->body && $response->body = simplexml_load_string($response->body, 'SimpleXMLElement', LIBXML_NOCDATA);
        return json_encode(array(
            'error' => !$response->isOk(),
            'url'   => $this->url($filePath, false),
            'msg'   => $response
        ));
    }
    /**
     * [_delete_file 删除文件接口]
     * @param  [type] $filePath [文件路径]
     * @return [type]           [description]
     */
    public function _delete_file($filePath)
    {
        $this->path($filePath);
        $response = $this->delete_object(
            $this->conf['Bucket'],
            $filePath
        );
        unset($response->header);
        $response->body && $response->body = simplexml_load_string($response->body, 'SimpleXMLElement', LIBXML_NOCDATA);
        return json_encode(array(
            'error' => !$response->isOk(),
            'msg'   => $response
        ));
    }
    public function url($filePath, $flag = true)
    {
        $flag && $this->path($filePath);
        $domain = $this->conf['domain'] ?: str_replace('-internal', '', $this->conf['BucketDomain']);
        $url = sprintf(
            '%s/%s',
            rtrim($domain, '/'),
            ltrim($filePath, '/')
        );
        Request::isUrl($url) or $url = sprintf(
            '%s://%s',
            parse_url(iCMS_URL, PHP_URL_SCHEME),
            $url
        );
        return $url;
    }
    public function path(&$filePath)
    {
        $this->conf['Dir'] && $filePath = sprintf('%s/%s', $this->conf['Dir'], $filePath);
    }
}
// require dirname(__FILE__).'/../../iCMS.php';
// $conf = Config::get('cloud.vendor.AliYunOSS');
// $cloud = new files_cloud_AliYunOSS($conf);
// $filePath = '2017/02-08/23/01b71d15d5bc0de1c15e1beb4be128ea.jpg';
// $fileRootPath= FilesClient::getPath($filePath,'+root');
// $response = $cloud->_upload_file($fileRootPath,$filePath);
// // $response = $cloud->_delete_file($filePath);
// print_r($response);
