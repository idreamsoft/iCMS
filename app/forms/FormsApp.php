<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FormsApp
{
    public $methods = array(iPHP_APP, 'save');
    public function do_iCMS()
    {
        $fid = (int) $_GET['id'];
        $this->display($fid);
    }
    public function API_iCMS()
    {
        $this->do_iCMS();
    }
    public function ACTION_save()
    {
        $fid       = (int) $_POST['fid'];
        $signature = $_POST['signature'];

        $vendor = Vendor::run('Token');
        $vendor->prefix = 'form_' . $fid . '_';

        list($_fid, $token, $timestamp, $nonce) = explode("#", auth_decode($signature));
        $_signature = $vendor->signature($token);
        if ($_fid == $fid && $_signature == $signature) {
            $active = true;
            $forms  = Forms::get($fid);
            if (empty($forms) || empty($forms['status'])) {
                $array = Script::code(0, array('forms:not_found_fid', $fid), null, 'array');
                $active = false;
            }
            if (empty($forms['config']['enable'])) {
                $array = Script::code(0, 'forms:!enable', null, 'array');
                $active = false;
            }
            if ($active) {
                $formsAdmincp = new formsAdmincp();
                $formsAdmincp->ACTION_save_data(false);
                $array = Script::code(1, $forms['config']['success'], null, 'array');
                Former::$error && $array = Former::$error;
            }
            $vendor->signature($token, 'DELETE');
        } else {
            $array = Script::code(0, 'forms:error', null, 'array');
        }

        if (Request::isAjax()) {
            echo json_encode($array);
        } else {
            if ($array['code']) {
                Script::success($array['msg']);
            } else {
                Script::alert($array['msg']);
            }
        }
    }

    public function display($fid, $tpl = true)
    {
        $forms = Forms::get($fid);

        if (empty($forms) || empty($forms['status'])) {
            AppsApp::throwError(['forms:not_found_fid', [$fid]], 10001);
        }
        $forms = $this->value($forms);

        return AppsApp::render($forms, $tpl, 'forms');
    }
    public static function value($value, $flag = false)
    {
        $flag && $value = Apps::item($value);

        $value['fieldArray']   = Former::fields($value['fields']);
        $value['action']       = Route::routing('forms');
        $value['url']          = Route::routing('forms/{id}', [$value['id']]);
        $value['iurl']         = Adapter::urls(array('href' => $value['url']));
        $value['iurl']['href'] = $value['url'];
        $value['result']       = Route::routing('forms/result/{id}', [$value['id']]);
        $value['link']         = '<a href="' . $value['url'] . '" class="forms" target="_blank">' . $value['title'] . '</a>';
        $value['pic']          = FilesPic::getArray($value['pic']);
        $value['layout_id']    = "former_" . $value['id'];

        return $value;
    }
}
