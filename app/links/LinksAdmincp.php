<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class LinksAdmincp extends AdmincpCommon
{
    public function __construct()
    {
        parent::__construct();
        $this->id = (int) $_GET['id'];
    }
    public function do_config()
    {
        $GLOBALS['CONFIG_APPID'] = self::$appId;
        Config::vapp('links');
    }
    public function save_config()
    {
        $data = Request::post('config');
        Config::$data = [
            'whitelist' => array_unique(explode("\n", $data['whitelist'])),
            'blacklist' => array_unique(explode("\n", $data['blacklist']))
        ];
        Config::vsave('links');

        Config::$data = [
            'base' =>  $data['base'],
            'template' => $data['template']
        ];
        Config::save(self::$appId);
        $this->autoCache();
    }
    public function do_add()
    {
        $this->id && $rs = LinksModel::get($this->id);
        self::added($this, __METHOD__, $rs);
        include self::view("links.add");
    }
    public function save()
    {
        $data = LinksModel::postData();
        $data['name'] or self::alert('网站不能为空');
        $data['url'] or self::alert('链接不能为空');

        $where['name'] = $data['name'];
        $data['id'] && $where['id'] = array('<>', $data['id']);
        LinksModel::field('id')->where($where)->value() && self::alert('该网站已经存在');
        if (empty($data['id'])) {
            LinksModel::create($data, true);
        } else {
            LinksModel::update($data, $data['id']);
        }

        iPHP::callback(array("FormerApp", "save"), array(self::$appId, $data['id']));
        // self::success('保存成功');
    }

    public function do_manage()
    {

        $keyword = Request::get('keyword');
        $keyword && $where['CONCAT(name,url)'] = array('REGEXP', $keyword);
        $cid = Request::get('cid');
        $cid && $where['cid'] = $cid;

        $orderby = self::setOrderBy();
        $result = LinksModel::where($where)
            ->orderBy($orderby)
            ->paging();

        include self::view("links.manage");
    }
    public function do_delete($id = null)
    {
        $id === null && $id = $this->id;
        $id or self::alert('请选择要删除的网站');
        LinksModel::delete($id);
        // $dialog && self::success('网站删除完成');
    }
    public function do_batch()
    {
        $stype = AdmincpBatch::$config['stype'];
        $actions = array(
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    $this->do_delete($id);
                }
                // self::success('网站删除完成');
            }
        );
        return AdmincpBatch::run($actions, "网站");
    }
    public static function widget_count()
    {
        $total = LinksModel::count();
        $widget[] = array($total, '全部');
        return $widget;
    }
    /**
     * [autoCache 在更新所有缓存时，将会自动执行]
     */
    public static function autoCache($config = null)
    {
        $config === null && $config  = Config::vget('links');
        Cache::set('links/whitelist', $config['whitelist'], 0);
        Cache::set('links/blacklist', $config['blacklist'], 0);
    }
}
