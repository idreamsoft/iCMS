<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');

class UserOrder
{

    /**
     * [notify_process 支付成功回调 更新 status,pay_time,openid,transaction_id]
     * @param  [type] $data     [支付通知数组]
     * @param  [type] $trade_id [支付订单ID]
     * @return [type]           [description]
     */
    public static function notify_process($data, $trade_id)
    {
        var_dump($data, $trade_id);
        $order_no = addslashes($data['order_no']);
        // DB::update('donate',
        //     array(
        //         'status'         =>'1',
        //         'pay_time'       => time(),
        //         'openid'         => $data['buyer_id'],
        //         'transaction_id' => $data['transaction_id'],
        //     ),
        //     array('order_no'=>$order_no)
        // );
    }
    /**
     * [callback_update 支付下单完成,更新产品数据]
     * @param  [type] $result  [返回结果]
     * @param  [type] $payment [支付实例]
     * @param  [type] $ret     [下单结果]
     * @return [type]          [description]
     */
    public static function callback_update(&$result, $payment, $ret)
    {
        is_array($ret) && $ret = json_encode($ret);
        // var_dump('sdfsdf');
        // var_dump($result,$payment,$ret);
        // DB::update('donate',
        //     array(
        //         'data'     => addslashes($ret),
        //         'app_id'   => $payment->config['app_id'],
        //         'trade_id' => $payment->paydata['trade_id'],
        //         'order_no' => $payment->paydata['order_no'],
        //     ),
        //     array('id'=>$payment->paydata['product_id'])
        // );
    }
    public static function add($subject, $value, $type, $data = [])
    {
        if (strpos($subject, ':') !== false) {
            $subject = Lang::get($subject);
        }
        $time = time();
        $order = [
            'type' => (int)$type,
            'subject' => $subject,
            'value' => $value,
            'client_ip' => Request::ip(),
            'create_time' => $time,
            'status' => 1,
        ] + $data;
        empty($order['order_no']) && $order['order_no'] = Utils::makeId();
        empty($order['userid']) && $order['userid'] = User::$id;

        return UserOrderModel::create($order, true);
    }
}
