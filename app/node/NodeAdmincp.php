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
    public $callback         = array();
    protected $NODE_URL      = APP_URL;
    protected $NODE_NAME     = "节点";
    protected $NODE_MODEL    = null;
    protected $CONTENT_MODEL = null;
    protected $ROUTE = [];
    /**
     *  URL规则选项
     */
    protected $RULE = array(
        'tag' => array(
            array('----'),
            array('{TKEY}', '标签标识'),
            array('{ZH_CN}', '标签名(中文)'),
            array('{NAME}', '标签名'),
            array('----'),
            array('{TCID}', '分类ID', false),
            array('{TCDIR}', '分类目录', false),
        ),
    );


    protected $app         = 'content';
    protected $title       = '内容';
    protected $primary     = 'id';
    protected $app_id      = null;

    protected $VIEW_ADD    = 'node.add';
    protected $VIEW_MANAGE = 'node.manage';
    protected $VIEW_DIR    = 'node';

    public function __construct($appid = null)
    {
        self::$MODEL = 'NodeModel';
        
        parent::__construct(iCMS_APP_NODE);
        $this->id = (int) Request::get('id');
        $this->app_id = $appid;
        $_GET['appid']  && $this->app_id = (int) $_GET['appid'];
        Node::$APPID = $this->app_id;
        $this->ROUTE = Etc::get('node', 'node.route');

    }
    public function setViewDir($dir)
    {
        $this->VIEW_DIR = $dir;
    }
    public function loadRoute($app = null)
    {
        is_null($app) && $app = $this->app;
        if ($route = Etc::many($app, 'node.route*',true,false)) {
            $this->ROUTE = array_merge($this->ROUTE, $route);
            sortKey($this->ROUTE, 'sort');
        }
    }
    public function setRoute($route)
    {
        $this->ROUTE = array_merge($this->ROUTE, $route);
        // file_put_contents(iPHP_APP_DIR.'/'.$this->app.'/etc/node.route.json',cnjson_encode($this->ROUTE ));
    }
    /**
     * [添加{title}分类]
     * @param  array $default [description]
     * @return [type]          [description]
     */
    public function do_add($default = null)
    {
        if ($this->id) {
            NodeAccess::check($this->id, 'e', 'page');
            $rs        = NodeModel::get($this->id);
            $rootid    = $rs['rootid'];
        } else {
            $rootid = (int) Request::get('rootid');
        }
        $rootid && $rootNode = Node::get($rootid);

        if (empty($rs)) {
            $rs = array(
                'pid'       => '0',
                'status'    => '1',
                'config' => array(
                    'ucshow'  => '1',
                    'send'    => '1',
                    'examine' => '1',
                ),
                'sortnum'   => '0',
                'mode'      => '0',
                'htmlext'   => Config::get('route.ext'),
            );
            if ($rootid) {
                $rootRs = NodeModel::get($rootid);
                $rs['htmlext']  = $rootRs['htmlext'];
            }
            if ($default) {
                $rs = array_merge($rs, (array) $default);
            }
        }
        $extends1 = Config::scan('node.config', 'node', false);
        $extends2 = Config::scan('node.config', $this->app, false);
        self::$EXTENDS  = array_merge(self::$EXTENDS, $extends1, $extends2);

        // AdmincpBase::$DEBUG['EXTENDS'] = self::$EXTENDS;

        self::added($this, __METHOD__, $rs);
        include self::view($this->VIEW_ADD, $this->VIEW_DIR);
    }
    /**
     * [保存节点]
     *
     * @return  [type]  [return description]
     */
    public function save()
    {
        $data = NodeModel::postData();
        $data['id'] && NodeAccess::check($data['id'], 'e', 'alert');
        NodeAccess::check($data['rootid'], 'a', 'alert');

        $this->app_id !== null && $data['appid'] = $this->app_id;

        if ($data['id'] && $data['id'] == $data['rootid']) {
            self::alert('不能以自身做为上级' . $this->NODE_NAME);
        }

        empty($data['name']) && self::alert($this->NODE_NAME . '名称不能为空');

        if ($data['mode'] == "2") {
            foreach ($data['rule'] as $key => $value) {
                $CR = $this->ROUTE[$key];
                $CRKW = explode(',', $CR['tips']);
                $cr_check = true;
                foreach ($CRKW as $i => $crk) {
                    $crk = str_replace(array('{', '}'), '', $crk);
                    if (strpos($value, $crk) !== false) {
                        $cr_check = false;
                    }
                }
                if ($cr_check && empty($data['domain']) && $key != 'tag') {
                    self::alert('伪静态模式' . $CR['label'] . '规则必需含有' . $CR['tips'] . '其中之一,否则将无法解析');
                }
            }
        }

        //内容元属性
        if ($data['config']) {
            $meta = array();
            if (is_array($data['config']['meta'])) foreach ($data['config']['meta'] as $mk => $meta) {
                if ($meta['name']) {
                    $meta['key'] or $meta['key'] = strtolower(Pinyin::get($meta['name']));
                    if (!preg_match("/[a-zA-Z0-9_\-]/", $meta['key'])) {
                        self::alert('只能由英文字母、数字或_-组成(不支持中文),留空则自动以名称拼音填充');
                    }
                    $meta['key'] = trim($meta['key']);
                    $data['config']['meta'][$mk] = $meta;
                }
            }
        }
        $id = $data['id'];
        if (empty($id)) {
            NodeAccess::check($data['rootid'], 'a');
            $nameArray = explode("\n", $data['name']);
            $_count    = count($nameArray);
            foreach ($nameArray as $nkey => $name) {
                $name    = trim($name);
                if (empty($name)) {
                    continue;
                }
                if ($_count == "1") {
                    if (empty($data['dir']) && empty($data['url'])) {
                        $data['dir'] = strtolower(Pinyin::get($name));
                    }
                } else {
                    empty($data['url']) && $data['dir'] = strtolower(Pinyin::get($name));
                }
                $data['mode'] == "2" && $data['dir'] = $this->checkDir($data['dir'], $this->app_id, $data['url']);
                $data['name'] = $name;
                $data['addtime'] = time();
                $data['count'] = 0;
                $data['comment'] = 0;
                $data['id'] = NodeModel::create($data, true);
                NodeModel::update(array('sortnum' => $data['id']), $data['id']);
            }
        } else {
            if (empty($data['dir']) && empty($data['url'])) {
                $data['dir'] = strtolower(Pinyin::get($data['name']));
            }
            NodeAccess::check($data['id'], 'e');
            $data['mode'] == "2" && $data['dir'] = $this->checkDir($data['dir'], $this->app_id, $data['url'], $data['id']);
            NodeModel::update($data, $data['id']);
        }
        self::saved($this, __METHOD__, $data);
        $this->cache_item($id);
        return APP_URL;
    }

    public function do_update()
    {
        $names = (array)Request::post('name');
        if ($names) foreach ($names as $id => $name) {
            NodeModel::update(array(
                'name' => $name,
                'sortnum' => (int) $_POST['sortnum'][$id]
            ), $id);
        }
        // self::success('更新完成');
    }
    public function do_batch()
    {
        AdmincpBatch::$config['etc.app'] = 'node';
        AdmincpBatch::$config['etc.name'] = $this->NODE_NAME;

        $actions = array(
            'merge' => function ($idArray, $ids, $batch) {
                $tonid = (int) $_POST['tonid'];
                foreach ($idArray as $k => $id) {
                    if ($tonid != $id) {
                        $this->merge($tonid, $id);
                        $this->do_delete($id, false);
                    }
                }
                $this->update_app_count($tonid);
                // $this->cache(true,$this->app_id);
            },
            'dir' => function ($idArray, $ids, $batch) {
                $bdir = Request::post('bdir');
                $pat = $_POST['pattern'];
                $dir = $bdir;
                if ($pat == 'addtobefore') {
                    $dir = array('raw' => 'CONCAT(?,dir)', $bdir);
                }
                if ($pat == 'addtoafter') {
                    $dir = array('raw' => 'CONCAT(dir,?)', $bdir);
                }
                NodeModel::update(compact('dir'), $idArray);
            },
            'mkdir' => function ($idArray, $ids, $batch) {
                $names = Request::post('name');
                foreach ($idArray as $k => $id) {
                    $name = $names[$id];
                    $dir  = Pinyin::get($name);
                    $this->checkDir($dir, $this->app_id, null, $id);
                    NodeModel::update(array('dir' => $dir), $id);
                }
                return true;
            },
            'recount' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $k => $id) {
                    $this->update_app_count($id);
                }
                return true;
            },
            'update' => function ($idArray, $ids, $batch) {
                $names = Request::post('name');
                $dirs = Request::post('dir');
                $sortnums = Request::post('sortnum');
                foreach ($idArray as $k => $id) {
                    NodeModel::update(array(
                        'name'    => $names[$id],
                        'dir'     => $dirs[$id],
                        'sortnum' => intval($sortnums[$id]),
                    ), $id);
                }
            },
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    NodeAccess::check($id, 'd', 'alert');
                    $this->do_delete($id, false);
                }
                // self::success('删除完成');
            },
            'default' => function ($idArray, $ids, $batch, $data = null) {
                $data && NodeModel::update($data, $idArray);
                return true;
            },
        );
        // $this->cache(true,$this->app_id);
        return AdmincpBatch::run($actions, $this->NODE_NAME);
    }
    /**
     * [更新排序]
     * @return [type] [description]
     */
    public function do_update_sort()
    {
        foreach ((array) $_POST['sortnum'] as $sortnum => $id) {
            NodeModel::update(array('sortnum' => $sortnum), $id);
        }
    }
    public function do_manage()
    {
        $this->tabs = Cookie::get(Admincp::$APP_NAME . '_tabs') ?: 'tree';
        $_GET['tabs'] && $this->tabs = $_GET['tabs'];
        $this->app_id && $apps = Apps::get($this->app_id);
        Admincp::$APP_NAME == 'node' && Menu::$DATA['breadcrumb']['title'] = $apps['name'];
        Menu::setData('nav.active', APP_URL);
        $this->tabs == "list" ? $this->manage_list() : $this->manage_tree();
    }
    /**
     * [树模式]
     * @return [type] [description]
     */
    public function manage_tree()
    {
        $this->tabs = 'tree';
        //node.manage_tree.html
        include self::view($this->VIEW_MANAGE . '_tree', $this->VIEW_DIR);
    }
    /**
     * [列表模式]
     * @return [type] [description]
     */
    public function manage_list()
    {
        $this->tabs = 'list';
        if ($this->app_id) {
            $where['appid'] = $this->app_id;
            // $apps = Apps::get($this->app_id);
        }
        $nids = NodeAccess::check('IDS', 'm');
        is_bool($nids) or $access = array('id' => $nids);

        $keywords = Request::get('keywords');
        $st = Request::sget('st');

        if ($keywords && $st) {
            if (in_array($st, array('name', 'dir', 'id'))) {
                $where[$st] = array('REGEXP', $keywords);
            } elseif ($st == "appid") {
                $where['appid'] = $keywords;
            } elseif ($st == "tkd") {
                $where['CONCAT(name,title,keywords,description)'] = array('REGEXP', $keywords);
            }
        }
        $rootid = Request::get('rootid');
        is_numeric($rootid) && $where['rootid'] = $rootid;
        $status = Request::get('status');
        is_numeric($status) && $where['status'] = $status;

        $orderby = $this->getOrderBy();
        $result = NodeModel::where($access)->where($where)
            ->orderBy($orderby)
            ->paging();

        include self::view($this->VIEW_MANAGE . '_list', $this->VIEW_DIR);
    }
    public function do_copy()
    {
        $data = NodeModel::get($this->id);
        $data['name'] .= '副本';
        $data['dir'] .= 'fuben';
        unset($data['id']);
        $id = NodeModel::create($data);
        // self::success('克隆完成');
    }
    public function do_delete($id = null)
    {
        $id === null && $id = (int) $_GET['id'];
        NodeAccess::check($id, 'd');
        $msg    = '请选择要删除的' . $this->NODE_NAME . '!';

        if (Node::hasChild($id)) {
            $msg = '请先删除本' . $this->NODE_NAME . '下的子' . $this->NODE_NAME . '!';
        } else {
            NodeModel::delete($id);
            NodeCache::deleteId($id);
            $msg = '删除成功!';
        }
        $this->do_cache(false);
        // $dialog && self::success($msg);
    }
    public function do_ajaxtree()
    {
        $root = (int) $_GET["root"];

        Node::$ACCESS = 'm';
        Node::$callback['func']  = array($this, 'tree');
        Node::$callback['recursive'] = false;
        Node::$callback['result'] = array();
        return Node::callfunc($root);
        // return self::success($result);
    }
    public function tree($node, $level, $child)
    {
        $expanded = $_GET['expanded'] ? true : false;
        $a = array('id' => $node['id'], 'data' => $this->treeData($node));
        if ($child) {
            if ($expanded) {
                $a['hasChildren'] = false;
                $a['expanded']    = true;
                $a['children']    = Node::callfunc($node['id'], null, $level + 1);
            } else {
                $a['hasChildren'] = true;
            }
        }
        if (NodeAccess::check($a['id'], Node::$ACCESS)) {
            return $a;
        } else {
            if ($a['children']) {
                $a['data'] = null;
                return $a;
            } else {
                return array();
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
            $C['dir'],
            $C['htmlext'],
            $C['config'],
            $C['comment']
        );
        is_array($C['pid']) && $C['pids'] = implode(',',$C['pid']);
        return $C;
    }
    /**
     * 更新{title}统计
     *
     * @return void
     */
    public function do_recount()
    {
        $result = NodeModel::where('appid', $this->app_id)->select();
        foreach ((array) $result as $key => $value) {
            $this->update_app_count($value['id']);
        }
        // $dialog && self::success('更新完成');
    }
    /**
     * [获取内容元属性设置]
     * @return [type] [description]
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
     * 更新{title}缓存
     *
     * @return void
     */
    public function do_cache()
    {
        $this->autoCache();
    }
    /**
     * [autoCache 在更新所有缓存时，将会自动执行]
     */
    public function autoCache()
    {
        @set_time_limit(0);
        self::config();
        $total = Node::total($this->app_id);
        if ($total > 500) {
            $_GET['total']  = $total;
            $_GET['_appid'] = $this->app_id;
            $this->do_cache_burst();
        } else {
            NodeCache::make($this->app_id);
        }
    }
    /**
     * [分批更新缓存 #NO:ACCESS#]
     * @param  [type] $total [description]
     * @return [type]        [description]
     */
    public function do_cache_burst()
    {
        @set_time_limit(0);
        DB::query("SET interactive_timeout=24*3600");

        $num      = 100;
        $appid    = (int) $_GET['_appid'];
        $page     = (int) $_GET['page'];
        $total    = (int) $_GET['total'];
        $flag     = $_GET['flag'];
        $offset   = $page * $num;

        empty($flag) && $flag = 'tmp';

        $config = array();
        if ($flag === 'stop') {
            //结束
            $config['stop'] = array(
                'msg'  => '<div class="alert alert-info">' . $this->NODE_NAME . '缓存更新完成</div>',
                'time' => '5',
            );
        } else {
            $callback = array(
                array('NodeCache', 'burst'),
                array($appid, $offset, $num, $flag)
            );
            $map = array(
                'tmp'    => array('gold', '生成' . $this->NODE_NAME . '临时缓存'),
                'gold'   => array('delete', '生成' . $this->NODE_NAME . '缓存'),
                'delete' => array('common', '清理' . $this->NODE_NAME . '临时缓存'),
                'common' => array('stop', '生成通用缓存'),
            );

            $next = $map[$flag][0];
            //下一步
            $config['step'] = array(
                'title'    => '<div class="alert">正在' . $map[$flag][1] . '</div>',
                'callback' => $callback,
                'url'      => 'do=cache_burst&_appid=' . $appid . '&flag=' . $flag . '&CSRF_TOKEN=' . Security::$CSRF_TOKEN,
                'msg'      => array($this->NODE_NAME, '个')
            );
            //下一批
            $config['next'] = array(
                'url'  => 'do=cache_burst&_appid=' . $appid . '&flag=' . $next . '&total=' . $total . '&CSRF_TOKEN=' . Security::$CSRF_TOKEN,
                'msg'  => '<hr /><div class="alert alert-info">准备进行' . $map[$next][1] . '</div>',
                'time' => '3',
            );
            if ($flag === 'common') {
                $total = $num = 1;
                $config['step']['msg'] = array('操作', '个');
                $config['next']['msg'] = '';
            }
        }
        Script::loop($total, $num, $config);
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
            $where = array('dir' => $dir);
            $id && $where['id'] = array('!=', $id);
            $hasDir = NodeModel::where($where)->count();
            if ($hasDir) {
                $count = NodeModel::where('dir', 'like', "{$dir}-%")->count();
                $dir = $dir . '-' . ($count + 1);
            }
        }
        return $dir;
    }


    public function getOrderBy()
    {
        return self::setOrderBy(array(
            'id'     => "CID",
            'sortnum' => "排序值",
            'dir'     => "目录值",
            'count'   => "记录数",
        ));
    }
    public function merge($tonid, $id)
    {
        $this->CONTENT_MODEL->update(
            array($this->primary => $tonid),
            array($this->primary => $id)
        );
        Tag::move($id, $tonid);
        DB::table("prop")->update(
            array('cid' => $tonid),
            array('cid' => $id)
        );
    }

    public function update_app_count($id)
    {
        $cc = $this->CONTENT_MODEL->where(array($this->primary => $id))
            ->count();
        NodeModel::update(array('count' => $cc), $id);
    }
    public static function widget_count($where = null)
    {
        $total = NodeModel::where($where)->count();
        $widget[] = array($total, '全部');
        foreach (Node::$statusMap as $status => $text) {
            $count = NodeModel::where('status', $status)->where($where)->count();
            $widget[] = array($count, $text);
        }
        return $widget;
    }

    public static function config($domain = null)
    {
        if (empty($domain)) {
            $result = NodeModel::field('id,domain')
                ->where(array(
                    'domain' => array('<>', ''),
                    'status' => '1'
                ))->select();
            foreach ((array) $result as $C) {
                $domain[$C['domain']] = $C['id'];
            }
        }

        Config::set(array(
            'domain' => $domain
        ), 'node', self::$appId, false);

        Config::cache();
    }
}
