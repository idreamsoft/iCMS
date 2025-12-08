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
    public static $orderBy = [
        'id'    => 'ID',
        'times' => '搜索次数',
    ];
    public static $MODEL = 'SearchLogModel';

    /**
     * 管理{title}
     *
     * @return void
     */
    public function do_index()
    {
        if ($statusMap = Prop::getMap(self::$MODEL, 'status')) {
            self::setContext([
                'tablist'   => 'params',
                'statusMap' => $statusMap,
                'params'    => [
                    'status' => Request::get('status'),
                ]
            ]);
        }

        if (Request::isAjax()) {
            return $this->index();
        }

        include self::view('index');
    }

    public function done_config()
    {
        $data = Request::post('config');
        $data['disable'] = array_unique(explode("\n", $data['disable']));
        Config::vsave('search', $data);
        $this->autoCache();
    }

    /**
     * autoCache 在更新所有缓存时，将会自动执行
     */
    public static function autoCache($config = null)
    {
        $config === null && $config = Config::vget('search');
        $config && Cache::set('search/disable', $config['disable'], 0);
    }

    public function index()
    {
        $keywords = Request::get('keywords');
        $keywords && $where['search'] = ['like', "%{$keywords}%"];
        return  self::tableData(['where' => $where]);
    }

    /**
     * 删除{title}
     */
    public function done_delete($ids = null)
    {
        $model = self::$MODEL; //兼容php5
        $ids === null && $ids = $this->ids;
        $ids or self::alert('Please select items to delete');
        is_array($ids) or $ids = [$ids];
        if ($ids) {
            $model::delete($ids);
        }
        return ['action' => 'delete', 'id' => $ids];
    }

    /**
     * 批处理{title}
     */
    public function done_batch()
    {
        $model = self::$MODEL; //兼容php5
        $actions = [
            'dels' => [$this, 'done_delete'],
            'prop' => function ($ids, $data, $batch) use ($model) {
                $pid = $data['pid'];
                $model::update(compact('pid'), $ids);
            },
        ];
        $result = AdmincpBatch::run($actions);
        return $result;
    }
}
