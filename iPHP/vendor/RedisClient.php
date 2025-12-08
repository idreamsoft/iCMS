<?php
/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
defined('iPHP') or exit('What are you doing?');
defined('iPHP_LIB') or exit('iPHP vendor need define iPHP_LIB');
// if (extension_loaded('Redis')) {
//     class RedisClient
//     {
//         public $redis = null;

//         public function __construct($config = [])
//         {
//             var_dump($config);
//             $this->redis = new Redis([
//                 'host'           => $config['host'],
//                 'port'           => (int)$config['port'],
//                 'connectTimeout' => 2.5,
//                 // 'auth' => ['phpredis', 'phpredis'],
//                 // 'ssl'     => ['verify_peer' => false],
//                 // 'backoff' => [
//                 //     'algorithm' => Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER,
//                 //     'base'      => 500,
//                 //     'cap'       => 750,
//                 // ],
//             ]);
//             $this->redis->select($config['db'] ?: 0);
//         }

//         public function add($key, $val, $exp = 0)
//         {
//             $this->_file = $this->file($key, 'add');
//             return $this->store($key, $val, $exp);
//         }

//         public function get($key)
//         {
//         }
//     }
// } else {
// }
    require_once iPHP_LIB . '/Redis/RedisClient.class.php';
