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

    public static function model()
    {
        return DB::table('plugin_verify');
    }
    public static function config()
    {
        return Config::get('plugin.email');
    }
    public static function send($config)
    {
        $default = Config::get('mail');
        $default['title'] = Config::get('site.name');
        $config = $default + $config;
        $config['subject'] = str_replace(
            ['{title}','{code}'],
            [$config['title'],$config['code']],
            $config['subject']
        );
        $config['body'] = str_replace(
            ['{title}', '{replyto}'],
            [$config['title'], $config['replyto']],
            $config['body']
        );
        $result = Vendor::run('SendMail', array($config));
    }
    public static function check($account, $code)
    {
        if (!preg_match("/^[\w\-\.]+@[\w\-]+(\.\w+)+$/i", $account)) {
            throw new sException('plugin:email:error');
        }

        if (!is_numeric($code) || strlen($code) != 6) {
            throw new sException('plugin:email:empty');
        }

        $status = 1;
        $time = time();
        $smsCodeModel = self::model();
        $model = $smsCodeModel->where(compact('account', 'code', 'status'));
        $row = $model->get();

        if (empty($row)) {
            throw new sException('plugin:email:error');
        }
        if ($row['expire_time'] < $time) {
            throw new sException('plugin:email:expire');
        }

        return $model->update([
            'status' => 2,
            'verify_time' => $time
        ]);
    }
}
