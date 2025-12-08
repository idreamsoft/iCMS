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
    public const TYPE_CHARGE = 1; // 充值
    public const TYPE_VIP = 2; // 会员
    public const TYPE_CONSUME = 3; // 消费
    public const TYPE_BUY = 4; // 购买
    public const TYPE_INCOME = 5; //  收益

    public const STATUS_UNPAID = 0; //  未支付
    public const STATUS_PAID = 1; //  已支付
    public const STATUS_CANCEL = 3; //  已取消
    public static $nameMap = [
        'money'  => '金币',
        'credit' => '积分',
        'scores' => '点数',
        'free' => '免费次数',
        'time'   => '时长',
        'count'  => '次',
    ];

    /**
     * notify_process 支付成功回调 更新 status,pay_time,openid,transaction_id
     * @param  [type] $data     [支付通知数组]
     * @param  [type] $trade_id [支付订单ID]
     * @return void
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
     * callback_update 支付下单完成,更新产品数据
     * @param  [type] $result  [返回结果]
     * @param  [type] $payment [支付实例]
     * @param  [type] $ret     [下单结果]
     * @return void
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

    /**
     * 检测会员是否过期
     *
     * @return void
     */
    public static function checkAndUpdateUserRole()
    {
        // 查询当前用户所有已支付、未过期的 VIP 订单
        $order = UserOrderModel::where([
            'userid'       => User::$id,
            'type'         => self::TYPE_VIP,
            'expire_time'  => ['>', time()],
            'status'       => self::STATUS_PAID,
        ])->select();

        if (!empty($order)) {
            $role_ids = array_column($order, 'value');
            $roleList = RoleModel::field('id,level')->where(['id' => $role_ids, 'type'=>'0'])->select();
            // 将角色 level 放入 order 中方便排序
            foreach ($order as &$item) {
                foreach ($roleList as $role) {
                    if ($item['value'] == $role['id']) {
                        $item['role_id'] = $role['id'];
                        $item['level'] = $role['level'];
                        break;
                    }
                }
            }

            // 对订单按 会员级等级权重 level 降序排序，确保取到最高等级
            usort($order, function ($a, $b) {
                if ($b['level'] != $a['level']) {
                    return (int)$b['level'] <=> (int)$a['level']; // 先按等级排序
                } else {
                    return (int)$b['expire_time'] <=> (int)$a['expire_time']; // 同等级则看有效期
                }
            });
            $role_id = User::$data['role_id'];
            // 取最高等级
            $highestRole = $order[0]['role_id'];
            // 如果当前角色不等于最高等级，则更新
            if (User::$data['role_id'] != $highestRole) {
                // 假设有一个方法可以更新用户表的 role_id 字段
                UserModel::update([
                    'role_id' => $highestRole,
                ], User::$id);

                $role_id = $highestRole;
            }
        } else {
            $role_id = User::defaultRole(); // 获取默认角色 ID
            // 所有会员都过期了，设置默认
            //BUG 不能手动测试用户等级会被还原成默认角色
            if (User::$data['role_id'] != $role_id) {
                UserModel::update([
                    'role_id' => $role_id,
                ], User::$id);
            }
        }
        if ($role_id && User::$data['role_id'] != $role_id) {
            User::$data['role_id'] = $role_id;
            User::$data['role'] = Role::get($role_id);
        }
    }

    /**
     * 检查内容是否购买,不限支付方式
     *
     * @param string  $id 商品ID
     * @param string $app 商品类型 一般填写 APP NAME
     * @return bool
     */
    public static function checkContent($id, $app, $userid = null)
    {
        return UserOrderModel::check([
            'userid'      => $userid === null ? User::$id : $userid,
            'type'        => self::TYPE_CONSUME,
            'status'      => self::STATUS_PAID,
            'product_id'  => $id,
            'product_type'=> $app,
        ]);
    }

    /**
     * 检查是否使用free点数
     *
     * @param string $app 商品类型 一般填写 APP NAME
     * @param int $userid 用户ID
     * @return bool
     */
    public static function checkFree($app, $date = 'today', $userid = null)
    {
        $where = [
            'userid'      => $userid === null ? User::$id : $userid,
            'type'        => self::TYPE_CONSUME,
            'name'        => 'free',
            'status'      => self::STATUS_PAID,
            'product_type'=> $app,
        ];
        if ($date) {
            $date = date('Y-m-d',strtotime($date));
            $where['create_time'] = ['between', [strtotime($date . ' 00:00:00'), strtotime($date . ' 23:59:59')]];
        }
        $value = (int)UserOrderModel::field('sum(value) as sum')
            ->where($where)
            ->value();
        return abs($value);
    }

    /**
     * 检查是否购买
     *
     * @param [type] $id 商品ID
     * @param [type] $app 商品类型 一般填写 APP NAME
     * @param [type] $name 支付类型 money/credit/scores
     * @return bool
     */
    public static function check($id, $app, $name, $userid = null)
    {
        $name = in_array($name, ['money', 'credit', 'scores']) ? $name : 'money';
        return UserOrderModel::check([
            'userid'      => $userid === null ? User::$id : $userid,
            'type'        => self::TYPE_CONSUME,
            'name'        => $name,
            'status'      => self::STATUS_PAID,
            'product_id'  => $id,
            'product_type'=> $app,
        ]);
    }

    public static function add($subject, $value, $name, $type, $data = [])
    {
        // if (strpos($subject, ':') !== false) {
        //     $subject = Lang::get($subject);
        // }
        // 消费订单金额为负数
        if ($type == self::TYPE_CONSUME) {
            $value = -abs((float)$value);
        }
        $time = time();
        $order = array_merge([
            'type'        => (int)$type,
            'subject'     => $subject,
            'name'        => $name,
            'value'       => $value,
            'client_ip'   => Request::ip(),
            'create_time' => $time,
            'status'      => 1,
        ], $data);
        empty($order['order_no']) && $order['order_no'] = Utils::makeId();
        empty($order['userid']) && $order['userid'] = User::$id;
        empty($order['pay_time']) && $order['pay_time'] = $time;

        return UserOrderModel::create($order, true);
    }

    /**
     * money/credit/scores 订单
     *
     * @param float $amount 积分金额
     * @param int $id 商品ID
     * @param string $title 商品标题
     * @param string $app 商品类型 一般填写 APP NAME
     * @param string $name 支付类型 money/credit/scores
     * @return void
     */
    public static function used($amount, $id, $title, $app, $name)
    {
        $name = in_array($name, ['money', 'credit', 'scores']) ? $name : 'credit';
        $userid = User::$id;
        $amount = (float)$amount;
        $credit = (float)User::$data[$name] - $amount;
        if ($credit < 0) {
            iPHP::alert('Insufficient ' . $name);
        }

        UserModel::where($userid)->update([$name=>$credit]);
        UserOrder::add(
            'user:order:consume',
            $amount,
            $name,
            UserOrder::TYPE_CONSUME,
            [
                'product_id'  => $id,
                'product_type'=> $app,
                'remark'      => $title,
            ]
        );
        return $credit;
    }

    // 积分订单
    public static function credit($amount, $id, $title, $app)
    {
        return self::used($amount, $id, $title, $app, 'credit');
    }

    // 点数订单
    public static function scores($amount, $id, $title, $app)
    {
        return self::used($amount, $id, $title, $app, 'scores');
    }

    // 充值金额订单
    public static function money($amount, $id, $title, $app)
    {
        return self::used($amount, $id, $title, $app, 'money');
    }

    /**
     * 订单数据处理
     *
     * @param array $params
     * @return array
     */
    public static function data($params)
    {
        $time = time();
        list($notify, $trade, $product) = $params;
        $pay_time = $trade['pay_time'];
        if (empty($pay_time) && $notify['pay_time']) {
            $pay_time = strtotime($notify['pay_time']);
        }
        $data = [
            'channel'        => $trade['channel'],
            'name'           => $product['unitName'],
            'remark'         => $product['remark'],
            'order_no'       => $trade['order_no'],
            'product_id'     => $product['id'],
            'product_type'   => $product['type'],
            'product_event'  => $product['event'],
            'transaction_id' => $notify['transaction_id'],
            'amount'         => $trade['amount'],
            'userid'         => $trade['userid'],
            'trade_id'       => $trade['id'],
            'pay_time'       => $pay_time ?: $time,
            'status'         => 1,
        ];
        if ($product['expire']) {
            $expire = $product['expire'] * 86400;
            $data['expire_time'] = $time + $expire;
        }
        return $data;
    }
}
