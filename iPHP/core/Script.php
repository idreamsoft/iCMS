<?php
// namespace iPHP\core;

/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
class Script
{

    public static $break      = true;
    public static $dialog     = array();

    // public static function json($array, $break = true, $flag = false)
    // {
    //     $json = json_encode($array);
    //     if ($callback = Request::sget('callback')) {
    //         self::jsonp($json, $callback);
    //     }
    //     Request::sget('script') && exit("<script>{$json};</script>");
    //     if ($flag) return $json;
    //     echo $json;
    //     $break && exit();
    // }

    // public static function jsonp($json, $callback = null, $node = '.parent')
    // {
    //     $callback === null && $callback = Request::sget('callback');
    //     empty($callback) && $callback = 'callback';
    //     is_array($json) && $json = json_encode($json);
    //     $json = str_replace('"%%function', 'function', $json);
    //     $json = str_replace('}%%"', '}', $json);
    //     echo "<script>window{$node}.{$callback}($json);</script>";
    //     // exit;
    // }
    public static function code($code = 0, $msg = '', $forward = '', $format = 'json')
    {
        if(is_array($msg)||@strstr($msg, ':')){
            $msg = Lang::get($msg, false);
        }
        $a = array('code' => $code, 'msg' => $msg, 'forward' => $forward);
        if ($format == 'json') {
            self::json($a);
        }
        return $a;
    }
    public static function msg($info, $ret = false, $msgType = null)
    {
        if (strpos($info, ':#:') === false) {
            $msg = $info;
        } else {
            list($label, $icon, $content) = explode(':#:', $info);
            $icon == 'warning' && $icon = 'times';

            if (iPHP_SHELL) {
                if ($label == "success") {
                    $msg = "\033[32m {$content} \033[0m"; //green
                } else {
                    $msg = "\033[31m {$content} \033[0m"; //red
                }
            } else {
                $msg = '<div class="ui-dialog-msg"><span class="badge badge-' . $label . '">';
                $icon && $msg .= '<i class="fa fa-fw fa-' . $icon . '"></i> ';
                if (strpos($content, ':') !== false && !preg_match("/<\/([^>]+?)>/is", $content)) {
                    $lang = Lang::get($content);
                    $lang && $content = $lang;
                }
                $msg .= $content . '</span></div>';
            }
        }
        self::$dialog['msgType'] && $msgType = strtoupper(self::$dialog['msgType']);
        if ($msgType == 'ARRAY') {
            return compact('label', 'icon', 'content');
        }
        if ($ret) return $msg;
        echo $msg;
    }
    public static function js($str = "js:", $ret = false)
    {
        $type = substr($str, 0, strpos($str, ':'));
        $act = substr($str, strpos($str, ':') + 1);
        switch ($type) {
            case 'js':
                $act && $code = $act;
                $act == "-1" && $code = 'window.top.history.go(-1);';
                $act == "0" && $code = '';
                $act == "1" && $code = 'window.top.location.href = window.top.location.href;';
                break;
            case 'url':
                $act == "-1" && $act = iPHP_REFERER;
                $act == "1" && $act = iPHP_REFERER;
                $code = "window.top.location.href='" . $act . "';";
                break;
            case 'src':
                $code = "$('#iPHP_FRAME').attr('src','" . $act . "');";
                break;
            default:
                $code = '';
        }

        if ($ret) {
            return $code;
        }

        echo '<script type="text/javascript">' . $code . '</script>';
        self::$break && exit();
    }
    public static function warning($info)
    {
        return self::msg('warning:#:warning:#:' . $info);
    }
    public static function alert($msg, $js = null, $s = 3, $flag = 'warning:#:warning:#:')
    {
        if (self::$dialog['alert'] === 'window') {
            self::js("js:window.alert('{$msg}')");
        }

        self::$dialog = array_merge(
            array(
                'id'         => iPHP_APP . '-DIALOG-ALERT',
                'skin'       => iPHP_APP . '_dialog_alert',
                'modal'      => true,
                'quickClose' => true,
                'width'      => 360,
                'height'     => 120,
            ),
            (array)self::$dialog
        );

        if (is_numeric($js) && $s == 3) {
            $s = $js;
            $js = null;
        }
        return self::dialog($flag . $msg, $js, $s);
    }
    public static function success($msg, $js = null, $s = 3)
    {
        return self::alert($msg, $js, $s, 'success:#:check:#:');
    }
    public static function set_dialog($key, $value)
    {
        self::$dialog[$key] = $value;
    }
    public static function close_dialog($top = true)
    {
        $obj = ($top ? 'top.' : '') . 'iCMS.ui.$dialog';
        echo '<script>if(' . $obj . ') ' . $obj . '.close().remove();</script>';
    }
    public static function dialog2($info = array(), $js = 'js:', $s = 3, $buttons = null, $update = false)
    {
        $info = (array) $info;
        $title = $info[1] ? $info[1] : '提示信息';
        $msg = self::msg($info[0], true, 'ARRAY');
        if (self::$dialog['callback']) {
            return iPHP::callback(self::$dialog['callback'], array($msg['content']));
        }
        if (iPHP_SHELL) {
            echo $msg['content'];
            return false;
        }

        $id = self::$dialog['id'] ? self::$dialog['id'] : 'iPHP-DIALOG';
        $options = array(
            "id"      => $id,
            "time"    => null,
            "api"     => 'iPHP',
            "label"   => $msg['label'],
            "icon"    => $msg['icon'],
            "content" => $msg['content'],
            "title"   => (self::$dialog['title'] ? self::$dialog['title'] : iPHP_APP) . " - {$title}'",
            "modal"   => self::$dialog['modal'] ? true : false,
            "width"   => (self::$dialog['width'] ? self::$dialog['width'] : 'auto'),
            "height"  => (self::$dialog['height'] ? self::$dialog['height'] : 'auto'),
        );
        isset(self::$dialog['quickClose']) && $options['quickClose'] = self::$dialog['quickClose'] ? true : false;
        self::$dialog['skin'] && $options['skin'] = self::$dialog['skin'];

        $func = self::js($js, true);

        $auto_func = 'd.close().remove();';
        $ok = array();
        if ($func) {
            $ok = array('okValue' => '确 定', 'ok' => '%%function(){' . $func . '}%%');
            $auto_func = $func . 'd.close().remove();';
        }
        $IS_FRAME = false;
        if (is_array($buttons)) {
            // $okbtn = "{value:'确 定',callback:function(){" . $func . "},autofocus: true}";
            foreach ($buttons as $key => $val) {
                $button = array('value' => $val['text']);
                $val['id'] && $button['id'] = $val['id'];
                $val['js'] && $func = $val['js'];
                $val['url'] && $func = "window.location.href='{$val['url']}';";
                if ($val['src']) {
                    $func = "$('#iPHP_FRAME').attr('src','{$val['src']}');return false;";
                    $IS_FRAME = true;
                }
                $val['target'] && $func = "window.open('{$val['url']}','_blank');";
                if ($val['close'] === false) {
                    $func .= "return false;";
                }
                $val['time'] && $s = $val['time'];
                if ($func) {
                    $button['callback'] = '%%function(){' . $func . '}%%';
                    $options['button'][] = $button;
                    $val['next'] && $auto_func = $func;
                }
            }
        } else {
            self::$dialog['ok'] or $options = array_merge($options, $ok);
        }
        self::$dialog['ok'] && $options[] += array('okValue' => '确 定', 'ok' => '%%function(d){' . self::$dialog['ok:js'] . '}%%');
        self::$dialog['cancel'] && $options[] += array('cancelValue' => '确 定', 'cancel' => '%%function(d){' . self::$dialog['cancel:js'] . '}%%');

        if ($update) {
            if ($update === 'FRAME' || $IS_FRAME) {
                $options['update'] = $dialog_id;
            }
            $auto_func = $func;
        }
        $s <= 30     && $timeout = $s * 1000;
        $s > 30      && $timeout = $s;
        $s === false && $timeout = false;
        if ($timeout) {
            $options['timeout'] = $timeout;
            $options['timeout_callback'] = '%%function(d){' . $auto_func . '}%%';
        } else {
            $update && $options['timeout_callback'] = '%%function(d){' . $auto_func . '}%%';
        }
        iJson::jsonp($options, 'dialog_callback');
    }
    public static function dialog($info = null, $js = 'js:', $s = 3, $buttons = null, $update = false)
    {
        // self::dialog2($info, $js, $s, $buttons , $update);
        $title = self::$dialog['sTitle'] ?: '提示信息';
        $content = self::msg($info, true);
        if (self::$dialog['callback']) {
            return iPHP::callback(self::$dialog['callback'], array($content));
        }
        if (iPHP_SHELL) {
            echo $content;
            return false;
        }
        $content =
            '<table class="ui-dialog-table" align="center">' .
            '<tr>' .
            '<td valign="middle">' . $content . '</td>' .
            '</tr>' .
            '</table>';
        $content = str_replace(array("\n", "\r", "\\"), array('', '', "\\\\"), $content);
        $content = addslashes($content);
        $dialog_id = self::$dialog['id'] ? self::$dialog['id'] : 'iPHP-DIALOG';
        $options = array(
            "time:null", "api:'iPHP'",
            "id:'" . $dialog_id . "'",
            "title:'" . (self::$dialog['title'] ? self::$dialog['title'] : iPHP_APP) . " - {$title}'",
            "modal:" . (self::$dialog['modal'] ? 'true' : 'false'),
            "width:'" . (self::$dialog['width'] ? self::$dialog['width'] : 'auto') . "'",
            "height:'" . (self::$dialog['height'] ? self::$dialog['height'] : 'auto') . "'",
        );
        if (isset(self::$dialog['quickClose'])) {
            $options[] = "quickClose:" . (self::$dialog['quickClose'] ? 'true' : 'false');
        }
        if (isset(self::$dialog['skin'])) {
            $options[] = "skin:'" . self::$dialog['skin'] . "'";
        }

        //$content && $options[]="content:'{$content}'";
        $auto_func = 'd.close().remove();';
        $func = self::js($js, true);
        if ($func) {
            $ok = 'okValue: "确 定",ok: function(){' . $func . '}';
            // $buttons OR $options[] = $ok
            $auto_func = $func . 'd.close().remove();';
        }
        $IS_FRAME = false;
        if (is_array($buttons)) {
            $okbtn = "{value:'确 定',callback:function(){" . $func . "},autofocus: true}";
            foreach ($buttons as $key => $val) {
                $val['id'] && $id = "id:'" . $val['id'] . "',";
                $val['js'] && $func = $val['js'] . ';';
                $val['url'] && $func = "iTOP.location.href='{$val['url']}';";
                if ($val['src']) {
                    $func = "iTOP.$('#iPHP_FRAME').attr('src','{$val['src']}');return false;";
                    $IS_FRAME = true;
                }
                $val['target'] && $func = "iTOP.window.open('{$val['url']}','_blank');";
                if ($val['close'] === false) {
                    $func .= "return false;";
                }
                $val['time'] && $s = $val['time'];

                if ($func) {
                    $buttonA[] = "{" . $id . "value:'" . $val['text'] . "',callback:function(){" . $func . "}}";
                    $val['next'] && $auto_func = $func;
                }
            }
            $button = implode(",", $buttonA);
        } else {
            self::$dialog['ok'] or $options[] = $ok;
        }
        self::$dialog['ok'] && $options[] = 'okValue: "确 定",ok: function(){' . self::$dialog['ok:js'] . '}';
        self::$dialog['cancel'] && $options[] = 'cancelValue: "取 消",cancel: function(){' . self::$dialog['cancel:js'] . '}';

        $dialog = '';
        if ($update) {
            if ($update === 'FRAME' || $IS_FRAME) {
                $dialog = 'var iTOP = window.top,d = iTOP.dialog.get("' . $dialog_id . '");';
            }
            $auto_func = $func;
        } else {
            $dialog .= 'var iTOP = window.top,';
            $dialog .= 'options = {' . implode(',', $options) . '},d = iTOP.' . iPHP_APP . '.ui.dialog(options);';
            // if(self::$dialog_lock){
            // 	$dialog.='d.showModal();';
            // }else{
            // 	$dialog.='d.show();';
            // }
        }
        $button && $dialog .= "d.button([$button]);";
        $content && $dialog .= "d.content('$content');";

        $s <= 30 && $timeout = $s * 1000;
        $s > 30 && $timeout = $s;
        $s === false && $timeout = false;
        if ($timeout) {
            $dialog .= 'window.setTimeout(function(){' . $auto_func . '},' . $timeout . ');';
        } else {
            $update && $dialog .= $auto_func;
        }
        echo self::$dialog['code'] ? $dialog : '<script>' . $dialog . '</script>';
        self::$break && exit();
    }

