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

    public static function model()
    {
        return DB::table('plugin_verify');
    }
    public static function config()
    {
        return Config::get('plugin.SMS');
    }
    public static function send($account)
    {

        if (!preg_match('/^1[34578]\d{9}$/', $account)) {
            // iJson::error('手机号码错误');
            throw new sException('iCMS:phone:error');
        }
        if ($account) {
            //多个接口 可增加 随机选择 错误重发等功能
            if ($aliyun = Config::get('plugin.SMS.aliyun')) {
                $aliyun['debug'] = self::$debug;
                if ($aliyun['AccessKeyId'] && $aliyun['AccessKeySecret']) {
                    $SMS = Vendor::run('aliYunSMS', $aliyun, true);
                    if ($SMS) {
                        $code = random(6, true);
                        $expire = Config::get('plugin.SMS.expire');
                        $create_time = time();
                        $expire_time = $create_time + ($expire ?: 90);
                        $verify_time = 0;
                        $status = 0;
                        $data = compact('account', 'code', 'create_time', 'expire_time', 'status');
                        $id = self::model()->create($data, true);
                        if (empty($id)) {
                            throw new sException('plugin:db:error');
                        }
                        $result = $SMS->send($account, ['code' => $code]);
                        if ($result === true) {
                            self::model()->update(['status' => 1], $id);
                            $sign = Utils::sign([$account, $code]);
                            return $sign;
                        } else {
                            $msg = sprintf('Message:%s,Code:%s,RequestId:%s', $result['Message'], $result['Code'], $result['RequestId']);
                            throw new sException($msg);
                        }
                    }
                    throw new sException('plugin:SMS:forbidden');
                } else {
                    throw new sException('plugin:SMS:config:miss');
                }
            }
        }
    }
    public static function check($account, $code)
    {
        if (!preg_match('/^1[34578]\d{9}$/', $account)) {
            // iJson::error('手机号码错误');
            throw new sException('iCMS:phone:error');
        }
        if (!is_numeric($code) || strlen($code) != 6) {
            // iJson::error('短信验证码错误');
            throw new sException('plugin:SMS:illegal');
        }

        $status = 1;
        $time = time();
        $smsCodeModel = self::model();
        $model = $smsCodeModel->where(compact('account', 'code', 'status'));
        $row = $model->get();

        if (empty($row)) {
            throw new sException('plugin:SMS:error');
            // iJson::error('短信验证码错误');
        }
        if ($row['expire_time'] < $time) {
            throw new sException('plugin:SMS:expire');
            // iJson::error('短信验证码已过期');
        }

        return $model->update([
            'status' => 2,
            'verify_time' => $time
        ]);
    }
    /**
     * [判断手机号+验证码 是否通过验证]
     *
     * @param   [type]  $account  [$account description]
     * @param   [type]  $code     [$code description]
     * @param   [type]  $expire   [$expire 验证后多少秒内有效]
     *
     * @return  [type]            [return description]
     */
    public static function verify($account, $code, $expire = 180)
    {
        $status = 2;
        $time = time();
        $smsCodeModel = self::model();
        $model = $smsCodeModel->where(compact('account', 'code', 'status'));
        $row = $model->get();
        // echo DB::getQueryLog();
        if (empty($row) || $time - $row['verify_time'] > $expire) {
            return false;
        }
        return true;
    }
}
