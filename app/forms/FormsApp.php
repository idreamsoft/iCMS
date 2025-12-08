<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FormsApp extends AppsBase
{
    public function api_index()
    {
        return $this->do_index();
    }

    public function do_index()
    {
        $id = Request::sget('id');
        if ($id) {
            $this->display($id);
        } else {
            $name = Request::sget('name');
            $this->display($name, 'app');
        }
    }

    public function done_save()
    {
        $post = Request::post();
        if (empty($post['formid']) || empty($post['nonce']) || empty($post['timestamp']) || empty($post['sign'])) {
            self::alert('FORMS:params_error');
        }
        $formid = decrypt($post['formid']);
        $nonce = $post['nonce'];
        $timestamp = $post['timestamp'];
        $sign = $post['sign'];
        $_sign = Forms::sign($formid, $nonce, $timestamp);

        if ($formid && $sign == $_sign && time() - $timestamp < 3600) {
            $forms = Forms::get($formid);
            if (empty($forms) || empty($forms['status'])) {
                self::alert('FORMS:not_found_fid');
            }
            if (empty($forms['config']['enable'])) {
                self::alert('FORMS:enable');
            }
            FormsData::init($forms);
            FormerApp::save(FormsData::$DATA, null, $update);
            return $forms['config']['success'] ?: 'FORMS:success';
        } else {
            self::alert('FORMS:error');
        }
    }

    public function display($fid, $field = 'id', $tpl = true)
    {
        $forms = Forms::get($fid, $field);

        if (empty($forms) || empty($forms['status'])) {
            AppsBase::alert(['forms:not_found_fid', [$fid]], 10001);
        }
        self::initialize($forms, $tpl);
        $forms = $this->values($forms);
        AppsCommon::init($forms)
                    ->link()
                    ->user()
                    ->comment()
                    ->hits()
                    ->params();
        return AppsBase::render($forms, $tpl, 'forms');
    }

    public static function values($value, $flag = false)
    {
        $flag && $value = Apps::item($value);

        $value['fieldArray'] = Former::fields($value['fields']);
        $value['action'] = Route::create('forms');
        $value['result'] = Route::create('forms/result/{id}', [$value['id']]);
        $value['link'] = '<a href="' . $value['url'] . '" class="forms" target="_blank">' . $value['title'] . '</a>';
        $value['pic'] = FilesPic::getArray($value['pic']);
        $value['layout_id'] = 'former_' . $value['id'];

        return $value;
    }
}
