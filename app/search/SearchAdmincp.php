<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class SearchAdmincp extends AdmincpCommon
{
    public function __construct()
    {
        parent::__construct();
        $this->id    = (int) $_GET['id'];
    }
    public function save_config()
    {
        Config::$data = Request::post('config');
        Config::$data['disable'] = array_unique(explode("\n", Config::$data['disable']));
        Config::vsave('search');
        $this->autoCache();
        // self::success('保存成功');
    }
    /**
     * [autoCache 在更新所有缓存时，将会自动执行]
     */
    public static function autoCache($config = null)
    {
        $config === null && $config  = Config::vget('search');
        $config && Cache::set('search/disable', $config['disable'], 0);
    }

    public function do_manage()
    {
        $config = Config::vapp('search', true);

        $keywords = Request::get('keywords');
        $keywords && $where['search'] = array('like', "%{$keywords}%");

        $orderby = self::setOrderBy(array(
            'id'    => "ID",
            'times' => "搜索次数",
        ));

        $result = SearchLogModel::where($where)
            ->orderBy($orderby)
            ->paging();
        include self::view("search.manage");
    }
    public function do_delete($id = null)
    {
        $id === null && $id = $this->id;
        $id or self::alert('请选择要删除的记录');
        SearchLogModel::delete($id);
        // $dialog && self::success('记录删除完成');
    }
    public function do_batch()
    {
        $stype = AdmincpBatch::$config['stype'];
        $actions = array(
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    $this->do_delete($id);
                }
                // self::success('记录删除完成');
            },
        );
        return AdmincpBatch::run($actions, "标签");
    }
}
