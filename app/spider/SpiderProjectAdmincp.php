<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 * @title
 */
defined('iPHP') or exit('What are you doing?');
/**
 * 采集方案
 */
class SpiderProjectAdmincp extends SpiderBase
{
    use AdmincpCommonTrait;

    /**
     * 测试采集方案
     * @return void
     */
    public function do_test()
    {
        Spider::$isTest = true;
        SpiderData::crawl();
    }

    public function batching()
    {
        return [
            'poid' => function ($ids, $data, $batch) {
                $poid = (int) $data['poid'];
                $poid or self::alert('请选择目标规则');
                SpiderProjectModel::update(compact('poid'), $ids);
            },
			'rid' => function ($ids, $data, $batch) {
                $rid = (int) $data['rid'];
                $rid or self::alert('请选择目标规则');
                SpiderProjectModel::update(compact('rid'), $ids);
            },
        ];
    }

    public function index()
    {
        $starttime = Request::get('starttime');
        $starttime && $where[] = ['lastupdate', '>=', str2time($starttime . (strpos($starttime, ' ') !== false ? '' : ' 00:00:00'))];

        $endtime = Request::get('endtime');
        $endtime && $where[] = ['lastupdate', '<=', str2time($endtime . (strpos($endtime, ' ') !== false ? '' : ' 00:00:00'))];

        Node::makeWhere($where, Spider::$cid);
        $rid = Request::get('rid');
        $rid && $where['rid'] = $rid;

        $auto = Request::get('auto');
        $auto && $where['auto'] = $auto;

        $poid = Request::get('poid');
        $poid && $where['poid'] = $poid;

        $keywords = Request::get('keywords');
        $keywords && $where['CONCAT(name)'] = ['REGEXP', $keywords];

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
     * 添加采集方案
     */
    public function do_add()
    {
        $rs = [];
        $this->id && $rs = SpiderProject::get($this->id);
        $cid = empty($rs['cid']) ? Spider::$cid : $rs['cid'];
        include self::view();
    }

    /**
     * 保存采集方案
     */
    public function saving(&$data = [])
    {   
        $data['lastupdate'] = $data['lastupdate'] ? str2time($data['lastupdate']) : '0';
        empty($data['name']) && self::alert('名称不能为空！');
        empty($data['cid']) && self::alert('请选择绑定的栏目');
        empty($data['rid']) && self::alert('请选择采集规则');
    }

    /**
     * 导入采集方案
     * @return void
     */
    public function do_import()
    {
        Files::$check_data = false;
        FilesCloud::$enable = false;
        FilesClient::$config['allow_ext'] = 'txt';
        $file = FilesClient::upload('upfile', 'spider');
        $path = FilesClient::getRoot($file['path']);
        if ($path) {
            $data = file_get_contents($path);
            @unlink($path);
            if ($data) {
                $data = base64_decode($data);
                $data = json_decode($data, true);
                if (is_array($data)) {
                    foreach ($data as $key => $value) {
                        SpiderProjectModel::create($value, true);
                    }
                    return;
                }
            }
        }
        self::alert('导入方案出现错误');
    }

    /**
     * 导出采集方案
     * @return void
     */
    public function do_export()
    {
        $data = SpiderProjectModel::withoutField('id')->get(['rid' => Spider::$rid]);
        if (empty($data)) {
            self::alert('规则暂无方案');
        }
        $data = base64_encode(json_encode($data));
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename=spider.rule.' . Spider::$rid . '.project.txt');
        exit($data);
    }

    public function do_update_lastupdate()
    {
        $id = Request::get('id');
        SpiderProjectModel::update(
            ['lastupdate' => time()],
            $id
        );
    }
}
