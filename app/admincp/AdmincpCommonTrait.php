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
/**
 * 后台 CRUD 核心实现
 * @abstract AdmincpCommon
 */
trait AdmincpCommonTrait
{
    /**
     * 添加{title}
     */
    public function do_add($rs = [])
    {
        $model = $this->getModel(); //兼容php5
        empty($rs) && $this->adding($rs);
        // $this->id && $rs = call_user_func_array([self::$MODEL,'get'],[$this->id]);
        // self::$MODEL::get($this->id);
        include self::view('add');
    }

    /**
     * 添加时回调,用于设置默认数据
     *
     * @return void
     */
    protected function adding(&$rs = [])
    {
    }

    // //do_add 前置方法
    // protected function __do_add()
    // {
    // }
    // //do_add 后置方法
    // public function do_add__()
    // {
    // }
    /**
     * 保存添加数据
     *
     * @param array $data
     * @return void
     */
    public function done_add(&$data = [])
    {
        $this->added($data);
        $this->presave($data);
        $ret = $this->save($data);
        $this->saved($data);
        return $ret;
    }

    /**
     * 保存添加数据时调用处理数据
     *
     * @param array $data
     * @return void
     */
    public function added(&$data = [])
    {
    }

    public function done_add__($data = [])
    {
    }

    /**
     * 编辑{title}
     */
    public function do_edit()
    {
        $model = $this->getModel(); //兼容php5
        $this->id or self::alert('请选择要编辑的项目');
        $this->id && $rs = $model::get($this->id);
        $view = $this->editing($rs);
        if ($view) {
            return include self::view($view);
        }
        return $this->do_add($rs);
    }

    /**
     * 编辑时回调,用于设置默认数据
     *
     * @param array $rs
     * @return $view
     */
    protected function editing(&$rs = [])
    {
    }

    // //do_edit 前置方法
    // protected function __do_edit()
    // {
    //     return $this->__do_add();
    // }
    // //do_edit 后置方法
    // public function do_edit__()
    // {
    // }
    /**
     * 编辑
     *
     * @param array $data 引用数据，用于保存时钩子处理
     * @return void
     */
    public function done_edit(&$data = [])
    {
        $this->edited($data);
        $this->presave($data);
        $ret = $this->save($data);
        $this->saved($data);
        return $ret;
    }

    /**
     * 保存编辑数据时调用处理数据
     *
     * @param array $data
     * @return void
     */
    public function edited(&$data = [])
    {
    }

    public function done_edit__($data = [])
    {
        // var_dump(__METHOD__);
        // echo iDebug::backtraceHtml();
    }

    /**
     * 保存{title}
     *
     * @param Array [Reference] $data 引用传值到 done_save__ 参数
     * @return void
     */
    public function save(&$data = [])
    {
        $model = $this->getModel(); //兼容php5
        $this->saving($data);
        if (empty($data['id'])) {
            $data['id'] = $model::create($data, true);
        } else {
            unset($data['created']);
            $model::update($data, $data['id']);
        }
        return Admincp::url(Admincp::$APP_NAME . '/index');
    }

    /**
     * 保存数据前处理
     * 应用定义了save方法时，可以使用此方法前置处理数据
     * 一般用于过滤非字段数据
     *
     * @param array $data
     * @return void
     */
    public function presave(&$data = [])
    {
        $model = $this->getModel(); //兼容php5
        $post = $this->getPost();
        if (empty($data) && $post) {
            $data = $model::filter($post); // 过滤非字段数据
        }
        empty($data) && $data = $model::postData();
    }

    /**
     * 保存时数据处理
     * 应用没有定义save方法时，才会调用此方法
     *
     * @param array $data
     * @return void
     */
    public function saving(&$data = [])
    {
    }

