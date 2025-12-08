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

class CommentApp extends AppsBase
{
    public $config = null;
    public static $LOGIN = ['vote', 'add'];

    public function __construct()
    {
        $this->config = Config::get('comment');
    }

    public function API_redirect()
    {
        $appid = (int) Request::get('appid');
        $iid = (int) Request::get('iid');
        $url = Apps::getUrl($appid, $iid);
        Utils::jumpto($url);
    }

    public function API_widget()
    {
        $name = Request::sget('name');
        empty($name) && iJson::error('empty:widget_name');
        $tpl = sprintf('iCMS://comment/widget.%s.htm', $name);
        View::display($tpl);
    }

    public function API_html()
    {
        $name = Request::spost('name');
        empty($name) && iJson::error('errors:empty:param');
        $tpl = sprintf('iCMS://comment/%s.htm', $name);
        $html = View::fetch($tpl);
        iJson::success(compact('html'));
    }

    public function API_reply()
    {
        $name = Request::post('name', 'api.reply');
        $tpl = sprintf('iCMS://comment/%s.htm', $name);
        $html = View::fetch($tpl);
        iJson::success(compact('html'));
    }

    //userid
    //iid
    //appid
    public function API_list()
    {
        // $param = Request::param('param');
        // $param && View::append('_REQUEST', $param,true);
        $name = Request::spost('name', 'api.list');
        $tpl = sprintf('iCMS://comment/%s.htm', $name);
        $html = View::fetch($tpl);
        iJson::success(compact('html'));
    }

    public function API_form()
    {
        View::display('iCMS://comment/api.form.htm');
    }

    /**
     * cms:comment:vote:down
     * cms:comment:vote:up
     *
     * @return void
     */
    public function api_vote()
    {
        $params = Request::post('params');
        $params or self::alert('iCMS:empty:params');
        $event = Request::spost('event');
        return $this->vote($params, $event);
    }

    public function vote($params, $event)
    {
        $id = (int) $params['id'];
        $appid = (int) $params['appid'];
        $userid = (int) User::$id;

        $UserTimeline = UserTimeline::user_iid_events(
            ['up', 'down'],
            $appid,
            $userid,
            $id
        );
        //赞同时删除 反对 记录
        $model = CommentModel::where($id);
        $result = [
            'is_up'  => false,
            'is_down'=> false,
        ] ;
        if ($event == 'down') {
            $result['is_down'] = true;
            //反对时 赞同数-1并删除赞同记录
            if ($utlId = $UserTimeline['up']) {
                $model->where('up', '>', 0)->dec('up');
            } elseif ($utlId = $UserTimeline['down']) {
                //已有反对记录 反对数-1并删除反对记录  (取消反对操作)
                $model->where('down', '>', 0)->dec('down');
                $result['is_down'] = false;
                // CommentModel::where($id)->inc('up');
            }
            $utlId && UserTimeline::delete($utlId);
            if ($result['is_down']) {
                CommentModel::where($id)->inc('down');
                UserTimeline::add($appid, $id, $event, $userid);
            }
            $result['is_up'] = false;
        } elseif ($event == 'up') {
            $result['is_up'] = true;
            //赞同时 反对数-1并删除记录
            if ($utlId = $UserTimeline['down']) {
                $model->where('down', '>', 0)->dec('down');
            } elseif ($utlId = $UserTimeline['up']) {
                //已有赞同记录 赞同数-1并删除赞同  (取消赞同操作)
                $model->where('up', '>', 0)->dec('up');
                $result['is_up'] = false;
            }
            $utlId && UserTimeline::delete($utlId);
            if ($result['is_up']) {
                CommentModel::where($id)->inc('up');
                UserTimeline::add($appid, $id, $event, $userid);
            }
            $result['is_down'] = false;
        }
        $result += CommentModel::where($id)->field('up,down')->get();
        $result['message'] = sprintf('comment:vote:%s:success', $event);
        return $result;
    }

