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

class NodeAdmincp extends AdmincpCommon
{
    use AdmincpCommonTrait;

    public $NODE_NAME = '节点';
    public $NODE_MODEL = null;
    public $CONTENT_MODEL = null;
    public $ROUTE = [];
    public $RULE = []; //URL规则选项

    public $app = 'content';
    public $title = '内容';
    public $primary = 'cid';
    public static $orderBy = [
        'id'      => 'ID',
        'sortnum' => '排序值',
        'dir'     => '目录值',
        'count'   => '记录数'
    ];
    public $VIEW_ADD = 'add';
    public $VIEW_INDEX = 'index';
    public $VIEW_DIR = 'node';
    public $active_nav = 'tree';

    public function __construct($appid = 0)
    {
        self::$MODEL = 'NodeModel';
        self::$CLASS = 'Node';
        parent::__construct($appid);
        Node::$APPID = $appid;
        if ($_appid = (int) Request::sget('appid')) {
            Node::$APPID = $_appid;
        };
        Lang::load('node');
        $this->ROUTE = Etc::get('node', 'route/node');
        $this->RULE = Etc::many('*', 'admincp/node.rule', 'recursive', false);
        if (Node::$APPID) {
            $appids = array_flip(iCMS::$config['apps']);
            $name = $appids[Node::$APPID];
            $this->loadRoute($name);
        }
    }

    public function loadRoute($app = null)
    {
        is_null($app) && $app = $this->app;
        if (is_numeric($app)) {
            $a = Apps::getDataLite($app);
            $app = $a['app'];
        }
        if ($route = Etc::many($app, 'route/node*', true, false)) {
            $this->ROUTE = array_merge($this->ROUTE, $route);
            $this->ROUTE = array_filter($this->ROUTE);
            sortKey($this->ROUTE, 'sort');
        }
    }

    public function setRoute($route)
    {
        $this->ROUTE = array_merge($this->ROUTE, $route);
    }

    /**
     * 添加{title}分类
     * @param  array $default [description]
     * @return void
     */
    public function do_add($rs = [])
    {
        if ($this->id) {
            AdmincpAuth::node($this->id, 'e');
            $rs = NodeModel::get($this->id);
            $rootid = $rs['rootid'];
        } else {
            $rootid = (int) Request::get('rootid');
        }

        if (empty($rs)) {
            $rs = [
                'pid'       => '0',
                'status'    => '1',
                'config'    => [
                    'ucshow'  => '1',
                    'send'    => '1',
                    'examine' => '1',
                ],
                'sortnum'   => '0',
                'mode'      => '0',
                'htmlext'   => Config::get('route.ext'),
            ];
            if ($rootid) {
                $rootRs = NodeModel::get($rootid);
                $rs['htmlext'] = $rootRs['htmlext'];
            }
            $this->adding($rs);
        }

        if (Admincp::$APP != 'node') {
            $extends1 = AdmincpView::getExtends('add', 'node', false);
            $extends2 = AdmincpView::getExtends('app/node/add*', $this->app, false);
            self::$EXTENDS = array_merge(self::$EXTENDS, $extends1, $extends2);
        }

        // AdmincpBase::$DEBUG['EXTENDS'] = self::$EXTENDS;

        // self::added($this, __METHOD__, $rs);
        self::setJsPath('node/assets/js/add');
        include self::view($this->VIEW_ADD, $this->VIEW_DIR);
    }