    /**
     * 保存后调用
     *
     * @param array $data
     * @return void
     */
    public function saved(&$data = [])
    {
        $post = Request::post();
        // $data = self::$DATA;
        if ($data['id']) {
            // $this->id = $data['id'];
            // 自定义
            if ($appId = $post['former_appid']) {
                // $appId = decrypt($appId);
                $apps = Apps::get($appId);
                if ($apps['fields']) {
                    Content::model($apps);
                    FormerApp::save($apps, $data['id']);
                }
            }
            // 动态属性
            if ($appId = $post['metadata_appid']) {
                $id = $post['metadata_id'];
                if (empty($id) && $data['id']) {
                    $id = $data['id'];
                }
                $mode = $post['metadata_mode'];
                $metadata = (array) $post['metadata'];
                AppsMeta::saveData($appId, $id, compact('mode', 'metadata'));
            }
        }
        $params = [&$data, $this];
        if (isset($this->callback)) {
            if ($callArr = $this->callback['saved']) {
                foreach ($callArr as $key => $call) {
                    iPHP::callback($call, $params);
                }
                $this->RESULT = $data;
            }
            // if ($rcall = $this->callback['result']) {
            //     iPHP::callback($rcall, $params);
            // }
            // $this->callback['result'] = function () use ($data) {
            //     return $data;
            // };
        }
        // testHook::admincp_$app_$do_saved
        Admincp::hooks(Admincp::$APP, 'saved', Admincp::$APP_DO, $params);
        // testHook::admincp_$app_saved
        Admincp::hooks(Admincp::$APP, 'saved', null, $params);
        // testHook::admincp_common_saved
        Admincp::hooks('common', 'saved', null, $params);
    }

    /**
     * 复制{title}
     */
    public function done_copy()
    {
        $model = $this->getModel(); //兼容php5
        $rs = $model::get($this->id);
        unset($rs['id']);
        $rs['name'] && $rs['name'] .= '副本';
        $rs['title'] && $rs['title'] .= '副本';
        $rs['sortnum'] && $rs['sortnum'] = time();
        $id = $model::create($rs);
        $url = Admincp::url(Admincp::$APP_NAME . '/edit', ['id' => $id]);
        // Utils::jumpto($url);
        return $url;
    }

    /**
     * {title}评论
     */
    public function do_comment()
    {
        if (apps::check('comment')) {
            $comment = new CommentAdmincp();
            return $comment->do_index();
        } else {
            self::alert('comment app not found');
        }
    }

    /**
     * 获取{title}节点下拉数据
     *
     * @auth no
     * @return void
     */
    public function done_node()
    {
        $node = new NodeAdmincp($this->appid());
        return $node->done_selectpage();
    }

    /**
     * 删除{title}
     */
    public function done_delete($ids = null)
    {
        $model = $this->getModel(); //兼容php5
        $ids === null && $ids = $this->ids;
        $ids or self::alert('Please select items to delete');
        is_array($ids) or $ids = [$ids];
        $flag = $this->deleting($ids); //return true  不再继续执行删数据
        if (is_null($flag) && $ids) {
            $model::delete($ids);
        }
        $ids && $this->deleted($ids);
        $params = [&$ids, $this];
        // testHook::admincp_$app_deleted
        Admincp::hooks(Admincp::$APP, 'deleted', null, $params);
        return ['action' => 'delete', 'id' => $ids];
    }

    /**
     * 删除时处理
     *
     * @param array $ids
     * @return bool
     */
    protected function deleting($ids = [])
    {
    }

    //删除后回调
    protected function deleted($ids = [])
    {
    }

    /**
     * 管理{title}
     *
     * @return void
     */
    public function do_index()
    {
        $model = $this->getModel(); //兼容php5
        $context = [
            'tablist'   => 'params',
            'params'    => [
                'status' => Request::get('status'),
            ]
        ];
        $this->index_context($context);

        if ($statusMap = Prop::getMap($model, 'status')) {
            $context['statusMap'] = $statusMap;
        }
        self::setContext($context);

        if (Request::isAjax()) {
            return $this->index();
        }

        include self::view('index');
    }

