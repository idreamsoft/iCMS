<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PluginVerify extends Model
{
    public $table = 'plugin_verify';
    /**
     * 默认过期时间
     */
    const EXPIRE = 15 * 60;

    public static function generate($account, $expire = 0)
    {
        $code = random(6, true);
        $expire or $expire = self::EXPIRE;
        $create_time = time();
        $expire_time = $create_time + ($expire ?: 90);
        $verify_time = 0;
        $status = 0;

        // $row = PluginVerify::field('create_time')->where([
        //     'account'=>$account,
        //     'status'=>'0',
        //     'expire_time'=>['>=',$create_time]
        // ])->orderby("id DESC")->get();

        // if($row){
        //     throw new sException('plugin.verify.generate.expire');
        // }

        DB::beginTransaction();
        $data = compact('account', 'code', 'create_time', 'expire_time', 'status');
        $data['id'] = self::create($data, true);
        $data['expire'] = $expire;
        if (empty($data['id'])) {
            $msg = 'plugin:email:verify:generate_error';
            Utils::isPhone($account) && $msg = 'plugin:phone:verify:generate_error';
            throw new sException($msg);
        }
        DB::commit();
        return $data;
    }

    public static function result($result, $data)
    {
        DB::beginTransaction();
        $update = [];
        $update = $result === true ?
            ['status' => 1, 'result'=>'ok'] :
            ['status' => -1, 'result'=>json_encode($result)];
        $update && self::update($update, $data['id']);
        DB::commit();

        if ($result === true) {
            return true;
        } else {
            $msg = sprintf('Message:%s,Code:%s,RequestId:%s', $result['Message'], $result['Code'], $result['RequestId']);
            throw new sException($msg);
        }
    }

    /**
     * 判断账号+验证码 是否通过验证
     *
     * @param   [type]  $account  [$account description]
     * @param   [type]  $code     [$code description]
     * @param   [type]  $expire   [$expire 验证后多少秒内有效]
     *
     * @return  [type]            [return description]
     */
    public static function verify($account, $code, $expire = 180)
    {
        if($expire==='email'){
            $config = PluginEmail::config();
            $expire = $config['expire']?:86400;
        }
        $status = 2;
        $time = time();
        $row = self::where(compact('account', 'code', 'status'))->get();
        if (empty($row) || $time - $row['verify_time'] > $expire) {
            if (Utils::isPhone($account)) {
                throw new sException('plugin:phone:verify_fail');
            }else{
                throw new sException('plugin:email:verify_fail');
            }
        }
        return true;
    }

    public static function phone($phone, $code)
    {
        if (!Utils::isPhone($phone)) {
            // iJson::error('手机号码错误');
            throw new sException('plugin:phone:error');
        }

        return self::checking($phone, $code, 'phone');
    }

    public static function email($email, $code)
    {
        if (!preg_match("/^[\w\-\.]+@[\w\-]+(\.\w+)+$/i", $email)) {
            throw new sException('plugin:email:error');
        }
        return self::checking($email, $code, 'email');
    }

    public static function checking($account, $code, $type)
    {
        if (!is_numeric($code) || strlen($code) != 6) {
            throw new sException('plugin:' . $type . ':verify:illegal');
        }

        $status = 1;
        $time = time();
        $row = self::where(compact('account', 'code', 'status'))->get();

        if (empty($row)) {
            throw new sException('plugin:' . $type . ':verify:error');
        }
        if ($row['expire_time'] < $time) {
            throw new sException('plugin:' . $type . ':verify:expire');
        }
        return self::update([
            'status'      => 2,
            'verify_time' => $time
        ]);
    }
}
