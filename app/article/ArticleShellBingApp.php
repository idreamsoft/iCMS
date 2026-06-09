<?php
// Bing 推送
// php run.php article bing

class ArticleShellBingApp
{
    const HOST = '你的域名';
    const KEY = '32位的API密钥';

    public static function shell_bing()
    {
        $list = ArticleFunc::node([
            'row' => 100
        ]);

        $urls = [];
        foreach ($list as $key => $value) {
            $urls[] = $value['url'];
            foreach ((array)iCMS::$config['template']['device'] as $key => $device) {
                $lg = $device['lang'];
                $urls[] = $value['iurl'][$lg]['url'];
            }
        }

        $list = ArticleFunc::lists([
            'row' => 1000
        ]);

        // $urls = [];
        foreach ($list as $key => $value) {
            $urls[] = $value['url'];
            foreach ((array)iCMS::$config['template']['device'] as $key => $device) {
                $lg = $device['lang'];
                $urls[] = $value['iurl'][$lg]['url'];
            }
        }
        $list = TagFunc::lists([
            'row' => 100
        ]);

        // $urls = [];
        foreach ($list as $key => $value) {
            $urls[] = $value['url'];
            foreach ((array)iCMS::$config['template']['device'] as $key => $device) {
                $lg = $device['lang'];
                $urls[] = $value['iurl'][$lg]['url'];
            }
        }
        $ret = self::submitUrlsToIndexNowWithCurl(self::HOST, self::KEY, $urls);
        print_r($ret);
    }
    public static function submitUrlsToIndexNowWithCurl($host, $key, $urlList)
    {
        // 准备POST数据
        $data = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => sprintf("https://%s/%s.txt", $host, $key),
            'urlList' => array_map('trim', $urlList)
        ];

        // 转换为JSON格式
        $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES);

        // 初始化cURL
        $ch = curl_init();

        // 设置cURL选项
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.indexnow.org/IndexNow',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Host: api.indexnow.org'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true
        ]);

        // 执行请求
        $response = curl_exec($ch);

        // 获取HTTP状态码
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // 关闭cURL
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'response' => $response,
            'request_data' => $jsonData
        ];
    }
}
