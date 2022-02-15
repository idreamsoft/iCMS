<?php

class Utils
{
    public static $debug = false;
    public static function sign($params)
    {
        ksort($params);
        $text = http_build_query($params);
        return Security::makeHash($text, iPHP_KEY);
    }
    public static function makeId($value = null)
    {
        if ($value) return $value;
        return date("YmdHis") . uniqid() . rand(10000, 99999);
    }
    public static function LOG($output = null, $name = 'debug')
    {
        if (iPHP_DEBUG || self::$debug) {
            if ($output === 'RAW') {
                $output = Request::input(null,null);
            }
            $s = substr(md5(sha1(iPHP_KEY)), 8, 16);
            is_array($output) && $output = var_export($output, true);
            File::append(iPHP_APP_CACHE . '/' . $name . '.' . $s . '.log', $output . "\n");
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
        libxml_disable_entity_loader(true);
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

        $xml = "<xml>";
        foreach ($values as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }
    /**
     * [lastId 记录获取最后的ID]
     * @param  [type] $name [description]
     * @param  [type] $id   [description]
     * @return [type]       [description]
     */
    public static function lastId($id = null, $name = null)
    {
        $name === null && $name = basename(iPHP_SELF);
        $path = dirname(iPHP_SELF) . '/' . $name . '.lastId.txt';
        if ($id === null) {
            file_exists($path) or file_put_contents($path, 1);
            $lastId  = (int)trim(file_get_contents($path));
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
        if (!is_array($array)) return 0;

        $count = 0;
        foreach ($array as $item) {
            $c = self::getArrDim($item);
            if ($c > $count) $count = $c;
        }
        return $count + 1;
    }
    //判断是否为数字索引
    public static function isAssocArr($arr)
    {
        $keys = array_keys($arr);
        $ks   = implode('', $keys);
        return is_numeric($ks);
    }
    //判断是否为纯属数字数组
    public static function isNumArr($arr)
    {
        $values = array_values($arr);
        $vs   = implode('', $values);
        return is_numeric($vs);
    }
}