    public static function check($is)
    {
        return $is ?
            '<font color="green"><i class="fa fa-fw fa-check"></i></font>' :
            '<font color="red"><i class="fa fa-fw fa-times"></i></font>';
    }
    public static function flush_start()
    {
        @set_time_limit(0);
        @header('Cache-Control: no-cache');
        @header('X-Accel-Buffering: no');
        ob_start();
        ob_end_clean();
        ob_end_flush();
        ob_implicit_flush(true);
    }
    public static function flush()
    {
        flush();
        ob_flush();
    }
    public static function loop($total = null, $pageSize = 100, $config = array())
    {
        $total === null && $total = (int)$_GET['total'];
        $page      = (int)$_GET['page'];
        $allTime   = (int)$_GET['allTime'];
        $totalTime = (int)$_GET['totalTime'];
        $maxpage   = ceil($total / $pageSize);

        $step = $config['step'];
        $next = $config['next'];
        $stop = $config['stop'];

        if ($step) {
            is_callable($step['callback'][0]) && $call = call_user_func_array($step['callback'][0], (array)$step['callback'][1]);
            $use_time = Helper::timerStop();
            $query  = array(
                'total'   => $total,
                'page'    => $page + 1,
                'allTime' => $allTime + $use_time
            );
            $query = Route::merge_query($query, $step['url']);
            if ($maxpage > 0 && $query['page'] < $maxpage) {
                $url = Route::URI($query);
            }

            $msg    = $step['title'];
            $format = "共<span class='label label-info'>{$total}</span>%2\$s%1\$s,将分成<span class='label label-info'>{$maxpage}</span>次完成<hr />" .
                "开始执行第<span class='label label-info'>" . $query['page'] . "</span>次,<span class='label label-info'>{$pageSize}</span>%2\$s%1\$s<hr />";
            array_unshift($step['msg'], $format);
            $msg .= call_user_func_array('sprintf', $step['msg']);
            $memory = memory_get_usage();
            $msg .= "用时<span class='label label-info'>{$use_time}</span>秒,";
            $msg .= "使用内存:" . File::sizeUnit($memory) . '<hr />';
            $msg .= $call;
        }

        if ($step && $url) {
            $moreBtn = array(
                array("id" => "btn_stop", "text" => "停止", "url" => APP_URL),
                array("id" => "btn_next", "text" => "继续", "src" => $url, "next" => true)
            );
            $dtime    = 0.1;
            isset($step['time']) && $dtime = $step['time'];
            $ntime = ($maxpage - $query['page']) * $use_time + $dtime;
            $msg .= "预计全部完成还需要<span class='label label-info'>{$ntime}</span>秒<hr />";
        } else {
            $moreBtn = array(array("id" => "btn_next", "text" => "完成", "url" => APP_URL));
            $dtime   = 5;
            $msg .= "已全部完成!";
            $query["allTime"] && $msg .= "总共用时<span class='label label-info'>" . $query["allTime"] . "</span>秒";

            if ($next) {
                isset($next['time']) && $dtime = $next['time'];
                $nquery = array(
                    'page'      => 0,
                    'allTime'   => 0,
                    'totalTime' => ($totalTime + $query["allTime"])
                );
                $nquery = Route::merge_query($nquery, $next['url']);
                $url    = Route::URI($nquery);
                $msg .= $next['msg'];
                $moreBtn = array(
                    array("id" => "btn_stop", "text" => "停止", "url" => APP_URL),
                    array("id" => "btn_next", "text" => "继续", "src" => $url, "next" => true)
                );
            }
            if ($stop) {
                empty($stop['btn']) && $stop['btn'] = '完成';
                isset($stop['time']) && $dtime = $stop['time'];
                isset($stop['url']) && $moreBtn = array(array("id" => "btn_next", "text" => $stop['btn'], "url" => $stop['url']));
                $msg = $stop['msg'];
                $totalTime && $msg .= "总共用时<span class='label label-info'>{$totalTime}</span>秒";
            }
        }
        $update = $page ? 'FRAME' : false;
        self::dialog($msg, ($url ? "src:" . $url : ''), $dtime, $moreBtn, $update);
    }
}
