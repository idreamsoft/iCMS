<?php

/**
 * 阿里云oss iPHP接口 统一
 */

defined('iPHP') or exit('What are you doing?');


class FilesCloudAliYunOSS
{
    public $conf;
    public $client;
    public $response;
    public function __construct($conf)
    {
        $this->conf = $conf;
        $this->client = new OSS\OssClient(
            $conf['AccessKey'],
            $conf['SecretKey'],
            $conf['Endpoint']
        );
    }
    /**
     * 上传文件接口
     * @param  [type] $rootPath [文件绝对路径]
     * @param  [type] $path [文件路径]
     * @return void
     */
    public function upload($rootPath, $path)
    {
        $this->response = $this->client->uploadFile(
            $this->conf['Bucket'],
            $this->path($path),
            $rootPath
        );

        return [
            "url" => $this->url($path)
        ];
    }
    /**
     * 删除文件接口
     * @param  [type] $path [文件路径]
     * @return void
     */
    public function delete($path)
    {
        $this->response = $this->client->deleteObject(
            $this->conf['Bucket'],
            $this->path($path)
        );
        return true;
    }
    public function url($path)
    {
        $path = $this->path($path);
        $domain = $this->conf['Domain'];
        if (empty($domain)) {
            $domain = sprintf(
                '%s.%s',
                $this->conf['Bucket'],
                str_replace('-internal', '', $this->conf['Endpoint'])
            );
        }
        $url = sprintf('%s/%s', rtrim($domain, '/'), ltrim($path, '/'));
        Request::isUrl($url) or $url = sprintf(
            '%s://%s',
            parse_url(iCMS_URL, PHP_URL_SCHEME),
            $url
        );
        return $url;
    }
    public function path($path)
    {
        $this->conf['Dir'] && $path = sprintf('%s/%s', $this->conf['Dir'], $path);
        return $path;
    }
}
// require dirname(__FILE__).'/../../iCMS.php';
// $conf = Config::get('cloud.vendor.AliYunOSS');
// $cloud = new FilesCloudAliYunOSS($conf);
// $filePath = '2017/02-08/23/01b71d15d5bc0de1c15e1beb4be128ea.jpg';
// $fileRootPath= FilesClient::getPath($filePath,'+root');
// $response = $cloud->upload($fileRootPath,$filePath);
// // $response = $cloud->delete($filePath);
// print_r($response);
