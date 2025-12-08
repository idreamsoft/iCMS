<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class MessageFunc extends AppsFuncCommon
{
    public static function message_list($vars = null)
    {
        $pageSize = 30;
        $where_sql = "WHERE `status` ='1'";
        $type = $vars['type'];
        $friend = (int) $vars['friend'];

        if ($type == 'sys') {
            $sql = " AND `userid`='" . Message::SYS_UID . "' AND `friend` ='" . User::$id . "'";
        }
        if ($friend) {
            $sql = " AND `userid`='" . User::$id . "' AND `friend`='" . $friend . "'";
        }
        if ($sql) {
            $where_sql .= $sql;
            $group_sql = '';
            $p_fields = 'COUNT(*)';
            $s_fields = '*';
        } else {
            //包含系统信息
            // $where_sql.= " AND (`userid`='".User::$id."' OR (`userid`='".message::SYS_UID."' AND `friend`='".User::$id."'))";

            $where_sql .= " AND `userid`='" . User::$id . "'";
            $group_sql = 'GROUP BY `friend` DESC';
            $p_fields = 'COUNT(DISTINCT id)';
            $s_fields = 'id,COUNT(id) AS msg_count,`userid`, `friend`, `send_uid`, `send_name`, `receiv_uid`, `receiv_name`, `content`, `type`, `sendtime`, `readtime`';
        }

        $offset = (int) $vars['offset'];
        $total = Paging::totalCache("SELECT {$p_fields} FROM `#iCMS@__message` {$where_sql} {$group_sql}", 'nocache');
        View::assign('message_list_total', $total);
        $Paging = Paging::make([
            'count' => $total,
            'size'  => $pageSize,
        ]);
        View::generic([
            'PAGES' => $Paging,
            'PAGE'  => $Paging->vars()
        ]);
        $offset = $Paging->offset;
        $resource = MessageModel::select("SELECT {$s_fields} FROM `#iCMS@__message` {$where_sql} {$group_sql} ORDER BY `id` DESC LIMIT {$offset},{$pageSize}");
        if ($resource) {
            $sendUserInfo = User::column($resource, 'send_uid', 'send_name');
            $receivUserInfo = User::column($resource, 'receiv_uid', 'receiv_name');

            foreach ($resource as $key => $value) {
                $value['sender'] = $sendUserInfo[$value['send_uid']];
                $value['receiver'] = $receivUserInfo[$value['receiv_uid']];
                $value['label'] = MessageModel::$typeMap[$value['type']];

                if ($value['userid'] == $value['send_uid']) {
                    $value['is_sender'] = true;
                    $value['user'] = $value['receiver'];
                }
                if ($value['userid'] == $value['receiv_uid']) {
                    $value['is_sender'] = false;
                    $value['user'] = $value['sender'];
                }
                if ($value['type'] == '1') {
                    $value['type_text'] = 'msg';
                }
                if ($value['type'] == '2' || $value['type'] == '0') {
                    $value['type_text'] = 'sys';
                }
                $value['url'] = Route::routing('message/userid/{userid}', [$value['user']['id']]);
                $resource[$key] = $value;
            }
        }
        return $resource;
    }

    public static function users($vars = null)
    {
        $where = [];
        $whereNot = [];
        $resource = [];
        $model = MessageModel::field('MAX(id) AS id');
        $where['userid'] = User::$id;
        $model->groupBy('friend', 'DESC');
        $where['status'] = ['>','0'];
        self::inited($vars, $model, $where, $whereNot);
        self::where();
        return self::getResource(__METHOD__);
    }

    public static function lists($vars = null)
    {
        $where = [];
        $whereNot = [];
        $resource = [];
        $model = MessageModel::field('id');

        $type = $vars['type'];
        $userid = $vars['userid'];
        $friend = (int) $vars['friend'];

        if (isset($vars['userid'])) {
            $userid = $vars['userid'];
            $userid == '@me' && $userid = User::$id ?: 0;
            $where[] = ['userid', $userid];
        }

        if (isset($type)) {
            if ($type == 'sys') {
                $where[] = ['userid', Message::SYS_UID];
                $where[] = ['friend', User::$id];
            } else {
                $where[] = ['type', $type];
            }
        }

        if ($friend) {
            $where['userid'] = User::$id;
            $where['friend'] = $friend;
        }
        // if (empty($where)) {
        //     $where['userid'] = User::$id;
        //     $model->groupBy('friend', 'DESC');
        //     $model->field(true, 'friend');
        //     $s_fields = 'COUNT(id) AS msg_count';
        // }
        // $where['status'] = '1';

        // self::node('cid');
        self::inited($vars, $model, $where, $whereNot);
        self::where();
        self::orderby(['new' => 'id', 'sort' => 'sortnum'], 'id');
        return self::getResource(__METHOD__);
    }

    public static function resource($idsArray = null)
    {
        $vars = self::$vars;

        $resource = MessageModel::field(true)->where($idsArray)->orderBy('id', $idsArray)->select();
        if ($resource) {
            $friendUserInfo = User::column($resource, 'friend');
            $sendUserInfo = User::column($resource, 'send_uid', 'send_name');
            $receivUserInfo = User::column($resource, 'receiv_uid', 'receiv_name');

            foreach ($resource as $key => $value) {
                $value['friender'] = $friendUserInfo[$value['friend']];
                $value['sender'] = $sendUserInfo[$value['send_uid']];
                $value['receiver'] = $receivUserInfo[$value['receiv_uid']];
                $value['label'] = MessageModel::$typeMap[$value['type']];

                if ($value['userid'] == $value['send_uid']) {
                    $value['is_sender'] = true;
                    $value['user'] = $value['receiver'];
                }
                if ($value['userid'] == $value['receiv_uid']) {
                    $value['is_sender'] = false;
                    $value['user'] = $value['sender'];
                }
                if ($value['type'] == '1') {
                    $value['type_text'] = 'msg';
                }
                if ($value['type'] == '2' || $value['type'] == '0') {
                    $value['type_text'] = 'sys';
                }
                $value['url'] = Route::routing('message/userid/{userid}', [$value['user']['id']]);
                $resource[$key] = $value;
            }
        }
        return $resource;
    }
}
