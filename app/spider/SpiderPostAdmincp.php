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
 * 采集发布
 */
class SpiderPostAdmincp extends SpiderBase
{
    use AdmincpCommonTrait;

    public function index()
    {
        $keywords = Request::get('keywords');
        $keywords && $where['CONCAT(name,app,post)'] = ['REGEXP', $keywords];
        $cid = Request::get('cid');
        $cid && $where['cid'] = $cid;

        return self::tableData([
            'where' => $where
        ], function ($rows) {
            $rows = array_map(function ($row) {
                return $row;
            }, $rows);
            return $rows;
        });
    }

    /**
     * 添加发布模块
     * @return void
     */
    public function do_add()
    {
        $this->id && $rs = SpiderPostModel::get($this->id);
        include self::view();
    }

    /**
     * 保存发布模块
     * @return void
     */
    // public function saving(&$data = [])
    // {
    // }
    public function done_apps()
    {
        
        $methods = AppsHooks::grep('done_add','*Admincp');
        // var_dump($methods);
        $list = [];
        if ($methods) {
            foreach ($methods as $key => $call) {
                if($call[1]=='done_add'){
                    // $app = substr($call[0],0,-7);
                    $list[] = iJson::listItem(['call'=>$call[1]], $call[0]);
                }
            }
        }
        return iJson::listData($list);
    }
}
