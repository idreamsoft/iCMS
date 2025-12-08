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
class SpiderBase extends AdmincpCommon
{
	public function __construct()
	{
		parent::__construct();

		Spider::$cid   = (int) Request::get('cid');
		Spider::$rid   = (int) Request::get('rid');
		Spider::$pid   = (int) Request::get('pid');
		Spider::$urlId = (int) Request::get('urlId');
		Spider::$title = Request::get('title');
		Spider::$url   = Request::get('url');
		Spider::$poid  = (int) Request::get('poid');
	}
}
