<?php
class LinksHook
{
    /**
     * 钩子:外链跳转中间页
     * @param String $content [参数]
     * @return string        [返回替换过的内容]
     */
    public static function field_hook($content)
    {
        preg_match_all("/<a.*?(href=[\"|'](.+?)[\"|'])[^>]*?>/", $content, $matches);
        if ($matches[2]) {
            $base = Config::get('links.base');
            $urls = array_unique($matches[2]);
            $search = $matches[1];
            usort($urls,'sortlen');
            usort($search,'sortlen');
            $replace = [];
            if ($urls) foreach ($urls as $key => $url) {
                $target = Route::make(['target' => $url], $base);
                $replace[$key] = str_replace($url, $target, $search[$key]);
            }
            $content = str_replace($search, $replace, $content);
        }
        return $content;
    }
}