    /**
     * 保存节点
     *
     */
    public function save(&$data = [])
    {
        $data = NodeModel::postData();
        $attribute = Request::post('attribute');

        Node::$APPID && $data['appid'] = Node::$APPID;

        if ($data['id'] && $data['id'] == $data['rootid']) {
            self::alert('不能以自身做为上级' . $this->NODE_NAME);
        }

        empty($data['name']) && self::alert($this->NODE_NAME . '名称不能为空');

        if ($data['mode'] == '2') {
            foreach ($data['rule'] as $key => $value) {
                $CR = $this->ROUTE[$key];
                $CRKW = explode(',', $CR['tips']);
                $cr_check = true;
                foreach ($CRKW as $i => $crk) {
                    $crk = str_replace(['{', '}'], '', $crk);
                    if (strpos($value, $crk) !== false) {
                        $cr_check = false;
                    }
                }
                if ($cr_check && empty($data['domain']) && $key != 'tag') {
                    self::alert('伪静态模式' . $CR['label'] . '规则必需含有' . $CR['tips'] . '其中之一,否则将无法解析');
                }
            }
        }

        if ($attribute) {
            empty($data['config']) && $data['config'] = [];
            $data['config']['attribute'] = $attribute;
        }

        $id = $data['id'];
        if (empty($id)) {
            AdmincpAuth::node($data['rootid'], 'a');

            $nameArray = explode("\n", $data['name']);
            $nameArray = array_filter($nameArray, function ($name) {
                return trim($name) !== '';
            });
            $dir = trim($data['dir']);
            $count = count($nameArray);
            foreach ($nameArray as $nkey => $name) {
                $name = trim($name);
                if ($dir && $count > 1) {
                    $data['dir'] = $dir . '-' . ($nkey + 1);
                } else {
                    empty($data['url']) && empty($dir) && $data['dir'] = strtolower(Pinyin::get($name));
                }
                $data['mode'] == '2' && $data['dir'] = $this->checkDir($data['dir'], Node::$APPID, $data['url']);
                $data['name'] = $name;
                $data['addtime'] = time();
                $data['userid'] = Admin::$id;
                $data['creator'] = Admin::$nickname;
                $data['count'] = 0;
                $data['comment'] = 0;
                $data['id'] = NodeModel::create($data, true);
                NodeModel::update(['sortnum' => $data['id']], $data['id']);
            }
        } else {
            AdmincpAuth::node($data['id'], 'e');
            if (empty($data['dir']) && empty($data['url'])) {
                $data['dir'] = strtolower(Pinyin::get($data['name']));
            }
            $data['mode'] == '2' && $data['dir'] = $this->checkDir($data['dir'], Node::$APPID, $data['url'], $data['id']);
            NodeModel::update($data, $data['id']);
        }
        // self::saved($this, __METHOD__, $data);
        $this->cache_item($id);
        return Admincp::url('index', true);
        // return Admincp::url('edit/id/'.$data['id'], true);
    }

    /**
     * 获取节点下拉数据
     *
     * @auth no
     * @return void
     */
    public function done_selectpage()
    {
        return parent::done_selectpage();
    }

    public function selectpage()
    {
        return [
            'list' => function (&$list, $param) {
                if ($param['root']) {
                    if (AdmincpAuth::node(0, 'a')) {
                        $name = '顶级' . $param['name'] ?: '节点';
                        $head = iJson::listItem('0', $name, '======' . $name . '======');
                        array_unshift($list, $head);
                    }
                }
            },
            'result' => function ($param, $where, $whereOr, $orderby, &$response) {
                $pageSize = $param['pageSize'] ? (int)$param['pageSize'] : 10;
                $offset = ($param['pageNumber'] - 1) * $pageSize;
                isset($param['appid']) && Node::$APPID = $param['appid'];
                $total = Node::total(Node::$APPID);
                if ($total > NodeCache::BURST) {
                    $param['appid'] && $where['appid'] = $param['appid'];
                    $model = NodeModel::where($where);
                    $result = $model->paging($pageSize);
                    $response['pageNumber'] = Paging::$index;
                    $response['totalRow'] = Paging::$count;
                    $response['totalPage'] = Paging::$total;
                    $response['sql'] = DB::getQueryLog();
                    return $result;
                }

                Node::$callback['func'] = function ($node, $level, $child, $where) use ($param) {
                    $id = $node['id'];
                    $isSearch = true;
                    if ($searchValue = $param['searchValue']) {
                        $isSearch = false;
                        $search = explode(',', $searchValue);
                        $value = $node[$param['searchKey']] . $id;
                        foreach ($search as $sk => $sv) {
                            if (stripos($value, $sv) !== false) {
                                $isSearch = true;
                                break;
                            }
                        }
                    }
                    if ($param['access'] && AdmincpAuth::node($id, $param['access'])) {
                        if ($node['status'] && $isSearch) {
                            $tag = ($level == '1' ? '' : '├ ');
                            $text = str_repeat('│ ', $level - 1) . $tag . $node['name'] . "[id:{$id}][appid:" . $node['appid'] . ']' . ($node['outurl'] ? '[∞]' : '');
                            $option = iJson::listItem($id, $node['name'], $text);
                        }
                    }
                    return  $option ? [$option] : [];
                };
                Node::$callback['mode'] = 1; //一维数组
                return Node::callfunc();
            }
        ];
    }