    /**
     * cms:comment:vlist
     *
     * @return array
     */
    public function api_vlist()
    {
        $params = Request::post('params') ?: [];
        $vars = Request::post('vars') ?: [];
        $vars = array_filter_keys($vars, 'iid,appid,userid,page,row,reply_row,reply_show,page_ajax,page_style,status', true);
        $option = array_merge([
            'iid'       => $params['iid'],
            'appid'     => $params['appid'],
            'page'      => true,
            'page_ajax' => 'comment:page'
        ], $vars);
        $rows = CommentFunc::lists($option);
        $page = [];
        if ($PAGES = CommentFunc::$PAGES) {
            $page = $PAGES->vars();
        }
        $pageData = CommentFunc::$pageData;

        $rows && $rows = array_map(function ($row) use ($vars) {
            $row = array_filter_keys($row, 'id,user,content,create_time,update_time,params,up,is_up,down,is_down,reply_count', true);
            $row['replyFrom'] = !$this->config['reply']['enable'];
            $row['replyList'] = CommentReply::getList([
                'comment_id'=> $row['id'],
                'row'       => $vars['reply_row'] ?: 5
            ]);
            // $row['reply_count'] =  $row['replyList']['pageData']['total'];
            $row['replyList']['show'] = isset($vars['reply_show']) ? (int)$vars['reply_show'] : false;
            return $row;
        }, $rows);
        return ['config'=>$this->config, 'rows'=>$rows, 'pageData'=>$pageData, 'page'=>$page];
    }

    public function create($data)
    {
        $data['content'] or self::alert('comment:empty:content');

        $flag = iPHP::callback('Filter::run', [&$data, 'content'], false);
        $flag && self::alert('comment:filter');

        $params = is_array($data['params']) ? $data['params'] : json_decode($data['params'], true);
        is_array($params) && $data = array_merge($data, $params);
        $data['iid'] or iPHP::alert('comment:empty:iid');
        $data['target_title'] = $params['title'];
        $data['target_userid'] = $params['userid'];
        $data['target_username'] = $params['username'];

        $data['create_time'] = $_SERVER['REQUEST_TIME'];
        $data['update_time'] = $_SERVER['REQUEST_TIME'];
        $data['ip'] = Request::ip();
        $data['userid'] = User::$id;
        $data['username'] = User::$nickname;
        $data['status'] = $this->config['examine'] ? CommentModel::EXAMINE : '1';
        $data['id'] = CommentModel::create($data, true);
        Apps::updateInc('comment', $data['iid'], $data['appid']);
        User::updateInc('comment', $data['userid']);
    }

    public function done_add()
    {
        $data = Request::post();

        if (!$this->config['enable']) {
            self::alert('comment:close');
        }
        if ($this->config['captcha']) {
            Captcha::check($data['captcha'], 'comment');
        }
        $this->create($data);

        return $this->config['examine'] ?
        'comment:examine' :
        'comment:success';
    }

    public static function value($value, $vars)
    {
        self::getUrls($value);

        if (!$vars['isRaw']) {
            $value['content'] = nl2br($value['content']);
        }
        empty($value['user']) && $value['user'] = User::info($value['userid'], $vars['username']);
        AppsCommon::init($value, $vars)->params();
        $value['is_up'] = false;
        $value['is_down'] = false;
        if ($vars['UserTimeline'] && isset($vars['UserTimeline'][$value['id']])) {
            $value = array_merge($value, $vars['UserTimeline'][$value['id']]);
        }
        $value['params']['iid'] = (int) $value['iid'];
        $value['params']['appid'] = iCMS_APP_COMMENT;
        $value['params']['app'] = 'comment';
        return $value;
    }

    public static function getUrls(&$value)
    {
        $value['url'] = self::route($value, 'id');
        $value['content_url'] = self::route($value, 'appid,iid,cid', 'redirect');
        return [
            'url'         => $value['url'],
            'content_url' => $value['content_url']
        ];
    }

    public static function route($vars, $keys, $do = null)
    {
        $vars = array_filter_keys($vars, $keys);
        $vars['app'] = 'comment';
        $do && $vars['do'] = $do;
        return Route::url('api', $vars);
    }
}
