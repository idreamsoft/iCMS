<?php
class LinksApp
{
    public function do_iCMS()
    {
        $this->target();
    }
    public function API_iCMS()
    {
        $this->target();
    }
    public static function target()
    {
        $target = urldecode($_GET['target']);
        $parse  = parse_url($target);
        $whitelist = Cache::get('links/whitelist');
        $blacklist = Cache::get('links/blacklist');
        $isWhite = false;
        $isBlack = false;
        if ($whitelist) foreach ($whitelist as $key => $value) {
            if ($value && strpos($parse['host'], $value) !== false) {
                $isWhite = true;
                break;
            }
        }
        if ($blacklist) foreach ($blacklist as $key => $value) {
            if ($value && strpos($target, $value) !== false) {
                $isBlack = true;
                break;
            }
        }
        View::assign('links', [
            'url' => $target,
            'isWhite' => $isWhite,
            'isBlack' => $isBlack
        ]);
        $template = Config::get('links.template');
        View::display($template?:'/tools/links.target.htm');
    }
}