    public function batching()
    {
        AdmincpBatch::set('app', 'node');
        AdmincpBatch::set('name', $this->NODE_NAME);

        return [
            'move' => function ($ids, $data) {
                $rootid = (int) $data['nid'];
                foreach ($ids as $k => $id) {
                    if ($rootid == $id) {
                        unset($ids[$k]);
                    }
                }
                NodeModel::update(compact('rootid'), $ids);
                $this->update_app_count($rootid);
            },
            'merge' => function ($ids, $data) {
                $toid = (int) $data['toid'];
                foreach ($ids as $k => $id) {
                    if ($toid != $id) {
                        $this->merge($toid, $id);
                        $this->done_delete($id, false);
                    }
                }
                $this->update_app_count($toid);
                // $this->cache(true,Node::$APPID);
            },
            'dir' => function ($ids, $data) {
                $pat = $data['pattern'];
                $dir = $data['dir'];
                if ($pat == 'prepend') {
                    $dir = ['raw' => 'CONCAT(?,dir)', $dir];
                }
                if ($pat == 'append') {
                    $dir = ['raw' => 'CONCAT(dir,?)', $dir];
                }
                NodeModel::update(compact('dir'), $ids);
            },
            'mkdir' => function ($ids, $data) {
                $all = NodeModel::field('id,name')->where($ids)->select();
                foreach ($all as $k => $node) {
                    $name = $node['name'];
                    $dir = Pinyin::get($name);
                    $this->checkDir($dir, Node::$APPID, null, $node['id']);
                    NodeModel::update(['dir' => $dir], $node['id']);
                }
                return true;
            },
            'recount' => function ($ids, $data) {
                $this->update_app_count($ids);
                return true;
            },
            'rule' => function ($ids, $data) {
                $rule = Request::post('rule');
                NodeModel::update(compact('rule'), $ids);
            },
            'template' => function ($ids, $data) {
                $template = Request::post('template');
                NodeModel::update(compact('template'), $ids);
            }
        ];
    }

    /**
     * 更新排序
     */
    public function done_update_sort()
    {
        $sortnum = Request::post('sortnum');
        if ($sortnum) {
            foreach ($sortnum as $sortnum => $id) {
                NodeModel::update(['sortnum' => $sortnum], $id);
            }
        }
    }

    /**
     * 更新
     */
    public function done_update_tree()
    {
        $names = (array)Request::post('name');
        $dirs = (array)Request::post('dir');
        $sortnum = Request::post('sortnum');
        if ($names) {
            foreach ($names as $id => $name) {
                NodeModel::update([
                    'name'    => $name,
                    'dir'     => $dirs[$id],
                    'sortnum' => (int) $sortnum[$id]
                ], $id);
            }
        }
    }
    // public function done_mapper()
    // {
    //     $arr = NodeModel::select();
    //     $ret = array_column($arr, 'name', 'id');
    //     return $ret;
    // }

    public function do_index()
    {
        $this->do_tree();
    }

