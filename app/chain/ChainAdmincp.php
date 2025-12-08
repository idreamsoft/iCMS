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
    use AdmincpCommonTrait;
    public function do_add()
    {
        if ($this->id) {
            $rs = ChainModel::get($this->id);
        } else {
            $rs['keyword'] = Request::get('keyword');
            $rs['replace'] = Request::get('replace');
        }
        Request::get('url') && $rs['replace'] = Chain::getLink($rs['keyword'], Request::get('url'));

        include self::view();
    }
    public function save(&$data = [])
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
    }

    public function index()
    {
        $keywords = Request::get('keywords');
        $keywords && $where['keyword'] = array('REGEXP', $keywords);
        return  self::tableData(['where' => $where], function ($rows) {
            // $rows = array_map(function ($row) {
            //     return $row;
            // }, $rows);
            return $rows;
        });
    }

    public function deleted($ids = null)
    {
        $this->autoCache();
    }


    /**
     * autoCache 在更新所有缓存时，将会自动执行
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
