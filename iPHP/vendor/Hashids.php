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

Vendor::register('Hashids');

use Hashids\Hashids;

class VendorHashids {
	public $instance;
	public function __construct($param=array()){
        empty($param['salt'])&& $param['salt'] = iPHP_KEY;
        empty($param['len']) && $param['len'] = 8;
    	$this->instance = new Hashids($param['salt'],$param['len']);
	}
    public function encode() {
        $numbers = func_get_args();
        return call_user_func_array(array($this->instance, 'encode'),$numbers);
    }
    public function decode($hash) {
    	return $this->instance->decode($hash);
    }
}

