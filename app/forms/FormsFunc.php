<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FormsFunc extends AppsFuncCommon
{
    public static function boot($vars, $func = 'list')
    {
        if (isset($vars['formid']) || isset($vars['fid'])) {
            $formid = $vars['formid'];
            isset($vars['fid']) && $formid = $vars['fid'];
            $form = Forms::get($formid);
        } elseif (isset($vars['name'])) {
            $form = Forms::get($vars['name'], 'app');
        } elseif (isset($vars['app'])) {
            $form = Forms::get($vars['app'], 'app');
        } elseif (isset($vars['form'])) {
            is_array($vars['form']) && $form = $vars['form'];
        }
        if (empty($form) || empty($form['status'])) {
            self::msg(['func_param', ['iCMS:forms:' . $func, '[formid,form,name]']]);
        }
        FormsApp::initialize($form, false);
        $forms = FormsApp::values($form);
        return $forms;
    }

    public static function get($vars)
    {
        $form = self::boot($vars, 'create');

        if (empty($form['config']['enable'])) {
            self::msg('该表单设置不允许用户提交.');
            return false;
        }

        empty($vars['glue']) && $vars['glue'] = '';

        $vars['prefix'] && Former::$prefix = $vars['prefix'];
        Former::usercp($form, [], $vars['option']);

        $form['nonce'] = random(16);
        $form['timestamp'] = time();
        $form['sign'] = Forms::sign($form['id'], $form['nonce'], $form['timestamp']);
        $form['id'] = encrypt($form['id']);
        is_null($vars['layout']) && $vars['layout'] = true;
        $vars['layout'] && $form['layout'] = implode($vars['glue'], Former::render(false));
        View::assign('form', $form);

        $form['head'] = View::fetch('iCMS://forms/head.htm');
        $form['hidden'] = View::fetch('iCMS://forms/hidden.htm');
        $form['html'] = View::fetch('iCMS://forms/create.htm');
        return $form;
    }

    public static function encode($vars)
    {
        return encrypt($vars['value']);
    }

    public static function hidden($vars)
    {
        $vars['layout'] = false;
        $form = self::get($vars);
        echo $form ? $form['hidden'] : '';
    }

    public static function create($vars)
    {
        $form = self::get($vars);
        if (empty($form)) {
            self::msg('表单不存在或已被禁用.');
            return false;
        }
        if ($vars['assign']) {
            View::assign($vars['assign'], $form['html']);
        } else {
            echo $form['html'];
        }
    }
    public static $fields = null;

    public static function datas($vars)
    {
        $form = self::boot($vars);
        FormsData::init($form);

        if ($form['fields']) {
            self::$fields = Former::fields($form['fields']);
            View::assign('forms_fields', self::$fields);
        }

        $whereNot = [];
        $where = [];
        $resource = [];
        $model = ContentModel::field('id');
        // $status    = isset($vars['status']) ? (int) $vars['status'] : 1;
        // $where     = [['status', $status]];

        self::inited($vars, $model, $where, $whereNot);
        // self::setApp(Forms::APPID, Forms::APP);

        $sfield = $vars['sfield'];
        if ($keywords = $vars['keywords']) {
            $search = [];
            if (empty($sfield)) {
                foreach ((array) self::$fields as $fi => $field) {
                    $field['field'] == 'VARCHAR' && $search[] = $field['id'];
                }
                $search && $where['CONCAT(`' . implode('`,`', $search) . '`)'] = ['REGEXP', $keywords];
            } else {
                empty($pattern) && $pattern = 'REGEXP';
                $where[$sfield] = [$pattern, $keywords];
            }
        } else {
            $vars['pattern'] && $where[$sfield] = [$vars['pattern'], $keywords];
        }
        self::where();
        self::orderby([]);
        return self::getResource(__METHOD__, function ($idsArray = null) {
            $vars = self::$vars;
            if ($idsArray) {
                $resource = ContentModel::field('*')
                    ->where($idsArray)
                    ->orderBy('id', $idsArray)
                    ->select();
                if ($resource) {
                    if ($vars['data']) {
                        $data = [];
                        if (ContentDataModel::$unionKey) {
                            $where[ContentDataModel::$unionKey] = $idsArray;
                            $data = ContentDataModel::where($where)->select();
                            $data && $data = array_column($data, null, ContentDataModel::$unionKey);
                        }
                    }
                    foreach ($resource as $key => $value) {
                        foreach (self::$fields as $fi => $field) {
                            $id = $value['id'];
                            if ($data[$id] && is_array($data[$id])) {
                                $value = array_merge($value, $data[$id]);
                            }
                            FormerApp::vars($field, $fi, $value, $vars);
                            $resource[$key] = $value;
                        }
                    }
                }
            }
            return $resource;
        });
    }

    public static function lists($vars)
    {
        $whereNot = [];
        $resource = [];
        $model = FormsModel::field('id');
        $status = isset($vars['status']) ? (int) $vars['status'] : 1;
        $where = [['status', $status]];

        isset($vars['type']) && $where[] = ['type', $vars['type']];
        isset($vars['pic']) && $where[] = ['pic', '<>', ''];
        isset($vars['nopic']) && $where[] = ['pic', ''];
        isset($vars['startdate']) && $where[] = ['addtime', '>=', str2time($vars['startdate'] . (strpos($vars['startdate'], ' ') !== false ? '' : ' 00:00:00'))];
        isset($vars['enddate']) && $where[] = ['addtime', '<=', str2time($vars['enddate'] . (strpos($vars['enddate'], ' ') !== false ? '' : ' 00:00:00'))];

        self::inited($vars, $model, $where, $whereNot);
        self::where();
        self::orderby([]);
        return self::getResource(__METHOD__, function ($idsArray = null) {
            $vars = self::$vars;
            if ($idsArray) {
                $resource = FormsModel::field('*')
                    ->where($idsArray)
                    ->orderBy('id', $idsArray)
                    ->select();
                if ($resource) {
                    foreach ($resource as $key => $value) {
                        $value['pic'] = FilesPic::getArray($value['pic']);
                        // $value['url'] = Route::routing('forms/{id}', [$value['id']]);
                        $resource[$key] = $value;
                    }
                }
            }
            return $resource;
        });
    }
}
