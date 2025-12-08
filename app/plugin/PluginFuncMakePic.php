<?php

class PluginFuncMakePic
{
    public static function makePic($vars){
        PluginMakePic::$cache = $vars['cache']?true:false;
        $size = $vars['size']?:26;
        echo PluginMakePic::make($vars['text'],$size);
    }
}
