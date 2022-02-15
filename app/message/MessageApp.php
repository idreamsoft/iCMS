<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class MessageApp
{
    public $methods = array(iPHP_APP, 'pm', 'manage');

    public static function API_iCMS()
    {
    }
    public static function setRead($id, $userid)
    {
        if ($id && $userid) {
            $readtime = time();
            $update = compact('readtime');

            MessageModel::where(compact('userid'))->update($update);
            MessageModel::where([
                'userid' => Message::SYS_UID,
                'friend' => $userid
            ])->update($update);
        }
    }
    public static function setStatus($id, $userid, $friend = 0, $status = 0)
    {
        $update = compact('status');
        if ($friend) {
            MessageModel::where([
                'friend' => $friend,
                'userid' => $userid
            ])->update($update);
        } else {
            if ($id) {
                MessageModel::where(compact('userid', 'id'))->update($update);
                MessageModel::where([
                    'userid' => Message::SYS_UID,
                    'friend' => $userid,
                    'id' => $id
                ])->update($update);
            }
        }
    }
    public static function API_manage()
    {
        $act = $_POST['act'];
        $id  = (int) $_POST['id'];
        if ($act == "read") {
            $id or iJson::error();
            self::setRead($id, User::$id);
            iJson::success();
        }
        if ($act == "del") {
            $id or iJson::error();
            $user = (int) $_POST['user'];
            self::setStatus($id, User::$id, $user);
            iJson::success();
        }
    }
    public function ACTION_pm()
    {
        UserCP::status();

        $receiv_uid  = (int) $_POST['uid'];
        $receiv_name = Request::post('name');
        $content     = Request::post('content');

        $receiv_uid or Script::code(0, 'iCMS:error', 0, 'json');
        $content or Script::code(0, 'iCMS:pm:empty', 0, 'json');

        $send_uid  = User::$id;
        $send_name = User::$nickname;

        $setting = (array) User::value($receiv_uid, 'setting');
        if ($setting['inbox']['receive'] == 'follow') {
            if ($mid) {
                $mid = Request::post('mid');
                $mid = auth_decode($mid);
                $muserid = MessageModel::field('userid')->where($mid)->value();
            }
            if ($muserid != User::$id) {
                $check = UserFollow::is($receiv_uid, $send_uid);
                $check or Script::code(0, 'iCMS:pm:nofollow', 0, 'json');
            }
        }

        $fields = array('send_uid', 'send_name', 'receiv_uid', 'receiv_name', 'content');
        $data = compact($fields);
        Message::send($data, 1);
        Script::code(1, 'iCMS:pm:success', $id, 'json');
    }
    public static function _count($userid = null)
    {
        $sql[] = MessageModel::where([
            'userid' => $userid,
            'readtime' => '0',
            'status' => '1'
        ])->field('count(id)')->getSql();
        $sql[] = MessageModel::where([
            'userid' => Message::SYS_UID,
            'friend' => $userid,
            'readtime' => '0',
            'status' => '1'
        ])->field('count(id)')->getSql();
        $unionAll = implode(' UNION ALL ', $sql);
        $variable = MessageModel::select($unionAll);
        $count = 0;
        if (is_array($variable)) {
            $variable = array_column($variable, 'count(id)');
            $count = array_sum($variable);
        }
        return (int)$count;
    }
}
