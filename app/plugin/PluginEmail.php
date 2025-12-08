<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PluginEmail
{
    public static $debug = null;

    public static function config()
    {
        return Config::get('mail');
    }

    public static function send($config,$code=false)
    {
        if ($default = self::config()) {
            $default['title'] = Config::get('site.name');
            $config = array_merge($default, $config);
            if(empty($config['address'])){
                throw new sException('plugin:mail:address:empty');
            }
            $expire = '';
            if($config['address'][0] && $code ===true){
                $verify = PluginVerify::generate($config['address'][0],$default['expire']);
                $config['code'] = $verify['code'];
                $expire = ($verify['expire']/60).Lang::get('Minute');
            }else{
                $code && $config['code'] = $code;
            }

            $config['subject'] = str_replace(
                ['{title}', '{code}'],
                [$config['title'], $config['code']],
                $config['subject']
            );
            $config['body'] = str_replace(
                ['{title}', '{replyto}', '{code}','{expire}'],
                [$config['title'], $config['replyto'], $config['code'],$expire],
                $config['body']
            );
            $result = Vendor::run('SendMail', [$config]);
            if($verify){
                return PluginVerify::result($result,$verify);
            }else{
                return $result;
            }
        } else {
            throw new sException('plugin:mail:config:miss');
        }
    }
}