    /**
     * 树模式
     */
    public function do_tree()
    {
        $total = Node::total(Node::$APPID);
        if ($total > NodeCache::BURST) {
            $this->active_nav = 'list';
            return $this->do_list();
        }
        $this->active_nav = 'tree';
        self::setJsPath('node/assets/js/tree');
        include self::view($this->VIEW_INDEX . '_tree', $this->VIEW_DIR);
    }

    /**
     * 列表模式
     */
    public function do_list()
    {
        $this->active_nav = 'list';
        if (Node::$APPID) {
            $where['appid'] = Node::$APPID;
            // $apps = Apps::get(Node::$APPID);
        }
        $nids = AdmincpAuth::node('IDS', 'm');
        is_bool($nids) or $access = ['id' => $nids];

        $keywords = Request::get('keywords');
        $st = Request::sget('st');

        if ($keywords && $st) {
            if (in_array($st, ['name', 'dir', 'id'])) {
                $where[$st] = ['REGEXP', $keywords];
            } elseif ($st == 'appid') {
                $where['appid'] = $keywords;
            } elseif ($st == 'tkd') {
                $where['CONCAT(name,title,keywords,description)'] = ['REGEXP', $keywords];
            }
        }
        $rootid = Request::get('rootid');
        is_numeric($rootid) && $where['rootid'] = $rootid;
        $status = Request::get('status');
        is_numeric($status) && $where['status'] = $status;
        $sub = Request::get('sub');
        isset($sub) && $where['rootid'] = ['<>', 0];

        if (Request::isAjax()) {
            return self::tableData([
                'where' => $where,
                'with'  => $access
            ], function ($rows) {
                $rootidArray = array_column($rows, 'rootid');
                $rootidArray && $root_data = (array) Node::get($rootidArray);

                $rows = array_map(function ($item) use ($root_data) {
                    $root = $root_data[$item['rootid']];
                    $item['rootid_text'] = $root ? $root['name'] : '顶级' . $this->NODE_NAME;
                    NodeItem::url($item);
                    return $item;
                }, $rows);

                return $rows;
            });
        }
        self::setContext([
            'tablist' => 'params',
            'params'  => [
                'status' => $status,
            ]
        ]);
        self::setJsPath('node/assets/js/list');
        include self::view($this->VIEW_INDEX . '_list', $this->VIEW_DIR);
    }

    protected function __lang()
    {
        return Lang::load('node', false);
    }

    // public function do_copy()
    // {
    //     $data = NodeModel::get($this->id);
    //     $data['name'] .= '副本';
    //     $data['dir'] .= 'fuben';
    //     unset($data['id']);
    //     $id = NodeModel::create($data);
    //     // self::success('克隆完成');
    // }
    public function deleting($ids = [])
    {
        if ($ids) {
            $msg = '';
            foreach ($ids as $key => $id) {
                AdmincpAuth::node($id, 'd');

                if (Node::hasChild($id)) {
                    $msg .= '请先删除本' . $this->NODE_NAME . '下的子' . $this->NODE_NAME . '!';
                } else {
                    NodeModel::delete($id);
                    NodeCache::deleteId($id);
                }
            }
        }
        $this->done_cache(false);
        return $msg;
        // $dialog && self::success($msg);
    }

    public function done_tree()
    {
        $root = (int)Request::post('root');
        Node::$callback['func'] = [$this, 'tree'];
        Node::$callback['recursive'] = false;
        Node::$callback['mode'] = 2; //多维数组 有层级
        return ['data'=>Node::callfunc($root)];
    }

    public function tree($node, $level, $child, $where)
    {
        $expanded = Request::get('expanded') ? true : false;
        $a = ['id' => $node['id'], 'data' => $this->treeData($node)];
        if ($child) {
            if ($expanded) {
                $a['hasChildren'] = false;
                $a['expanded'] = false;
                $a['children'] = Node::callfunc($node['id'], $where, $level + 1);
            } else {
                $a['hasChildren'] = true;
            }
        }
        if (AdmincpAuth::node($a['id'], 'm')) {
            return $a;
        } else {
            if ($a['children']) {
                $a['data'] = null;
                return $a;
            } else {
                return [];
            }
        }
    }

