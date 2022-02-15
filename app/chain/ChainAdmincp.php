<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ChainAdmincp extends AdmincpCommon
{
    public function __construct()
    {
        parent::__construct();
        $this->id = (int) $_GET['id'];
    }

    public function do_add()
    {
        if ($this->id) {
            $rs = ChainModel::get($this->id);
        } else {
            $rs['keyword'] = Request::get('keyword');
            $rs['replace'] = Request::get('replace');
        }
        $_GET['url'] && $rs['replace'] = Chain::getLink($rs['keyword'], Request::get('url'));

        include self::view("add");
    }
    public function save()
    {
        $data = ChainModel::postData();
        $data['keyword'] or self::alert('关键词不能为空');
        $data['replace'] or self::alert('替换词不能为空');
        $data['replace'] = str_replace(array('"', "'"), array('&#34;', '&#39;'), $data['replace']);

        $where['keyword'] = $data['keyword'];
        $data['id'] && $where['id'] = array('<>', $data['id']);
        ChainModel::field('id')->where($where)->value() && self::alert('该关键词已经存在');
        if (empty($data['id'])) {
            ChainModel::create($data);
        } else {
            ChainModel::update($data, $data['id']);
        }
        $this->autoCache();
        // self::success('保存成功');
    }
    /**
     * 标签管理，设置为内链回调
     */
    public static function batchTag($idArray, $ids, $batch)
    {
        $result = TagModel::select($idArray);
        $nodeArray  = Node::many($result, 'cid');
        $tnodeArray = Node::many($result, 'tcid', Tag::APPID);
        foreach ($result as $tag) {
            $C = (array) $nodeArray[$tag['cid']];
            $TC = (array) $tnodeArray[$tag['tcid']];
            $url = Route::get('tag', array($tag, $C, $TC))->href;

            $data = ['keyword' => $tag['name']];
            $check = ChainModel::field('id')->where($data)->value();
            if (!$check) {
                $data['replace'] = $url;
                $flag = ChainModel::create($data, true);
            }
        }
        // return $flag;
    }

    public function do_manage()
    {
        $keyword = Request::get('keyword');
        $keyword && $where['keyword'] = array('REGEXP', $keyword);
        $orderby = self::setOrderBy();
        $result = ChainModel::where($where)
            ->orderBy($orderby)
            ->paging();

        include self::view("manage");
    }
    public function do_delete($id = null)
    {
        $id === null && $id = $this->id;
        $id or self::alert('请选择要删除的关键词');
        ChainModel::delete($id);
        $this->autoCache();
        // $dialog && self::success('关键词已经删除');
    }
    public function do_batch()
    {
        $stype = AdmincpBatch::$config['stype'];
        $actions = array(
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    $this->do_delete($id);
                }
                // return true;
                // self::success('关键词全部删除完成');
            }
        );
        return AdmincpBatch::run($actions, "网站");
    }
    /**
     * [更新缓存]
     *
     * @return  [type]  [return description]
     */
    public function do_cache()
    {
        $this->autoCache();
    }
    /**
     * [autoCache 在更新所有缓存时，将会自动执行]
     */
    public static function autoCache()
    {
        Chain::cache();
    }

    public static function widget_count()
    {
        $total = ChainModel::count();
        $widget[] = array($total, '全部');
        return $widget;
    }
}
