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
defined('iPHP_LIB') or exit('iPHP vendor need define iPHP_LIB');

require __DIR__ . '/TBAPI.class.php';

function VendorTBAPI()
{
	if (isset($GLOBALS['TBAPI'])) return $GLOBALS['TBAPI'];

	$GLOBALS['TBAPI'] = new TBAPI;
	return $GLOBALS['TBAPI'];
}
