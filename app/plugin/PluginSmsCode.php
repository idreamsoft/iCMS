<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PluginSmsCode
{
    public static $debug = null;

    public static function config()
    {
        return Config::get('plugin.SMS');
    }

    public static function send($account)
    {
        if (!Utils::isPhone($account)) {
            // iJson::error('手机号码错误');
            throw new sException('iCMS.phone.error');
        }
        if ($account) {
            //多个接口 可增加 随机选择 错误重发等功能
            $smsconfig = Config::get('plugin.SMS');
            foreach ($smsconfig as $key => $value) {
                if (!is_array($value)) {
                    continue;
                }
                $value['debug'] = self::$debug;
                if($value['enable']){
                    if ($value['AccessKeyId'] && $value['AccessKeySecret']) {
                        $SMS = Vendor::run($key, $value, true,'sms');
                        // var_dump($key,$SMS);
                        if ($SMS) {
                            $verify = PluginVerify::generate($account, Config::get('plugin.SMS.expire'));
                            $result = $SMS->send($account, ['code' => $verify['code']]);
                            PluginVerify::result($result, $verify);
                            if ($result === true) {
                                $sign = Utils::sign([$account, $verify['code']]);
                                return $sign;
                            }
                        } else {
                            throw new sException('plugin.SMS.forbidden');
                        }
                    } else {
                        throw new sException('plugin.SMS.config.miss');
                    }
                }
            }
        }
    }
}
