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
defined('iPHP') OR exit('What are you doing?');
defined('iPHP_LIB') OR exit('iPHP vendor need define iPHP_LIB');

class VendorToken{
    public $prefix = null;
	public function get(){
        $timestamp = $_SERVER['REQUEST_TIME'];
        $nonce     = substr(md5($_SERVER['HTTP_USER_AGENT']), 8,16).dechex(mt_rand(10000,99999));
        $pieces    = array(iPHP_KEY, $timestamp, $nonce);
        sort($pieces, SORT_STRING);
        $token = sha1(implode($pieces));
        return array($token, $timestamp, $nonce);
	}
    public function signature($token=null,$value=null,$cache_time=3600){
        $key = 'token/'.$this->prefix.substr(md5($token), 8,16);
        if($value==='DELETE'){
            return Cache::del($key);
        }
        if($value){
            return Cache::set($key,$value,$cache_time);
        }else{
            return Cache::get($key);
        }
    }
}
