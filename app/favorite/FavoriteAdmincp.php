<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FavoriteAdmincp extends AdmincpCommon
{
    public function __construct()
    {
        parent::__construct();
    }

    public function do_batch()
    {
        $stype = AdmincpBatch::$config['stype'];
        $actions = array(
            'dels' => function ($idArray, $ids, $batch) {
                foreach ($idArray as $id) {
                    $this->do_delete($id);
                }
                // self::success('收藏夹全部删除完成');
            }
        );
        return AdmincpBatch::run($actions, "收藏夹");
    }

    public static function widget_count()
    {
        $total = FavoriteModel::count();
        $widget[] = array($total, '全部');
        return $widget;
    }
}
