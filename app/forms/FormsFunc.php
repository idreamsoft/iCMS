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
    public static function inited($vars, $func = 'list')
    {
        if (isset($vars['formid']) || isset($vars['fid'])) {
            $formid = $vars['formid'];
            isset($vars['fid']) && $formid = $vars['fid'];
            $form   = Forms::get($formid);
        } else if (isset($vars['form'])) {
            is_array($vars['form']) && $form = $vars['form'];
        }
        if (empty($form) || empty($form['status'])) {
            Script::warning('iCMS&#x3a;forms&#x3a;' . $func . ' 标签出错! 缺少参数"app"或"app"值为空.');
        }
        return $form;
    }
    public static function create($vars)
    {
        $form = self::inited($vars, 'create');

        if (empty($form['config']['enable'])) {
            Script::warning('该表单设置不允许用户提交.');
            return false;
        }

        // isset($vars['main']) && Former::$template['main'] = $vars['main'];
        // isset($vars['label']) && Former::$template['label'] = $vars['label'];
        // foreach ($vars as $key => $value) {
        //     if (stripos($key, 'class_') !== false) {
        //         $key = str_replace('class_', '', $key);
        //         Former::$template['class'][$key] = $value;
        //     }
        // }

        isset($vars['prefix']) && Former::$prefix = $vars['prefix'] ?: 'iDATA';
        Former::$APP = $form;
        Former::$VALUES = array(
            'userid'   => User::$id,
            'username' => User::$account,
            'nickname' => User::$nickname
        );
        Former::$GATEWAY = 0;
        Former::$config['option'] = $vars['option'];
        Former::create($form);

        $vendor = Vendor::run('Token');
        list($token, $timestamp, $nonce) = $vendor->get();
        empty($form['token']) && $form['token']  = $token;
        empty($form['signature']) && $form['signature'] = auth_encode($form['id'] . '#' . $form['token'] . '#' . $timestamp . '#' . $nonce);
        $vendor->prefix = 'form_' . $form['id'] . '_';
        $vendor->signature($form['token'], $form['signature']);

        $form['layout'] = implode($vars['glue'], Former::render(false));
        View::assign('form', $form);
        $html = View::fetch('iCMS://forms/create.htm');
        if ($vars['assign']) {
            View::assign($vars['assign'], $html);
        } else {
            echo $html;
        }
    }
    public static $fields = null;
    public static function datas($vars)
    {
        $form = self::inited($vars);
        Forms::init($form);

        if ($form['fields']) {
            self::$fields = Former::fields($form['fields']);
            View::assign("forms_fields",  self::$fields);
        }

        $whereNot  = array();
        $where     = array();
        $resource  = array();
        $model     = ContentModel::field('id');
        // $status    = isset($vars['status']) ? (int) $vars['status'] : 1;
        // $where     = [['status', $status]];

        self::init($vars, $model, $where, $whereNot);
        // self::setApp(Forms::APPID, Forms::APP);

        $sfield = $vars['sfield'];
        if ($keywords = $vars['keywords']) {
            $search = array();
            if (empty($sfield)) {
                foreach ((array) self::$fields as $fi => $field) {
                    $field['field'] == 'VARCHAR' && $search[] = $field['id'];
                }
                $search && $where["CONCAT(`" . implode('`,`', $search) . "`)"] = array('REGEXP', $keywords);
            } else {
                empty($pattern) && $pattern = 'REGEXP';
                $where[$sfield] = array($pattern, $keywords);
            }
        } else {
            $vars['pattern'] && $where[$sfield] = array($vars['pattern'], $keywords);
        }
        self::where();
        self::orderby([]);
        return self::getResource(__METHOD__, function ($vars, $idsArray = null) {
            if ($idsArray) {
                $resource = ContentModel::field('*')
                    ->where($idsArray)
                    ->orderBy('id', $idsArray)
                    ->select();
                if ($resource) {
                    if ($vars['data']) {
                        $data = array();
                        if (ContentDataModel::$unionKey) {
                            $where[ContentDataModel::$unionKey] = $idsArray;
                            $data = ContentDataModel::where($where)->select();
                            $data && $data = array_column($data, null, ContentDataModel::$unionKey);
                        }
                    }
                    foreach ($resource as $key => $value) {
                        foreach (self::$fields as $fi => $field) {
                            $id = $value[Content::$primaryKey];
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
        $whereNot  = array();
        $resource  = array();
        $model     = FormsModel::field('id');
        $status    = isset($vars['status']) ? (int) $vars['status'] : 1;
        $where     = [['status', $status]];

        isset($vars['type']) && $where[] = ['type', $vars['type']];
        isset($vars['pic']) && $where[] = ['pic', '<>', ''];
        isset($vars['nopic'])   && $where[] = ['pic', ''];
        isset($vars['startdate']) && $where[] = array('addtime', '>=', str2time($vars['startdate'] . (strpos($vars['startdate'], ' ') !== false ? '' : " 00:00:00")));
        isset($vars['enddate'])   && $where[] = array('addtime', '<=', str2time($vars['enddate'] . (strpos($vars['enddate'], ' ') !== false ? '' : " 00:00:00")));

        self::init($vars, $model, $where, $whereNot);
        self::setApp(Forms::APPID, Forms::APP);
        self::where();
        self::orderby([]);
        return self::getResource(__METHOD__, function ($vars, $idsArray = null) {
            if ($idsArray) {
                $resource = FormsModel::field('*')
                    ->where($idsArray)
                    ->orderBy('id', $idsArray)
                    ->select();
                if ($resource) foreach ($resource as $key => $value) {
                    $value['pic'] && $value['pic']  = FilesClient::getUrl($value['pic']);
                    $value['url'] = Route::routing('forms/{id}', [$value['id']]);
                    $resource[$key] = $value;
                }
            }
            return $resource;
        });
    }
}
