<?php
class LinksHook
{
    /**
     * [钩子:外链跳转]
     * @param String $content [参数]
     * @return string        [返回替换过的内容]
     */
    public static function run($content)
    {
        preg_match_all("/<a.*?(href=[\"|'](.+?)[\"|'])[^>]*?>/", $content, $matches);
        if ($matches[2]) {
            $base = Config::get('links.base');
            $urls = array_unique($matches[2]);
            $search = $matches[1];
            usort($urls,[__CLASS__,'sortlen']);
            usort($search,[__CLASS__,'sortlen']);
            $replace = [];
            if ($urls) foreach ($urls as $key => $url) {
                $target = Route::make(['target' => $url], $base);
                $replace[$key] = str_replace($url, $target, $search[$key]);
            }
            $content = str_replace($search, $replace, $content);
        }
        return $content;
    }
    public static function sortlen($a, $b)
    {
        $al = strlen($a);
        $bl = strlen($b);
        if ($al  ==  $bl) {
            return  0;
        }
        return ($al  >  $bl) ? -1  :  1;
    }
}