    public function treeData($C)
    {
        unset(
            $C['rule'],
            $C['template'],
            $C['description'],
            $C['keywords'],
            $C['password'],
            $C['mpic'],
            $C['spic'],
            $C['title'],
            $C['subname'],
            $C['iurl'],
            $C['htmlext'],
            $C['config'],
            $C['comment']
        );
        is_array($C['pid']) && $C['pids'] = implode(',', $C['pid']);
        return $C;
    }

    public function done_attrs()
    {
        $ids = Request::post('ids');
        $result = NodeModel::field('id,config')->where($ids)->select();
        $attrs = [];
        foreach ((array) $result as $key => $value) {
            $config = isset($value['config']) ? $value['config'] : [];
            if (isset($config['attribute'])) {
                $attribute = $config['attribute'] ?: [];
                if ($values = $attribute['value']) {
                    $attribute['value'] = explode("\n", trim($values));
                }
                $attrs[] = $attribute;
            }
        }
        return $attrs;
    }

    /**
     * 更新{title}统计
     *
     * @return void
     */
    public function done_recount()
    {
        Node::$APPID && $where['appid'] = Node::$APPID;
        $result = NodeModel::where($where)->select();
        foreach ((array) $result as $key => $value) {
            $this->update_app_count($value['id']);
        }
        // $dialog && self::success('更新完成');
    }

    /**
     * 获取内容元属性设置
     */
    public static function do_appMeta($ret = false, $id = null)
    {
        $id === null && $id = (int) $_GET['id'];
        if ($id) {
            return Node::getAppMeta($id);
            // return $meta;
            // if ($ret) {
            // }
            // return self::success($meta);
        }
    }

    /**
     * autoCache 在更新所有缓存时，将会自动执行
     */
    public static function autoCache()
    {
        @set_time_limit(0);
        Node::config();
        $total = Node::total(Node::$APPID);
        if ($total > NodeCache::BURST) {
            return [
                'data' => [
                    'pageSize'     => NodeCache::BURST,
                    'pageTotal'    => ceil($total / NodeCache::BURST),
                    'totalCount'   => $total
                ],
                'url'  => Admincp::url('cache_burst', true),
                'state'=> 'worker.js'
            ];
        } else {
            NodeCache::make(Node::$APPID);
        }
    }

