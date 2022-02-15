<?php
class ApiApp
{
    public function API_iCMS()
    {
        $s = Request::get('s');
        $array = explode('/', ltrim($s, '/'));
        $func = [
            sprintf("%sApiApp", ucfirst($array[0])),
            sprintf("do_%s", $array[1]?:iPHP_APP)
        ];
        try {
            return iPHP::invoke($func);
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            iJson::error($msg, -999);
        }
    }
    public function API_wxMiniProgram()
    {
        $s = Request::get('s');
        $v = Request::get('v');
        $array = explode('/', ltrim($s, '/'));
        $v = str_replace('.','',$v);
        $func = [
            ///sprintf("%sApp", ucfirst($array[0])),
            sprintf("WeixinMiniApi%sApp", ucfirst($v)),
            sprintf("do_%s", $array[1]?:iPHP_APP)
        ];
        try {
            // View::$gateway = 'json';
            // View::set_template_dir(iPHP_APP_DIR.'/weixin/template');
            return iPHP::invoke($func);
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            iJson::error($msg, -999);
        }
    }
}
