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
        $where_sql  = "WHERE `status` ='1'";
        $type       = $vars['type'];
        $friend     = (int) $vars['friend'];

        if ($type == 'sys') {
            $sql = " AND `userid`='" . Message::SYS_UID . "' AND `friend` ='" . User::$id . "'";
        }
        if ($friend) {
            $sql = " AND `userid`='" . User::$id . "' AND `friend`='" . $friend . "'";
        }
        if ($sql) {
            $where_sql .= $sql;
            $group_sql = '';
            $p_fields  = 'COUNT(*)';
            $s_fields  = '*';
        } else {
            //包含系统信息
            // $where_sql.= " AND (`userid`='".User::$id."' OR (`userid`='".message::SYS_UID."' AND `friend`='".User::$id."'))";

            $where_sql .= " AND `userid`='" . User::$id . "'";
            $group_sql = 'GROUP BY `friend` DESC';
            $p_fields  = 'COUNT(DISTINCT id)';
            $s_fields  = 'id,COUNT(id) AS msg_count,`userid`, `friend`, `send_uid`, `send_name`, `receiv_uid`, `receiv_name`, `content`, `type`, `sendtime`, `readtime`';
        }

        $offset = (int) $vars['offset'];
        $total  = Paging::totalCache("SELECT {$p_fields} FROM `#iCMS@__message` {$where_sql} {$group_sql}", 'nocache');
        View::assign("message_list_total", $total);
        $multi  = Paging::make(array(
            'count' => $total,
            'size' => $pageSize,
        ));
        $offset = $multi->offset;
        $resource = MessageModel::select("SELECT {$s_fields} FROM `#iCMS@__message` {$where_sql} {$group_sql} ORDER BY `id` DESC LIMIT {$offset},{$pageSize}");
        if ($resource) foreach ($resource as $key => $value) {
            $value['sender']   = User::info($value['send_uid'], $value['send_name']);
            $value['receiver'] = User::info($value['receiv_uid'], $value['receiv_name']);
            $value['label']    = Message::$typeMap[$value['type']];

            if ($value['userid'] == $value['send_uid']) {
                $value['is_sender'] = true;
                $value['user']      = $value['receiver'];
            }
            if ($value['userid'] == $value['receiv_uid']) {
                $value['is_sender'] = false;
                $value['user']      = $value['sender'];
            }
            if ($value['type'] == '1') {
                $value['type_text'] = 'msg';
            }
            if ($value['type'] == '2' || $value['type'] == '0') {
                $value['type_text'] = 'sys';
            }
            $value['url']   = Route::routing('user/message/{uid}', [$value['user']['uid']]);
            $resource[$key] = $value;
        }
        return $resource;
    }
    public static function lists($vars = null)
    {
        $where  = array();
        $whereNot  = array();
        $resource  = array();
        $model     = MessageModel::field('id');


        $type       = $vars['type'];
        $friend     = (int) $vars['friend'];

        if ($type == 'sys') {
            $where['userid'] = Message::SYS_UID;
            $where['friend'] = User::$id;
        }

        if ($friend) {
            $where['userid'] = User::$id;
            $where['friend'] = $friend;
        }
        if (empty($where)) {
            $where['userid'] = User::$id;
            $model->groupBy('friend', 'DESC');
            $model->field(true, 'friend');
            $s_fields  = 'COUNT(id) AS msg_count';
        }
        $where['status'] = '1';

        // self::node('cid');
        // self::orderby(['new' => 'id', 'sort' => 'sortnum'], 'id');
        self::init($vars, $model, $where, $whereNot);
        self::setApp(Message::APPID, Message::APP);
        self::where();
        return self::getResource(__METHOD__, [__CLASS__, 'resource']);
    }
    public static function resource($vars, $idsArray = null)
    {
        $vars['ids'] && $idsArray = $vars['ids'];
        
        $resource = MessageModel::field(true)->where($idsArray)->orderBy('id', $idsArray)->select();
        if ($resource) foreach ($resource as $key => $value) {
            $value['sender']   = User::info($value['send_uid'], $value['send_name']);
            $value['receiver'] = User::info($value['receiv_uid'], $value['receiv_name']);
            $value['label']    = Message::$typeMap[$value['type']];

            if ($value['userid'] == $value['send_uid']) {
                $value['is_sender'] = true;
                $value['user']      = $value['receiver'];
            }
            if ($value['userid'] == $value['receiv_uid']) {
                $value['is_sender'] = false;
                $value['user']      = $value['sender'];
            }
            if ($value['type'] == '1') {
                $value['type_text'] = 'msg';
            }
            if ($value['type'] == '2' || $value['type'] == '0') {
                $value['type_text'] = 'sys';
            }
            $value['url']   = Route::routing('user/message/{uid}', [$value['user']['uid']]);
            $resource[$key] = $value;
        }
        return $resource;
    }
}
