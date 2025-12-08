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

class VendorToken
{
    public $prefix = null;

    public function get()
    {
        $timestamp = $_SERVER['REQUEST_TIME'];
        $nonce = random(16);
        $pieces = [iPHP_KEY, $timestamp, $nonce];
        sort($pieces, SORT_STRING);
        $token = sha1(implode($pieces));
        return [$token, $timestamp, $nonce];
    }

    public function signature($token = null, $value = null, $cache_time = 3600)
    {

    }
}