    /**
     * 获取管理列表数据
     *
     * @return void
     */
    public function index()
    {
        $model = $this->getModel(); //兼容php5
        $where = $model::getData();
        $this->index_where($where);
        AdmincpAuth::where($where);
        $keywords = Request::get('keywords');
        if (isset($keywords)) {
            $findby = Request::get('findby');
            if ($findby) {
                list($field, $exp) = explode(';', $findby);
                empty($exp) && $exp = 'regexp';
                $expArray = explode(',', '=,<,>,<>,!=,<=,>=,like,not like,regexp,in');
                if (in_array($exp, $expArray)) {
                    $field = Security::safeStr($field, '_,');
                    if (strpos($keywords, ',') !== false) {
                        $keywords = explode(',', $keywords);
                    }
                    if (strpos($field, ',') !== false) {
                        $field = 'CONCAT(' . $field . ')';
                    }
                    $where[] = [$field, $exp, $keywords];
                }
            }
        }
        foreach ($where as $key => $value) {
            if (is_array($value) && in_array($key, ['created', 'updated'])) {
                unset($where[$key]);
                foreach ($value as $kk => $val) {
                    $val && $where[] = [$key, $kk, str2time($val . (strpos($val, ' ') !== false ? '' : ' 00:00:00'))];
                }
            }
        }
        return self::tableData(
            $model,
            ['where' => $where],
            function ($rows) {
                $rows = array_map(function ($row) {
                    $this->index_data($row);
                    return $row;
                }, $rows);
                return $rows;
            }
        );
    }

    /**
     * 列表上下文
     *
     * @param array $context
     * @return void
     */
    protected function index_context(&$context = [])
    {
    }

    /**
     * 列表查询条件
     *
     * @param array $where
     * @return void
     */
    protected function index_where(&$where = [])
    {
    }

    /**
     * 列表数据处理回调
     * @param array $row
     * @return void
     */
    protected function index_data(&$row = [])
    {
    }

    /**
     * 批处理{title}
     */
    public function done_batch()
    {
        $model = $this->getModel(); //兼容php5
        $actions = [
            'dels' => [$this, 'done_delete'],
            'prop' => function ($ids, $data, $batch) use ($model) {
                $pid = $data['pid'];
                $model::update(compact('pid'), $ids);
            },
            'keyword' => function ($ids, $data, $batch) use ($model) {
                $pattern = $data['pattern'];
                $keywords = $data['keyword'];
                if ($pattern == 'prepend') {
                    $keywords = ['raw' => 'CONCAT(?,keywords)', $keywords];
                }
                if ($pattern == 'append') {
                    $keywords = ['raw' => 'CONCAT(keywords,?)', $keywords];
                }
                $model::update(compact('keywords'), $ids);
            },
            'status' => function ($ids, $data, $batch) use ($model) {
                $status = $data['status'];
                $model::update(compact('status'), $ids);
            },
            'default' => function ($ids, $data, $batch) use ($model) {
                $data && $model::update($data, $ids);
            }
        ];
        $batching = (array)$this->batching();
        $actions = array_merge($actions, $batching);
        $result = AdmincpBatch::run($actions);
        $this->batched($result);
        return $result;
    }

    /**
     * 设置批处理对应的处理方法
     *
     * @return void
     */
    protected function batching()
    {
    }

    /**
     * 批处理结果处理
     *
     * @param [type] $result
     * @return void
     */
    protected function batched(&$result)
    {
    }

    /**
     * 移入回收站
     *
     * @return void
     */
    public function done_trash()
    {
        $this->done_update();
    }

    /**
     * 发布{title}
     *
     * @return void
     */
    public function done_publish()
    {
        $this->done_update();
    }

    /**
     * 更新{title}
     *
     * @return void
     */
    public function done_update()
    {
        $model = $this->getModel();
        $this->id && $where = $this->id;
        //postdata where=[],data=[]
        if ($_where = Request::post('where', false)) {
            $where = $_where;
        }
        if ($_data = Request::post('data', false)) {
            $data = $_data;
        }
        //postdata id,field,value
        $id = Request::post('id', false);
        $field = Request::post('field', false);
        $value = Request::post('value');
        if ($id && $field && isset($value)) {
            $where = $id;
            $data = [$field => $value];
        }
        $this->updating($data, $where);
        if ($data && $where) {
            $params = [$data, $where];
            $result = call_user_func_array([$model, 'update'], $params);
            $this->updated($result);
        }
        return true;
    }

    /**
     * 设置更新,处理数据和条件
     *
     * @param array $data
     * @param array $where
     * @return void
     */
    protected function updating(&$data, &$where)
    {
    }

    /**
     * 设置更新结果处理
     *
     * @param array $result
     * @return void
     */
    protected function updated(&$result)
    {
    }
}
