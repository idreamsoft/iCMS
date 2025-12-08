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
 * 采集规则
 */
class SpiderRuleAdmincp extends SpiderBase
{
    use AdmincpCommonTrait;

    /**
     * 测试采集规则
     */
    public function do_test()
    {
        Spider::$isTest = true;
        SpiderList::crawl('TEST');
    }

    public function do_index()
    {
        if (Request::isAjax()) {
            return $this->index();
        }
        include self::view();
    }

    public function index()
    {
        $keywords = Request::get('keywords');
        $keywords && $where['CONCAT(name,rule)'] = ['REGEXP', $keywords];

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
     * 添加采集规则
     */
    public function do_add()
    {
        $rs = [];
        $this->id && $rs = SpiderRule::get($this->id);
        $rs['rule'] && $rule = $rs['rule'];
        if (empty($rule['data'])) {
            $rule['data'] = [
                [
                    'name'  => 'title',
                    'empty' => true,
                    'dom'   => true,
                    // 'data@source' => 'sdfsdf',
                    'process' => [
                        ['helper' => 'trim'],
                        ['helper' => 'cleanhtml']
                    ]
                ],
                [
                    'name'    => 'body', 'empty' => true, 'page' => true, 'multi' => true,
                    'process' => [
                        ['helper' => 'format'],
                        ['helper' => 'trim']
                    ]
                ],
            ];
        } else {
            // //兼容旧版
            // if(is_array($rule['data']))foreach ($rule['data'] as $key => $value) {
            // 	if(isset($value['process'])){
            // 		continue;
            // 	}
            // 	$rule['data'][$key]['process'] = self::old7014($value);
            // 	unset($rule['data'][$key]['cleanbefor'],$rule['data'][$key]['helper'],$rule['data'][$key]['cleanafter']);
            // }
            $rule['fs']['encoding'] && $rule['http']['ENCODING'] = $rule['fs']['encoding'];
            $rule['fs']['referer'] && $rule['http']['REFERER'] = $rule['fs']['referer'];
        }
        $rule['data'] = array_values($rule['data']);
        $rule['data'] = array_map(function ($value) {
            $value['data_source'] = $value['data@source'];
            $value['has_data_source'] = $value['data_source'] ? true : false;
            if (is_array($value['process'])) {
                $value['process'] = array_values($value['process']);
                $value['process'] = array_map(function ($item) {
                    $item += (array)SpiderHelper::getTitles($item['helper']);
                    return $item;
                }, $value['process']);
            }
            return $value;
        }, $rule['data']);

        $rule['sort'] or $rule['sort'] = 1;
        $rule['mode'] or $rule['mode'] = 1;
        $rule['page_no_start'] or $rule['page_no_start'] = 1;
        $rule['page_no_end'] or $rule['page_no_end'] = 5;
        $rule['page_no_step'] or $rule['page_no_step'] = 1;

        $helperArr = SpiderHelper::getArray();
        include self::view();
    }

    /**
     * 保存采集规则
     * @return void
     */
    public function save(&$data = [])
    {
        empty($data['name']) && self::alert('规则名称不能为空！');
        //empty($rule['list_area_rule']) 	&& self::alert('列表区域规则不能为空！');
        if ($data['rule']['mode'] != '2') {
            empty($data['rule']['list_url_rule']) && self::alert('列表链接规则不能为空！');
        }

        if ($data['id']) {
            SpiderRuleModel::update($data, $data['id']);
            return true;
        } else {
            $data['id'] = SpiderRuleModel::create($data, true);
            return Admincp::url(Admincp::$APP_NAME . '/edit/id/'.$data['id']);
        }
    }

    /**
     * 导出采集规则
     * @return void
     */
    public function do_export()
    {
        $row = SpiderRuleModel::get($this->id);
        unset($row['id']);
        $data = base64_encode(json_encode($row));
        Header('Content-type: application/octet-stream');
        Header('Content-Disposition: attachment; filename=spider.rule.' . $row['name'] . '.txt');
        exit($data);
    }

    /**
     * 导入采集规则
     */
    public function done_import()
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
                if ($data['rule']) {
                    SpiderRuleModel::create($data, true);
                    return;
                }
            }
        }
        self::alert('导入规则出现错误');
    }
}
