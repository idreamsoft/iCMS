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
    use AdmincpCommonTrait;
    public function done_config()
    {
        $data = Request::post('config');

        Config::vsave('links', [
            'whitelist' => array_unique(explode("\n", $data['whitelist'])),
            'blacklist' => array_unique(explode("\n", $data['blacklist']))
        ]);

        Config::save(self::$APPID, [
            'base' =>  $data['base'],
            'template' => $data['template']
        ]);
        $this->autoCache();
    }

    public function save(&$data = [])
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
    }

    public function index()
    {

        $keywords = Request::get('keywords');
        $keywords && $where['CONCAT(name,url)'] = array('REGEXP', $keywords);
        $cid = Request::get('cid');
        $cid && $where['cid'] = $cid;
        return  self::tableData(['where' => $where], function ($rows) {
            // $rows = array_map(function ($row) {
            //     return $row;
            // }, $rows);
            return $rows;
        });
    }


    public static function widget_count()
    {
        $total = LinksModel::count();
        $widget[] = array($total, '全部');
        return $widget;
    }
    /**
     * autoCache 在更新所有缓存时，将会自动执行
     */
    public static function autoCache($config = null)
    {
        $config === null && $config  = Config::get('links');
        Cache::set('links/whitelist', $config['whitelist'], 0);
        Cache::set('links/blacklist', $config['blacklist'], 0);
    }
}