    /**
     * 分批更新缓存
     *
     * @access false
     * @return void
     */
    public function done_cache_burst()
    {
        @set_time_limit(0);
        $page = (int)Request::post('page', 1);
        $pageTotal = (int)Request::post('pageTotal', 0);
        $totalCount = (int)Request::post('totalCount', 0);
        $pageSize = (int)Request::post('pageSize', NodeCache::BURST);
        $worker = (int)Request::post('worker', 0);

        if ($pageTotal > 0 && $page > $pageTotal) {
            return [true, '更新完成'];
        } else {
            $finish_count = $page * $pageSize;
            NodeCache::make(Node::$APPID, $pageSize);
            return [[
                'page'         => $page,
                'pageSize'     => NodeCache::BURST,
                'pageTotal'    => Paging::$total,
                'totalCount'   => Paging::$count
            ], sprintf(
                $this->NODE_NAME . '缓存更新完成:%s,当前页:%s,总页数:%s,总记录数:%s',
                $finish_count,
                $page,
                Paging::$total,
                Paging::$count
            )];
        }
        // DB::query('SET interactive_timeout=24*3600');

        // $num = 100;
        // $appid = (int) $_GET['_appid'];
        // $page = (int) $_GET['page'];
        // $total = (int) $_GET['total'];
        // $flag = $_GET['flag'];
        // $offset = $page * $num;

        // empty($flag) && $flag = 'tmp';

        // $config = [];
        // if ($flag === 'stop') {
        //     //结束
        //     $config['stop'] = [
        //         'msg'  => '<div class="alert alert-info">' . $this->NODE_NAME . '缓存更新完成</div>',
        //         'time' => '5',
        //     ];
        // } else {
        //     $callback = [
        //         ['NodeCache', 'burst'],
        //         [$appid, $offset, $num, $flag]
        //     ];
        //     $map = [
        //         'tmp'    => ['gold', '生成' . $this->NODE_NAME . '临时缓存'],
        //         'gold'   => ['delete', '生成' . $this->NODE_NAME . '缓存'],
        //         'delete' => ['common', '清理' . $this->NODE_NAME . '临时缓存'],
        //         'common' => ['stop', '生成通用缓存'],
        //     ];

        //     $next = $map[$flag][0];
        //     //下一步
        //     $config['step'] = [
        //         'title'    => '<div class="alert">正在' . $map[$flag][1] . '</div>',
        //         'callback' => $callback,
        //         'url'      => 'do=cache_burst&_appid=' . $appid . '&flag=' . $flag . '&CSRF_TOKEN=' . Security::$CSRF_TOKEN,
        //         'msg'      => [$this->NODE_NAME, '个']
        //     ];
        //     //下一批
        //     $config['next'] = [
        //         'url'  => 'do=cache_burst&_appid=' . $appid . '&flag=' . $next . '&total=' . $total . '&CSRF_TOKEN=' . Security::$CSRF_TOKEN,
        //         'msg'  => '<hr /><div class="alert alert-info">准备进行' . $map[$next][1] . '</div>',
        //         'time' => '3',
        //     ];
        //     if ($flag === 'common') {
        //         $total = $num = 1;
        //         $config['step']['msg'] = ['操作', '个'];
        //         $config['next']['msg'] = '';
        //     }
        // }
        // Script::loop($total, $num, $config);
    }

    public function cache_item($id)
    {
        $data = NodeModel::get($id);
        NodeCache::setData($data);
        NodeCache::setId($data);
        NodeCache::delData($data['id']);
    }

    public function checkDir($dir, $appid, $url, $id = 0)
    {
        if (empty($url)) {
            $where = ['dir' => $dir];
            $id && $where['id'] = ['!=', $id];
            $hasDir = NodeModel::where($where)->count();
            if ($hasDir) {
                $count = NodeModel::where('dir', 'like', "{$dir}-%")->count();
                $dir = $dir . '-' . ($count + 1);
            }
        }
        return $dir;
    }

    public function merge($tonid, $id)
    {
        $this->CONTENT_MODEL->update(
            [$this->primary => $tonid],
            [$this->primary => $id]
        );
        Tag::move($id, $tonid);
        DB::table('prop')->update(
            ['cid' => $tonid],
            ['cid' => $id]
        );
    }

    public function update_app_count($id)
    {
        $node = NodeModel::get($id);
        if (empty($node)) {
            return false;
        }
        try {
            $appid = $node['appid'];
            if ($appid) {
                $a = Apps::getDataLite($appid);
                $app = $a['app'];
            }
            $model = ContentModel::setTable($app);
            if($model){
                $cc = $model->where([$this->primary => $id])->count();
                NodeModel::update(['count' => $cc], $id);
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public static function widget_count($where = null)
    {
        $total = NodeModel::where($where)->count();
        $widget[] = [$total, '全部'];
        foreach (NodeModel::$statusMap as $status => $text) {
            $count = NodeModel::where('status', $status)->where($where)->count();
            $widget[] = [$count, $text];
        }
        return $widget;
    }

    public function route_rule($s = null)
    {
        is_null($s) && $s = ['rule' => true, 'rule_name' => 'rule'];
        include AdmincpBase::view('route/base', $this->VIEW_DIR);
    }

    public function route_template($s = null)
    {
        is_null($s) && $s = ['tpl' => true, 'tpl_name' => 'template'];
        include AdmincpBase::view('route/base', $this->VIEW_DIR);
    }
}
