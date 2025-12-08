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
class Utils
{
    public static $debug = false;

    public static function isWeChatBrowser()
    {
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }
        return false;
    }

    public static function format($value, $n = 2)
    {
        return sprintf('%0.' . $n . 'f', $value);
    }

    public static function isEmail($email)
    {
        return preg_match("/^[\w\-\.]+@[\w\-]+(\.\w+)+$/i", $email);
    }

    public static function isPhone($text)
    {
        return preg_match("/^1[3-9]\d{9}$/i", $text);
    }

    public static function sign($params, $keys = null)
    {
        $keys && $params = array_filter_keys($params, $keys);
        ksort($params);
        $text = http_build_query($params);
        return Security::makeHash($text, iPHP_KEY);
    }

    public static function makeId($value = null)
    {
        if ($value) {
            return $value;
        }
        // return date('YmdHis') . uniqid() . random(5);
        return date('YmdHis') . random(6, 1) . random(6);
    }

    public static function log($output, $name = null)
    {
        is_null($name) && $name = date('Y-m-d H');
        is_array($output) && $output = var_export($output, true);
        File::append(iPHP_APP_CACHE . '/log/' . $name . '.log', $output . "\n");
    }

    public static function debug($output = null, $name = 'debug')
    {
        if (iPHP_DEBUG || self::$debug) {
            if ($output === 'RAW') {
                $output = Request::input();
            }
            $s = substr(md5(sha1(iPHP_KEY)), 8, 16);
            is_array($output) && $output = var_export($output, true);
            File::append(iPHP_APP_CACHE . '/debug/' . $name . '.' . $s . '.log', $output . "\n");
        }
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @return array|false
     */
    public static function xmlToArray($xml, &$sxml = null)
    {
        if (!$xml) {
            return false;
        }

        // 检查xml是否合法
        $xml_parser = xml_parser_create();
        if (!xml_parse($xml_parser, $xml, true)) {
            xml_parser_free($xml_parser);
            return false;
        }

        //将XML转为array
        //禁止引用外部xml实体
        if (PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader(true);
        }
        $sxml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $data = json_decode(json_encode($sxml), true);
        return $data;
    }

    /**
     * 输出xml字符
     * @param array $values
     * @return string|bool
     **/
    public static function arrayToXml($values)
    {
        if (!is_array($values) || count($values) <= 0) {
            return false;
        }

        $xml = '<xml>';
        foreach ($values as $key => $val) {
            if (is_numeric($val)) {
                $xml .= '<' . $key . '>' . $val . '</' . $key . '>';
            } else {
                $xml .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
            }
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * lastId 记录获取最后的ID
     * @param  [type] $name [description]
     * @param  [type] $id   [description]
     * @return void
     */
    public static function lastId($id = null, $name = null)
    {
        $name === null && $name = File::name(iPHP_SELF);
        $path = dirname(iPHP_SELF) . '/' . $name . '.lastId.txt';
        if ($id === null) {
            file_exists($path) or file_put_contents($path, 1);
            $lastId = (int)trim(file_get_contents($path));
            return $lastId;
        }
        file_put_contents($path, $id);
    }

    public static function toArray($data)
    {
        return json_decode(json_encode($data), true);
    }

    public static function toObject($data)
    {
        return json_decode(json_encode($data));
    }

    //获取数组维数
    public static function getArrDim($array)
    {
        if (!is_array($array)) {
            return 0;
        }

        $count = 0;
        foreach ($array as $item) {
            $c = self::getArrDim($item);
            if ($c > $count) {
                $count = $c;
            }
        }
        return $count + 1;
    }

    //判断是否为数字索引
    public static function isAssocArr($arr)
    {
        $keys = array_keys($arr);
        $ks = implode('', $keys);
        return is_numeric($ks);
    }

    //判断是否为纯属数字数组
    public static function isNumArr($arr)
    {
        $values = array_values($arr);
        $vs = implode('', $values);
        return is_numeric($vs);
    }

    public static function getConfig($key = null, $config = [])
    {
        if (is_null($key)) {
            return $config;
        }
        $keyArray = explode('.', $key);
        foreach ($keyArray as $k) {
            $config = $config[$k];
        }
        return $config;
    }

    public static function jumpto($url = '', $code = '')
    {
        $url or $url = iPHP_REFERER;
        if (Request::isAjax()) {
            iJson::success('jumpto', $url);
            exit;
        }
        if (@headers_sent()) {
            echo '<meta http-equiv=\'refresh\' content=\'0;url=' . $url . '\'><script type="text/javascript">window.location.replace(\'' . $url . '\');</script>';
        } else {
            header("Location: $url");
        }
        $code && Request::status($code);
        exit;
    }

    public static function buffer()
    {
        @set_time_limit(0);
        @header('Cache-Control: no-cache');
        @header('X-Accel-Buffering: no');
        ob_start();
        ob_end_clean();
        ob_end_flush();
        ob_implicit_flush(true);
    }

    public static function flushEnd()
    {
        ob_end_clean();
        ob_end_flush();
        ob_implicit_flush(true);
    }

    public static function flush()
    {
        flush();
        ob_flush();
    }
    public static function desensitizePhoneNumber($phoneNumber) {
        // 检查输入是否为空或格式不正确
        if (empty($phoneNumber) || !is_numeric($phoneNumber) || strlen($phoneNumber) != 11) {
            return 'Invalid phone number';
        }
    
        // 使用 substr_replace 函数将中间四位替换为 ****
        return substr_replace($phoneNumber, '****', 3, 4);
    }
    public static function checkPasswordComplexity($password)
    {
        // 密码长度至少为8
        if (strlen($password) < 8) {
            return false;
        }

        // 密码中必须包含大写字母、小写字母和数字
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return false;
        }

        // 密码必须包含特殊字符
        if (!preg_match('/[\W_]/', $password)) {
            return false;
        }

        // 密码复杂度符合要求
        return true;
    }

    public static function cliHelp($args, $method = '', $argv = '')
    {
        if ($args == '--help') {
            exit(sprintf('exp: %s spider %s %s', $_SERVER['argv'][0], substr($method, 6), $argv));
        }
    }

    public static function cliPrints($text, $opt = 'w')
    {
        $map = [
            'r' => "%s\033[%s31m %s \033[0m", //红色字
            'g' => "%s\033[%s32m %s \033[0m", //绿色字
            'y' => "%s\033[%s33m %s \033[0m", //黄色字
            'b' => "%s\033[%s34m %s \033[0m", //蓝色字
            'p' => "%s\033[%s35m %s \033[0m", //紫色字
            's' => "%s\033[%s36m %s \033[0m", //天蓝字
            'w' => "%s\033[%s37m %s \033[0m", //白色字
        ];
        //字背景颜色范围:40 - 49
        $bgcMap = [
            'd'  => 40, //黑  dark
            'dr' => 41, //深红
            'g'  => 42, //绿
            'y'  => 43, //黄色
            'b'  => 44, //蓝色
            'p'  => 45, //紫色
            'dg' => 46, //深绿
            'w'  => 47, //白色
        ];

        //\33[0m 关闭所有属性
        //\33[1m 设置高亮度
        //\33[4m 下划线
        //\33[5m 闪烁
        //\33[7m 反显
        //\33[8m 消隐
        // 字背景颜色范围:40 - 49

        list($color, $bgc, $prop) = explode(':', $opt);

        $text = html2text($text);
        $prop && $format1 = sprintf("\33[%sm", $prop);
        $bgc && $format2 = sprintf(
            '%s;',
            is_numeric($bgc) ? $bgc : $bgcMap[$bgc]
        );
        $format = sprintf($map[$color], $format1, $format2, $text);
        print self::datetime() . $format . PHP_EOL;
    }

    public static function datetime($format='[%s] ')
    {
        $mtimestamp = sprintf('%.3f', microtime(true)); // 带毫秒的时间戳
        $timestamp = floor($mtimestamp); // 时间戳
        $milliseconds = round(($mtimestamp - $timestamp) * 1000); // 毫秒
        $milliseconds = sprintf("%-'03s", $milliseconds);
        $string = date('Y-m-d H:i:s', $timestamp) . '.' . $milliseconds;
        return sprintf($format, $string);
    }

    public static function template($template, $variables = [])
    {
        $renderer = new TemplateRenderer($template, $variables);
        return $renderer->render();
    }

    public static function http404($error = [], $tpl = '')
    {
        // 如果不是调试模式，则返回404
        if (!iPHP_DEBUG && !iPHP_TEMPLATE_DEBUG) {
            Request::status(404, $error[1]);
        }
        if (@constant('iPHP_URL_404')) {
            if (Request::isUrl(iPHP_URL_404)) {
                Utils::jumpto(iPHP_URL_404 . '?url=' . urlencode($_SERVER['REQUEST_URL']));
            } else {
                $file = sprintf('%s://%s', iPHP_APP, $tpl ? $tpl . '.htm' : iPHP_URL_404);
                View::tplExist($file) or $file = sprintf('%s://404.htm', iPHP_APP);

                View::assign('error_code', $error[1]);
                View::assign('error_msg', $error[0] ? $error[0] : '404 Not Found');
                View::display($file);
            }
        }else{
            if (iPHP_DEBUG || iPHP_TEMPLATE_DEBUG) {
                $trace = iDebug::backtraceHtml();
                $trace = Security::filterPath($trace);
                View::assign('error_code', $error[1]);
                View::assign('error_msg', $error[0]);
                View::assign('error_trace',$trace);
                View::display(sprintf('%s://debug.htm', iPHP_APP));
            }
        }
        exit;
    }
}
class TemplateRenderer
{
    protected $template;
    protected $variables;

    public function __construct($template, $variables = [])
    {
        $this->template = $template;
        $this->variables = $variables;
    }

    public function render()
    {
        $output = $this->template;

        // 处理嵌套的占位符和简单的变量替换
        $output = $this->replacePlaceholders($output, $this->variables);

        return $output;
    }

    protected function replacePlaceholders($template, $variables)
    {
        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                // 递归处理嵌套数组
                $template = $this->replacePlaceholders($template, $value);
            } else {
                // 构造占位符
                $placeholder = '{{' . $key . '}}';
                // 使用str_replace函数替换占位符
                $template = str_replace($placeholder, $value, $template);
            }
        }
        return $template;
    }
}
