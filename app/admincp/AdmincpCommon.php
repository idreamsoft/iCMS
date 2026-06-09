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
 * 后台标准通用功能
 */
abstract class AdmincpCommon extends AdmincpBase
{
    // use AdmincpCommonTrait;
    /**
     * 查看{title}详情
     *
     * @return void
     */
    public function do_detail()
    {
        $model = $this->getModel(); //兼容php5
        $rs = $model::get($this->id);
        $fields = $model::fullFields();
        include self::view();
    }

    /**
     * 配置{title}
     *
     * @return void
     */
    public function do_config()
    {
        return ConfigView::display(self::$APPID);
    }

    /**
     * 保存{title}
     *
     * @return void
     */
    public function done_config()
    {
        $data = Request::post('config');
        Config::save(self::$APPID, $data);
    }

    /**
     * 获取{title}字段映射
     *
     * id=>字段 映射
     * test/mapper
     * test/mapper/aaa
     *
     * @auth no 无需鉴权的方法
     * @return void
     */
    public function done_mapper()
    {
        $model = $this->getModel(); //兼容php5
        $field = Request::sget('sdo') ?: 'name';
        $arr = $model::select();
        $ret = array_column($arr, $field, 'id');
        return $ret;
    }

    /**
     * 获取{title}属性映射
     *
     * test/map
     * test/map/aaa
     *
     * @auth no
     * @return void
     */
    public function done_map()
    {
        $model = $this->getModel(); //兼容php5
        $field = Request::sget('sdo') ?: 'status';
        $arr = Prop::getMap($model, $field);
        $list = [];
        if ($arr) {
            foreach ($arr as $id => $name) {
                $list[] = iJson::listItem([$field => $id], Lang::get($name));
            }
        }
        return iJson::listData($list);
    }

    /**
     * 获取字段属性
     * test/prop
     * test/prop/pid
     *
     * @auth no
     * @return array
     */
    public function done_prop()
    {
        $list = [];
        $field = Request::sget('sdo') ?: 'pid';
        $int = Request::sparam('int');
        $arr = (array)Prop::get($field, '', $int);
        $method = sprintf('prop_%s', $field);
        isset($arr[0]) or $arr[0] = Lang::get('Default', 'Prop');
        if (method_exists($this, $method)) {
            $arr = call_user_func([$this, $method]);
        }
        if ($arr) {
            ksort($arr);
            foreach ($arr as $id => $name) {
                $list[] = iJson::listItem([$field => $id], $name);
            }
        }
        return iJson::listData($list);
    }

    /**
     * 获取{title}排序字段
     *
     * @auth no
     * @return void
     */
    public function done_orderby()
    {
        $arr = static::$orderBy;
        return $this->getOrderby($arr);
    }

    public function getOrderby($arr)
    {
        $list = [];
        $asc_text = Lang::get('common:ASC');
        $desc_text = Lang::get('common:DESC');
        if ($arr) {
            foreach ($arr as $id => $name) {
                $name = Lang::get($name);
                $list[$asc_text][] = iJson::listItem($id . ' ASC', $name . ' [' . $asc_text . ']', '');
                $list[$desc_text][] = iJson::listItem($id . ' DESC', $name . '[' . $desc_text . ']', '');
            }
        }
        return iJson::listData($list);
    }

    /**
     * 更新{title}缓存
     *
     * @return void
     */
    public function done_cache()
    {
        if (method_exists($this, 'autoCache')) {
            return $this->autoCache();
        }
    }
    public function do_cache (){
        return $this->done_cache();
    }
    
    public static function autoCache()
    {
    }
    /**
     * 更新{title}小挂件
     *
     * @return void
     */
    // public function do_widget()
    // {
    //     // article/widget/prop
    //     $paths = Request::getPaths();
    //     $request = array_slice($paths, 2);
    //     if ($request[0]) {
    //         $func = [
    //             sprintf('%sWidgetAdmincp', Admincp::$APP),
    //             sprintf('do_%s', $request[0])
    //         ];
    //         // return ArticleWidgetAdmincp
    //         return iPHP::invoke($func);
    //     }
    // }

    protected function selectpage()
    {
        /**
         * result($param, $where, $whereOr, $orderby, &$response) 回调获取数据结果
         * item($value, $param) 回调处理数据成员
         * data(&$result, $param) 回调处理最后result数据
         * list(&$list, $param) 回调处理最后list数据,可处理权限
         *
         */
    }

