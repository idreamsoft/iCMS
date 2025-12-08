<?php

class ApiApp
{
    public function API_iCMS()
    {
        $s = Request::get('s');
        $array = explode('/', ltrim($s, '/'));
        $func = [
            sprintf('%sApiApp', ucfirst($array[0])),
            sprintf('do_%s', $array[1] ?: iPHP_APP)
        ];
        try {
            return iPHP::invoke($func);
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            iJson::error($msg, -999);
        }
    }

    //api.php?do=wxMiniProgram
    public function done_wxMiniProgram()
    {
        return $this->wxMiniProgram();
    }

    public function api_wxMiniProgram()
    {
        return $this->wxMiniProgram();
    }

    public function wxMiniProgram()
    {
        $s = Request::get('s'); //WeixinMini/config
        $v = Request::get('v'); //v1.0.0
        $array = explode('/', ltrim($s, '/'));
        $vArr = explode('.', $v); //v1
        $func = [
            //WeixinMiniApiV1App
            sprintf('WeixinMiniApi%sApp', ucfirst($vArr[0])),
            sprintf('api_%s', $array[1] ?: iPHP_APP)
        ];
        try {
            return iPHP::invoke($func);
        } catch (\Exception $ex) {
            $msg = $ex->getMessage();
            $code = $ex->getMessage();
            iJson::error($msg, $code);
        }
    }
}
