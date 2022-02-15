<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class Message
{
	const SYS_UID = 0; //系统信息 UID
	const SYS_NAME = "系统信息";

    const APP = 'MESSAGE';
    const APPID = iCMS_APP_MESSAGE;

	public static $typeMap = array(
		'0' => '系统信息',
		'1' => '私信',
		'2' => '提醒',
		'3' => '留言',
	);
	//1 私信
	public static function send($a = array(
		"send_uid"    => 0, "send_name"   => NULL,
		"receiv_uid"  => 0, "receiv_name" => NULL,
		"content"     => NULL
	), $type = 1, $is_html = false)
	{

		// $userid = (int)$a['userid'];
		// $friend = (int)$a['friend'];

		$send_uid    = (int) $a['send_uid'];
		$send_name   = Security::escapeStr($a['send_name']);
		$receiv_uid  = (int) $a['receiv_uid'];
		$receiv_name = Security::escapeStr($a['receiv_name']);

		$content  = Security::escapeStr($a['content']);
		$is_html && $content  = $a['content'];

		$sendtime = time();
		if ($send_uid && $send_uid == $receiv_uid && !$a['self']) {
			return;
		}
		$fields = array('userid', 'friend', 'send_uid', 'send_name', 'receiv_uid', 'receiv_name', 'content', 'type', 'sendtime', 'readtime', 'status');
		$data   = compact($fields);
		$data['userid']   = $send_uid;
		$data['friend']   = $receiv_uid;
		$data['readtime'] = "0";
		$data['status']   = "1";
		MessageModel::create($data,true);
		if ($type == "1") {
			$data['userid']   = $receiv_uid;
			$data['friend']   = $send_uid;
			MessageModel::create($data,true);
		}
	}

	// public static function at($a){
	// 	self::send($a,2);
	// }

	//2 提醒
	public static function remind($a)
	{
		$a['send_uid']  = Message::SYS_UID;
		$a['send_name'] = Message::SYS_NAME;
		self::send($a, 2, true);
	}
	//0 通告
	public static function announce($a)
	{
		// if(empty($a['receiv_uid'])){
		$a['receiv_uid']  = "0";
		$a['receiv_name'] = "@所有人";
		// }
		$a['send_uid']  = Message::SYS_UID;
		$a['send_name'] = Message::SYS_NAME;
		self::send($a, 0, true);
	}
}
