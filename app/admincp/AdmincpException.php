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
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    public function display()
    {
        $ex = self::$ex ?: $this;
        $code    = $ex->getCode();
        $line    = $ex->getLine();
        $file    = $ex->getFile();
        $message = Security::filterPath($ex->getMessage());
        $trace   = Security::filterPath($ex->getTraceAsString());
        $state   = (string)(method_exists($ex, 'getState') ? $ex->getState() : $code);

        Script::$dialog['width'] = 600;

        if (Request::param('frame') || Request::isPost() || Request::file()) {
            $isAlert = true;
            iJson::$jsonp = 'AdmAlert';
        }
        if(Request::isAjax()){
            $isAjax = true;
            iJson::$jsonp = null;
        }
        switch ($state) {
            case 'ajax':
                $isAjax = true;
                iJson::$jsonp = null;
                break;
            case 'alert': //admincpBase::alert
                if ($isAjax) {
                    return iJson::error($message);
                }
                break;
            case 'error': //错误页面
                $title = "出错啦!";
                break;
            case 'uri': //无uri权限
                $access  = Cache::get('app/access');
                $text    = $message;
                if (is_array($access) && $access[$message]) {
                    $text = $access[$message];
                }
                $title   = "无权限访问!";
                // $mText   = '的访问';
                // if (stripos($text, 'do_') !== false) {
                //     $mText = '功能的执行';
                // }
                $message = sprintf("对不起！您未获得【%s [%s]】的权限，请联系管理员为您开启。", $text,$message);
                break;
            case 'node.alert': //无node权限 alert
            case 'node.page': //无node权限 page
                list($cid, $type) = explode(':', $message);
                $typeText = NodeAccess::$appTypeMap[$type];
                empty($typeText) && $typeText = NodeAccess::$nodeTypeMap[$type];
                $title   = "无权限访问!";
                $cText = "节点【id={$cid}】";
                $cid == "0" && $cText = '顶级节点';
                $message = sprintf("对不起，您未获得%s %s权限，<br />请联系管理员为您开启。", $cText, $typeText);
                break;
            case '-90': //安全检测
                $title = "非法访问!";
                $error = sprintf('<hr/><div class="text-left">来路：%s</div>', $_SERVER["REQUEST_URI"]);
                break;
            default:
                Script::$dialog['height'] = 'auto';
                $message .= sprintf(' 【STATE:%s】', $state);
                $error = sprintf('<hr/><div class="text-left">%s</div>', str_replace("\n", "<br />", $trace));
                break;
        }
        if ($isAjax) return iJson::error($message . $error, -1);

        if ($isAlert) {
            $time = 30000000;
            Script::$dialog['sTitle'] = 'AdmincpException';
            return Script::alert($message . $error, $time);
        }
        include AdmincpView::display("exception", 'admincp');
    }
}