    /**
     * 获取{title}下拉数据
     *
     * @auth no
     * @return void
     */
    public function done_selectpage()
    {
        $param = Request::param();
        $pageSize = $param['pageSize'] ? (int)$param['pageSize'] : 10;
        $where = $param['where'] ?: [];
        $whereOr = $param['whereOr'] ?: [];

        //初始化请求参数
        // searchTable: tbl
        // searchKey: id
        // searchValue: 9
        if (isset($param['searchKey']) && isset($param['searchValue'])) {
            $values = explode(',', $param['searchValue']);
            $where[$param['searchKey']] = $values;
            $pageSize = count($values);
        }
        $orderby = 'id DESC';
        if ($param['orderBy']) {
            $orderby = implode(' ', $param['orderBy'][0]);
        }
        if ($q_word = $param['q_word']) {
            $andOr = strtoupper($param['andOr']);
            $searchField = implode(',', $param['searchField']);
            $searchField = sprintf('CONCAT(%s)', $searchField);
            // $whereKw = array(sprintf('CONCAT(%s)', $searchField), 'REGEXP', $keywords);
            $likeMode = (int)$param['searchlikemode'];
            $likeMap = ['%%%s%%', '%s%%', '%%%s'];
            foreach ($q_word as $key => $word) {
                // $word = trim($word);
                if ($word != '') {
                    $like = sprintf($likeMap[$likeMode], $word);
                    if ($andOr == 'AND') {
                        $where[] = [$searchField, 'like', $like];
                    } elseif ($andOr == 'OR') {
                        $whereOr[] = [$searchField, 'like', $like];
                    } else {
                    }
                } else {
                    unset($param['q_word'][$key]);
                }
            }
        }
        $param['where'] = $where;
        $param['whereOr'] = $whereOr;
        // var_dump($param);

        // var_dump($param['orderBy']);
        //翻页请求参数
        // $q_word =
        // pageNumber: 2
        // pageSize: 10
        // andOr: AND
        // searchTable: tbl

        $response = iJson::listData([], $pageSize);

        $call = $this->selectpage();
        $call['orderby'] && $orderby = $call['orderby'];

        if ($call['result']) {
            $result = call_user_func_array($call['result'], [$param, $where, $whereOr, $orderby, &$response]);
        } else {
            $modelStr = $this->getModel();
            $model = new $modelStr();
            $where && $model = $model->where($where);
            $whereOr && $model = $model->orWhere($whereOr);
            $model = $model->orderBy($orderby);

            if ($pageSize < 0) {
                $result = $model->select();
                $response['pageSize'] = count($result);
            } else {
                $result = $model->paging($pageSize);
                $response['pageNumber'] = Paging::$index;
                $response['totalRow'] = Paging::$count;
                $response['totalPage'] = Paging::$total;
            }
            $response['sql'] = DB::getQueryLog();
        }
        $response['list'] = $this->spResponseList($param, $result, $call);
        return $response;
    }

    public function spResponseList($param, $result, $call = [])
    {
        $showField = $param['showField'] ?: 'text';
        $textField = $param['textField'] ?: 'name';
        $keyField = $param['keyField'] ?: 'id';
        $fields = $param['fields'] ? explode(',', $param['fields']) : [];
        $list = [];
        if ($result) {
            $call['data'] && call_user_func_array($call['data'], [&$result, $param]);
            foreach ($result as $key => $value) {
                if ($call['item']) {
                    $list[] = call_user_func_array($call['item'], [$value, $param]);
                } else {
                    $id = $value[$keyField];
                    $name = $value[$textField];
                    $text = $value[$showField] ?: sprintf('%s[%s=%s]', $name, $keyField, $id);
                    $items = [];
                    foreach ($fields as $field) {
                        $items[$field] = $value[$field];
                    }
                    $list[] = iJson::listItem($id, $name, $text, $items);
                }
            }
        }
        $call['list'] && call_user_func_array($call['list'], [&$list, $param]);
        // $response = iJson::listData($list, $pageSize);
        return $list;
        // return $response;
    }

    /**
     * 获取{title}动态属性
     *
     * @auth no
     * @return void
     */
    public function done_getmeta()
    {
        $appid = Request::spost('appid');
        $id = Request::spost('id');
        $data = AppsMeta::get($appid, $id, true);
        return $data ? $data : [];
    }

    /**
     * 保存{title}动态属性
     *
     * @auth no
     * @return void
     */
    public function done_savemeta()
    {
        $post = Request::post();
        if ($appId = $post['appid']) {
            $id = $post['id'];
            empty($id) && self::alert('动态属性项目ID不能为空,请先保存项目');
            $mode = $post['mode'];
            $metadata = (array) $post['metadata'];
            AppsMeta::saveData($appId, $id, compact('mode', 'metadata'));
        }
        return true;
    }
}
