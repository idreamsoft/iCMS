<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class AdmincpException extends sException
{
    public static $ex;

    public function __construct($message, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function display()
    {
        $ex = $this->getPrevious() ?: $this;
        $exvar = iPHP::getException($ex);
        extract($exvar);
        $isAjax = Request::isAjax();
        $isPost = Request::isPost();
        // $html = ob_get_contents();
        // $html = Security::filterPath($html);
        // ob_end_clean();

        $error = '';
        $url = null;
        if ($ex instanceof PDOException) {
            $state = 'PDOException';
        }
        switch ((string)$state) {
            case 'json':
                iJson::output($message);
                break;
            case 'ajax':
                $isAjax = true;
                break;
            case 'alert': //admincp::alert
                $html = '';
                break;
            case 'template':
                $title = Lang::get('admincp:template_error');
                $message .= '<hr />' . Security::filterPath(implode('<br />', AdmincpView::$TEMPLATE));
                $code = -9;
                break;
            case 'uri': //无uri权限
                $access = Cache::get('app/access');
                $text = $message;
                // if (is_array($access) && $access[$message]) {
                //     $text = $access[$message];
                // }
                $title = Lang::get('no access');
                $message = Lang::get('admincp:no_permission', [$text, $message]);
                $code = -3;
                break;
            case 'node': //无node权限
                list($cid, $type) = explode(':', $message);
                $typeText = AdmincpAuth::$appTypeMap[$type];
                empty($typeText) && $typeText = AdmincpAuth::$nodeTypeMap[$type];
                $title = Lang::get('no access');
                $cText = "node id={$cid}";
                $cid == '0' && $cText = Lang::get('top node');
                $message = Lang::get('admincp:no_permission', [$cText . $typeText]);
                $code = -3;
                break;
            case 'illegal': //安全检测
                $title = Lang::get('illegal access!');
                $error = sprintf('<hr/><div class="text-start">%s：%s</div>', Lang::get('referer'), $_SERVER['REQUEST_URI']);
                break;
            case 'PDOException':
                $code = -999;
                list($pdocode, $message, $driver) = DB::getError($ex);
                $title = Lang::get($driver . ' ERROR!');
                $error = sprintf('<hr/><div class="text-start">%s</div>', str_replace("\n", '<br />', $trace));
                break;
            case 'success': //正常提示
                $title = Lang::get('success');
                $color = 'success';
                $icon = 'fa fa-xs fa-check-circle';
                break;
            case 'security': //
            case 'error': //admincp::error
            default:
                $title = Lang::get('Error!');
                $code = -99;
                $code < 0 && $message .= sprintf(' 【STATE:%s】【CODE:%s】', $state, $code);
                $error = sprintf('<hr/><div class="text-start">%s</div>', str_replace("\n", '<br />', $trace));
                break;
        }
        //后台没有登陆 不报错
        if ($code < 0 && $code != Admin::LOGIN_CODE && !Admin::isLogin()) {
            if ($isAjax || $isPost) {
                iJson::fail('auth:login:required', 'reload');
            }
            return Request::status(404);
        }

        if ($isAjax || $isPost) {
            $msg = $message;
            $title && $title .= '<hr class="m-0 mt-1"/>';
            $code < 0 && $msg = $title . $html . $message . $error;
            return iJson::fail($msg, $url, $code, $state);
        }
        AdmincpView::setJsPath('admincp/assets/js/exception');
        include iPHP_APP_DIR . '/admincp/views/base/exception.html';
    }
}
