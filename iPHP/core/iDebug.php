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

class iDebug
{
    public static $ID = 'error-message';
    public static $CALLBACK = [];
    public static $ex = null;
    public static $DATA = [];
    public static $SHOW_ARGS = true;

    public static function dump($data = null, $title = null)
    {
        if (empty($data)) {
            return;
        }
        $data === true && $data = iDebug::$DATA;

        echo '<div class="block block-themed">';
        echo '<div class="block-header">' . ($title ?: Lang('info:debug')) . '</div>';
        echo '<div class="block-content">';
        echo '<pre class="alert alert-primary">';
        $data = Security::filterPath($data);
        print_r($data);
        echo '</pre>';
        echo '</div>';
        echo '</div>';
    }

    public static function info($tpl = null)
    {
        $tpl = Security::filterPath($tpl);
        if (iPHP_DEBUG && iPHP_DEBUG_TRACE) {
            echo '<div class="block block-themed">';
            printf('<div class="block-header bg-primary-dark">info %s</div>', Lang('info:debug'));
            echo '<div class="block-content">';
            vprintf('<div class="alert alert-success">%s:%s <hr /> 
			%s:%s, %s:%ss <hr/>%s:%s %s:%ss
			</div>', [
                Lang('Template'), $tpl,
                Lang('info:use_memory'), File::sizeUnit(memory_get_usage()),
                Lang('info:execution_time'), Helper::timerStop(),
                Lang('database:execution_times'), DB::getQueryNum(),
                Lang('database:query_time'), DB::getQueryTime(),
            ]);
            iPHP_TPL_DEBUGGING && iDebug::dump(true, Lang('info:debug'));
            iPHP_DB_TRACE && iDebug::dump(DB::getQueryTrace(), Lang('database:details'));
            echo '</div>';
            echo '</div>';
        }
    }

    public static function errorLog($error)
    {
        defined('iPHP_DEBUG_ERRORLOG') or define('iPHP_DEBUG_ERRORLOG', false); //兼容PHP7
        if (!iPHP_DEBUG_ERRORLOG) {
            return;
        }

        @file_put_contents(
            iPHP_APP_CACHE . '/error_log_' . md5(sha1(iPHP_KEY)) . '_' . date('Y-m') . '.log',
            "<?php exit('What the fuck!');?>"
                . PHP_EOL . '[' . date('Y-m-d H:i:s') . '] ' . Request::ip()
                . PHP_EOL . iPHP_REQUEST_URL
                . ($_GET ? PHP_EOL . '$_GET=>' . var_export($_GET, true) : '')
                . ($_POST ? PHP_EOL . '$_POST=>' . var_export($_POST, true) : '')
                . PHP_EOL . var_export(html2text($error), true)
                . PHP_EOL . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * 异常处理
     *
     * @param   Exception  $ex
     */
    public static function exceptionHandler($ex)
    {
        if (is_object($ex)) {
            if ($ex instanceof sException) {
                $state = $ex->getState();
            }
            if ($ex instanceof PDOException) {
                self::$SHOW_ARGS = false;
            }

            $trace = $ex->getTrace();
            $error = [
                'errno'   => 99998,
                'errstr'  => $ex->getMessage(),
                'errfile' => $ex->getFile(),
                'errline' => $ex->getLine(),
                'code'    => $ex->getCode(),
                'state'   => $state,
            ];
            if ($ex instanceof Error) {
                $error['errno'] = E_ERROR;
            }
        } elseif (is_array($ex)) {
            $trace = $ex['trace'];
            $error = $ex;
        }
        return self::errorPrint($error, $error['errno'], $trace);
    }

    /**
     * PHP程序执行完成
     *
     * @return void
     */
    public static function shutdownHandler()
    {
        @session_write_close();
        $last = error_get_last();
        $last && $error = [
            'errno'   => $last['type'],
            'errstr'  => $last['message'],
            'errfile' => $last['file'],
            'errline' => $last['line']
        ];
        // @ 强制屏蔽错误
        if (self::$ex === false) {
            return false;
        }
        $last && self::errorPrint($error, 99999);
    }

    /**
     * 错误处理函数
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $error = compact('errno', 'errstr', 'errfile', 'errline');
        return self::errorPrint($error, $error['errno']);
    }

    public static function errorPrint($error, $code = 0, $backtrace = null)
    {
        //屏蔽php8 Undefined array key
        if (version_compare(PHP_VERSION, '8.0', '>=')) {
            if ($error['errno'] == E_WARNING) {
                if (
                    stripos($error['errstr'], 'Trying to access array offset on null') === 0 ||
                    stripos($error['errstr'], 'Undefined array key') === 0 ||
                    stripos($error['errstr'], 'Undefined variable $') !== false ||
                    stripos($error['errstr'], 'Trying to access array offset on value of type') === 0
                ) {
                    return null;
                }
            }
        }

        if (iPHP_SHELL) {
            if (
                stripos($error['errstr'], 'Passing null to parameter') !== false
            ) {
                return null;
            }
        }
        // @ 强制屏蔽错误
        if (!(error_reporting() & $error['errno'])) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }
        self::errorLog($error);

        $body = ob_get_contents();
        $body = Security::filterPath($body);
        ob_get_clean();

        defined('E_STRICT_ERROR') or define('E_STRICT_ERROR', 2048);
        defined('E_EXCEPTION_ERROR') or define('E_EXCEPTION_ERROR', 99998);
        defined('E_RECOVERABLE_ERROR') or define('E_RECOVERABLE_ERROR', 4096);

        switch ($error['errno']) {
            case E_ERROR:
                $type = 'Error';
                break;
            case E_WARNING:
                $type = 'Warning';
                break;
            case E_PARSE:
                $type = 'Parse Error';
                break;
            case E_NOTICE:
                $type = 'Notice';
                break;
            case E_CORE_ERROR:
                $type = 'Core Error';
                break;
            case E_CORE_WARNING:
                $type = 'Core Warning';
                break;
            case E_COMPILE_ERROR:
                $type = 'Compile Error';
                break;
            case E_COMPILE_WARNING:
                $type = 'Compile Warning';
                break;
            case E_USER_ERROR:
                $type = 'Error';
                break;
            case E_USER_WARNING:
                $type = 'Warning';
                break;
            case E_USER_NOTICE:
                $type = 'Notice';
                break;
            case E_STRICT_ERROR:
                $type = 'Strict Notice';
                break;
            case E_RECOVERABLE_ERROR:
                $type = 'Recoverable Error';
                break;
            case E_DEPRECATED:
                $type = 'Deprecated';
                break;
            case E_EXCEPTION_ERROR:
                $type = 'Exception';
                break;
            default:
                $type = sprintf('Unknown error (%s)', $error['errno']);
                break;
        }

        // $error['errfile'] = Security::filterPath($error['errfile']);
        $message = sprintf(
            '<div id="%s"><b>%s %s(%s)</b>:%s in <b>%s:%s</b> on line <b>%s</b><br />%s</div>',
            self::$ID,
            iPHP_APP,
            $type,
            $code,
            $error['errstr'],
            $error['errfile'],
            $error['errline'],
            $error['errline'],
            self::backtraceHtml($backtrace)
        );

        $message = Security::filterPath($message);

        $body && $message = $body . '<hr />' . $message;

        !iPHP_SHELL && error_log($message);

        if (isset(self::$CALLBACK['errorPrint']) && self::$CALLBACK['errorPrint']) {
            iPHP::callback(self::$CALLBACK['errorPrint'], [$message, $error]);
        }
        if (Request::isAjax()) {
            // $message = str_replace("<br />","\n",$message);
            // $message = html2text($message);
            // $message = html2js($message);
            iJson::fail($message, -99);
        }
        if (iPHP_SHELL) {
            $message = str_replace('<br />', PHP_EOL, $message);
            $message = html2text($message) . PHP_EOL;
        }
        exit($message);
    }

    public static function args($args)
    {
        // $args = var_export($args,true);
        // $args = preg_replace('/\s*\d+\s*=>\s*/','',$args);
        $res = [];
        foreach ($args as $key => $value) {
            if (is_array($value)) {
                $count = count($value);
                if ($count > 3) {
                    $slice = array_slice($value, 0, 3);
                    // $res[] = '[]';
                    $res[] = sprintf('[%s,...(items count %d )]', self::args($slice), $count - 3);
                } else {
                    $res[] = sprintf('[%s]', self::args($value));
                }
            } else {
                if (is_null($value)) {
                    $res[] = 'NULL';
                } elseif ($value === true) {
                    $res[] = 'TRUE';
                } elseif ($value === false) {
                    $res[] = 'FALSE';
                } else {
                    is_object($value) && $value = get_class($value);
                    if (self::$SHOW_ARGS) {
                        $res[] = sprintf('%s', is_string($value) && strlen($value) > 50 ? '......' : var_export($value, true));
                    } else {
                        $res[] = sprintf('%s', gettype($value));
                    }
                }
            }
        }
        return implode(',', $res);
    }

    public static function backtraceHtml($backtrace = null)
    {
        empty($backtrace) && $backtrace = debug_backtrace();
        krsort($backtrace);

        $html = [];
        $c = count($backtrace);
        foreach ($backtrace as $i => $bt) {
            $args = $bt['args'] ?: '';
            if (is_array($args)) {
                $args = self::args($args);
                $args = preg_replace('/\s*\d+\s*=>\s*/', '', $args);
                $args = preg_replace("/\n/", '', $args);
                $args = str_replace("/\n/", '', $args);
            }
            isset($bt['class']) or $bt['class'] = '';
            isset($bt['type']) or $bt['type'] = '';

            $div = ($c - $i) . ". <b>{$bt['class']}{$bt['type']}{$bt['function']}({$args})</b>";
            isset($bt['file']) && $div .= " in <b>{$bt['file']}:{$bt['line']}</b>";
            isset($bt['line']) && $div .= " on line <b>{$bt['line']}</b>";
            $html[] = $div;
        }
        return '<pre>' . implode('<br />', $html) . '</pre>';
    }
}
