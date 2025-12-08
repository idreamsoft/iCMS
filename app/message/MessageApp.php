<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class MessageApp extends AppsBase
{
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
                    'id'     => $id
                ])->update($update);
            }
        }
    }

    public function api_room()
    {
        $friend = (int)Request::spost('friend');
        $lastId = (int)Request::spost('lastId', 0);
        $rows = MessageFunc::lists([
            'friend'=> $friend,
            'lastId'=> $lastId,
        ]);

        if ($rows) {
            $rows = array_reverse($rows);
            $rows && $rows = array_map(function ($row) {
                $todayDate = date('Y-m-d');
                $row['sendtimeText'] = date('Y-m-d H:i', $row['sendtime']);
                if (substr($row['sendtimeText'], 0, 10) == $todayDate) {
                    $row['sendtimeText'] = date('H:i', $row['sendtime']);
                }
                return $row;
            }, $rows);
            $lastArr = end($rows);
            $lastId = $lastArr ? $lastArr['id'] : $lastId;
        }
        if ($friend) {
            $room = User::info($friend);
            $room['idHash'] = Crypt::encrypt($room['id']);
        }
        return compact('rows', 'lastId', 'room');
    }

    public static function API_manage()
    {
        $act = $_POST['act'];
        $id = (int) $_POST['id'];
        if ($act == 'read') {
            $id or iJson::error();
            self::setRead($id, User::$id);
            iJson::success();
        }
        if ($act == 'del') {
            $id or iJson::error();
            $user = (int) $_POST['user'];
            self::setStatus($id, User::$id, $user);
            iJson::success();
        }
    }

    public function done_send()
    {
        if ($receiv_uid_hash = Request::post('userid')) {
            $receiv_uid = Crypt::decrypt($receiv_uid_hash);
        }
        $receiv_uid or self::alert('MESSAGE:error:receiv_uid');

        $receiv_name = Request::post('username');
        $content = Request::post('content');

        $content or self::alert('MESSAGE:error:content');

        $send_uid = User::$id;
        $send_name = User::$nickname;

        $setting = (array) User::value($receiv_uid, 'setting');
        if (isset($setting['message'])) {
            $receive = $setting['message']['receive'];
            if (!$receive) {
                return [false, 'MESSAGE:send:fail'];
            } elseif ($receive == 'follow') {
                if ($mid_hash = Request::post('mid')) {
                    $mid = Crypt::decrypt($mid_hash);
                    $muserid = MessageModel::field('userid')->where($mid)->value();
                }
                if ($muserid != User::$id) {
                    $check = UserFollow::is($receiv_uid, $send_uid);
                    if (!$check) {
                        return [false, 'MESSAGE:send:nofollow'];
                    }
                }
            }
        }

        $fields = ['send_uid', 'send_name', 'receiv_uid', 'receiv_name', 'content'];
        $data = compact($fields);
        Message::send($data, 1);
        return [true, 'MESSAGE:send:success'];
    }

    public static function _count($userid = null)
    {
        $sql[] = MessageModel::where([
            'userid'   => $userid,
            'readtime' => '0',
            'status'   => '1'
        ])->field('count(id)')->getSql();
        $sql[] = MessageModel::where([
            'userid'   => Message::SYS_UID,
            'friend'   => $userid,
            'readtime' => '0',
            'status'   => '1'
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
