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
class CommentFunc extends AppsFuncCommon
{
    public static function data($vars)
    {
        $where = [];
        $whereNot = [];
        $resource = [];
        $model = CommentModel::field('id');
        $status = isset($vars['status']) ? (int) $vars['status'] : 1;
        $where['status'] = $status;

        isset($vars['id']) && $where['id'] = $vars['id'];
        isset($vars['userid']) && $where['userid'] = $vars['userid'];

        if (isset($vars['loop'])) {
            $resource = $model->select();
            if ($resource) {
                $resource = array_column($resource, null, 'id');
                $userInfo = User::column($resource, 'userid', 'username');
                foreach ($resource as $key => &$value) {
                    $value['user'] = $userInfo[$value['userid']];
                }
            }
        } else {
            $resource = $model->get();
            $resource['user'] = User::info($resource['userid'], $resource['username']);
        }
        return $resource;
    }

    private static function list_display($vars)
    {
        $vars['app'] = 'comment';
        $vars['do'] = 'list';
        $vars['page_ajax'] = 'comment_page_ajax';
        $vars['total_cache'] = 'G';
        isset($vars['_display']) && $vars['display'] = $vars['_display'];
        unset($vars['method'], $vars['_display']);
        View::unvars($vars);
        $vars['query'] = http_build_query($vars);
        $vars['params'] = [
            'suid'  => (int) $vars['suid'],
            'iid'   => (int) $vars['iid'],
            'cid'   => (int) $vars['cid'],
            'appid' => (int) $vars['appid'],
            'title' => Security::escapeStr($vars['title']),
        ];
        $tpl = 'list.default';
        View::assign('comment_vars', $vars);
        View::display("iCMS://comment/{$tpl}.htm");
    }

    public static function vlist($vars)
    {
        // View::assign('comment_vlist', $vars);
        View::display('iCMS://comment/vlist.htm');
    }

    public static function lists($vars)
    {
        if (!Config::get('comment.enable')) {
            return;
        }

        // if ($vars['display'] && empty($vars['loop'])) {
        // 	$_vars = View::app_vars(true);
        // 	$vars  = array_merge($vars, (array) $_vars);
        // 	$vars['iid']   or self::msg('iCMS&#x3a;comment&#x3a;list 标签出错! 缺少参数"iid"或"iid"值为空.');
        // 	$vars['appid'] or self::msg('iCMS&#x3a;comment&#x3a;list 标签出错! 缺少参数"appid"或"appid"值为空.');
        // 	return commentFunc::list_display($vars);
        // }
        if (isset($vars['ref'])) {
            $params = View::getParams();
            $params && $vars = array_merge($vars, $params);
            $vars['iid'] or self::msg(['func_param', ['iCMS:comment:lists', 'iid']]);
            $vars['appid'] or self::msg(['func_param', ['iCMS:comment:lists', 'appid']]);
        }

        $whereNot = [];
        $model = CommentModel::field('id');
        $status = isset($vars['status']) ? $vars['status'] : 1;
        $where = [['status', $status]];

        isset($vars['appid']) && $where[] = ['appid', $vars['appid']];
        isset($vars['userid']) && $where[] = ['userid', $vars['userid']];
        isset($vars['cid']) && $where[] = ['cid', $vars['cid']];
        isset($vars['iid']) && $where[] = ['iid', $vars['iid']];
        isset($vars['indexid']) && $where[] = ['iid', $vars['indexid']];
        // isset($vars['page_ajax']) && $vars['page_ajax'] = true;

        self::inited($vars, $model, $where, $whereNot);
        self::nodes('cid');
        self::orderby([]);
        self::where();
        return self::getResource(__METHOD__);
    }

    public static function resource($idsArray = null)
    {

        $vars = self::$vars;
        $total = self::$pageData['total'];
        $pageSize = self::$pageData['pageSize'];
        $pageNow = self::$pageData['current'];

        $resource = CommentModel::field('*')->where($idsArray)->orderBy('id', $idsArray)->select();
        $ln = ($pageNow - 1) < 0 ? 0 : $pageNow - 1;
        if ($resource) {
            $userInfo = User::column($resource, 'userid', 'username');
            $vars['UserTimeline'] = UserTimeline::user_events(['up', 'down'], Comment::APPID);
            foreach ($resource as $key => $value) {
                $value['statusText'] = CommentModel::$statusMap[$value['status']];
                $value['user'] = $userInfo[$value['userid']];
                $value = CommentApp::value($value, $vars);
                $value['apps'] = Apps::getDataLite($value['appid']);
                if ($vars['by'] == 'ASC') {
                    $value['lou'] = $key + $ln * $pageSize + 1;
                } else {
                    $value['lou'] = $total - ($key + $ln * $pageSize);
                }
                $value['total'] = $total;
                $resource[$key] = $value;
            }
        }
        return $resource;
    }

    // public static function getAppParams($name = true, $out = false)
    // {
    //     $rs = View::get($name);
    //     return $rs['params'];
    // }
    public static function form($vars)
    {
        if (!Config::get('comment.enable')) {
            return;
        }

        if (isset($vars['params']) && is_array($vars['params'])) {
            $vars = array_merge($vars, $vars['params']);
        } elseif (!isset($vars['ref'])) {
            $_vars = View::getParams();
            $_vars && $vars = array_merge($vars, $_vars);
            unset($vars['ref'], $_vars);
        }

        if ($vars['throw'] !== false) {
            $vars['iid'] or self::msg(['func_param', ['iCMS:comment:form', 'iid']]);
            // $vars['cid']   or self::msg('iCMS&#x3a;comment&#x3a;form 标签出错! 缺少参数"cid"或"cid"值为空.');
            $vars['appid'] or self::msg(['func_param', ['iCMS:comment:form', 'appid']]);
            $vars['title'] or self::msg(['func_param', ['iCMS:comment:form', 'title']]);
        }

        switch ($vars['display']) {
            case 'iframe':
                $tpl = 'form.iframe';
                $vars['do'] = 'form';
                break;
            default:
                isset($vars['_display']) && $vars['display'] = $vars['_display'];
                $vars['params'] = [
                    'iid'       => (int) $vars['iid'],
                    'cid'       => (int) $vars['cid'],
                    'appid'     => (int) $vars['appid'],
                    'userid'    => (int) $vars['userid'],
                    'username'  => Security::escapeStr($vars['username']),
                    'title'     => Security::escapeStr($vars['title']),
                ];
                $tpl = 'form';
                break;
        }
        $vars['hidden'] && $vars['class'] .= ' hide';
        $vars['sign'] = Utils::sign($vars['params']);

        View::assign('comment_vars', $vars);
        View::display('iCMS://comment/' . $tpl . '.htm');
    }
}
