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

// i('div')->attr('{
//                 "id": "id1",
//                 "name": "name",
//                 "class": "class",
//                 "value": "default"
//             }')->append('asd');

// preg_match_all('@<div(.*?[^<])>(.*?[^<]+)</div>@is', '<div id="id1" name="name" class="class" value="default"><a><asad></div>', $matches);
// var_dump($matches);
// i('textarea',$attr)->css('height','300px')
// $a = i('textarea')->css('height','300px');
// // $a->css(array('width'=>'300px;'));
// var_dump((string)$a);
// exit();
// $a = i('<textarea style="height:300px;" id="data_xytpuj" name="data[xytpuj]" class="span12"></textarea>')
// ->attr();
// var_dump($a);
//
// $a = i("div")->addClass("clearfloat mt10")->attr;
// var_dump($a);
// $a->removeClass('mt10');
// $a->removeClass('mt10');
// $a = i('<textarea style="height:300px;" id="data_xytpuj" name="data[xytpuj]" class="span12"></textarea>');
// $a->removeAttr('style');
// var_dump((string)$a);
// exit;

function i($selector = '')
{
    return new iQuery($selector);
}

/**
 * 仿jquery的几个小功能,简单对html操作
 */
class iQuery
{
    public $context = null;
    public $selector = null;
    protected $preg_value = '(.*?[^"|\'|>|/]*)';

    public function __construct($selector)
    {
        return $this->tag($selector);
    }

    public function tag($selector)
    {
        if (strpos($selector, '<') !== false) {
            preg_match('@<(\w+)\s.*?@is', $selector, $match);
            if ($match[1]) {
                $this->context = $selector;
                $this->selector = $match[1];
            }
            return $this;
        }
        $this->selector = $selector;
        switch ($selector) {
            // case 'select':
            // case 'button':
            // case 'textarea':
            // case 'span':
            // case 'div':
            //     $this->context = '<'.$selector.' {attr}>{html}</'.$selector.'>';
            // break;
            case 'img':
            case 'input':
                $this->context = '<' . $selector . ' {attr} />';
                break;
            default:
                $this->context = '<' . $selector . ' {attr}>{html}</' . $selector . '>';
                break;
        }
        return $this;
    }

    public function css_array($p = null)
    {
        $style = $this->attr('style');
        if ($style) {
            $array = explode(';', $style);
            foreach ($array as $key => $value) {
                list($a, $b) = explode(':', $value);
                $c[$a] = $b;
            }
            if ($p === null) {
                return $c;
            }
            return $c[$p];
        }
    }

    /**
     * css() 方法设置或返回被选元素的一个或多个样式属性。
     * @param  [type] $property [description]
     * @param  [type] $value    [description]
     * @return void
     */
    public function css($property = null, $value = null)
    {
        //返回匹配元素样式属性
        if ($property === null && $value === null) {
            return $this->css_array();
        }
        //css('{"witdh": "100px","height": "100px"}') json格式
        if (!is_array($property) && $value === null) {
            $_property = json_decode($property, true);
            is_array($_property) && $property = $_property;
            unset($_property);
        }
        //非法json返 false
        //css('type','text')
        if (!is_array($property) && is_string($property) && $value !== null) {
            $property = [$property=>$value];
        }
        //css('witdh')
        if (is_string($property) && $value === null) {
            return $this->css_array($property);
        }
        $pieces = [];
        foreach ((array)$property as $key => $value) {
            $pieces[] = $key . ':' . trim($value, ';') . ';';
        }

        if ($pieces) {
            $style = implode('', $pieces);
            $this->attr('style', $style);
        }
        return $this;
    }

    public function removeClass($key = null)
    {
        $class = '';
        if ($key) {
            $_class = $this->attr('class');
            $pieces = array_flip(explode(' ', $_class));
            unset($pieces[$key]);
            $class = implode(' ', array_flip($pieces));
        }
        $this->attr('class', $class);
        return $this;
    }

    /**
     * 返回匹配元素的属性和值。
     * @param  [type] $attr [description]
     * @return void
     */
    public function attrArray($attr = null)
    {
        preg_match_all('@([\w\-_]+)=["|\']*(.+?[^"|\'|>|/]*)["|\']*@is', $this->context, $matches);
        if ($matches[1]) {
            $array = [];
            foreach ($matches[1] as $key => $value) {
                $array[$value] = $matches[2][$key];
            }
            if ($attr === null) {
                return $array;
            }
            return $array[$attr];
        }
    }

    /**
     * 设置或返回匹配元素的属性和值。
     * @param  [type] $attribute 属性的名称。
     *                           一个或多个属性/值对。
     * @param  [type] $value     规定属性的值。
     */
    public function attr($attribute = null, $value = null)
    {
        if ($value === 'reset') {
            $this->tag($this->selector);
        }
        //返回匹配元素的属性和值
        if ($attribute === null && $value === null) {
            return $this->attrArray();
        }
        //attr('{"id": "id","name": "name","class": "class"}') json格式
        if (!is_array($attribute) && $value === null) {
            $_attribute = json_decode($attribute, true);
            is_array($_attribute) && $attribute = $_attribute;
            unset($_attribute);
        }
        //非法json返 false
        //attr('type','text')
        if (!is_array($attribute) && is_string($attribute) && $value !== null) {
            $attribute = [$attribute=>$value];
        }
        //attr('type')
        if (is_string($attribute) && $value === null) {
            return $this->attrArray($attribute);
        }

        if (strpos($this->context, '{attr}') === false) {
            foreach ((array)$attribute as $key => $val) {
                $replace = sprintf('%s="%s"', $key, $val);
                $search = sprintf('@%s="%s"@is', $key, $this->preg_value);
                preg_match($search, $this->context, $match);
                if ($match) {
                    $this->context = preg_replace($search, $replace, $this->context);
                } else {
                    // 使用正则表达式查找第一个 <div> 标签
                    $pattern = sprintf('/<%s([^>]*)>/', $this->selector);
                    // 替换第一个匹配的 <div> 标签，添加属性
                    $replacement = sprintf('<%s$1 %s>', $this->selector, $replace);
                    $this->context = preg_replace($pattern, $replacement, $this->context, 1);
                    // $this->context = str_replace('<' . $this->selector, '<' . $this->selector . ' ' . $replace, $this->context);
                }
            }
        } else {
            foreach ((array)$attribute as $key => $val) {
                $val === null or $pieces[$key] = $key . '="' . $val . '"';
            }
            if (isset($attribute['value'])) {
                //移除非input标签的 value
                if ($this->selector != 'input') {
                    unset($pieces['value']);
                }
            } else {
                //增加input标签的空value
                if ($this->selector == 'input') {
                    $pieces['value'] = 'value=""';
                }
            }
            $attr = implode(' ', $pieces);
            $this->context = str_replace('{attr}', $attr, $this->context);
            //给textarea 赋值
            if ($this->selector == 'textarea' && isset($attribute['value'])) {
                $this->val($attribute['value']);
            }
        }
        return $this;
    }

    public function removeAttr($key = null)
    {
        $attr = '';
        if ($key) {
            $array = $this->attr();
            unset($array[$key]);
            $this->attr($array, 'reset');
        }
        return $this;
    }

    public function val($value = null)
    {
        if ($this->selector == 'input') {
            if ($value === null) {
                return $this->attr('value');
            } else {
                $this->attr('value', $value);
            }
        } elseif ($this->selector == 'textarea') {
            if ($value === null) {
                return $this->html();
            } else {
                $this->html($value);
            }
        }
        return $this;
    }

    public function text($text = null)
    {
        if ($text === null) {
            $text = $this->html();
            return $this->html2text($text);
        } else {
            $text = $this->html2text($text);
            $this->html($text);
        }
        return $this;
    }

    public function html($html = null, $append = false)
    {
        $this->context = str_replace('{html}', '', $this->context);
        if ($html === null) {
            preg_match('@<' . $this->selector . '(.*?[^<]*)>(.*?[^<]*)</' . $this->selector . '>@is', $this->context, $match);
            if ($match[2]) {
                return $match[2];
            }
        } else {
            $reference = null;
            $append && $reference = '$2'; //反向引用
            $this->context = preg_replace(
                '@<' . $this->selector . '(.*?[^<]?)>(.*?[^<]?)</' . $this->selector . '>@is',
                '<' . $this->selector . '$1>' . $reference . $html . '</' . $this->selector . '>',
                $this->context
            );
        }
        return $this;
    }

    public function addClass($class, $flag = true)
    {
        if ($flag) {
            $this->attr('class', $class);
        } else {
            $class = $this->attr('class') . ' ' . $class;
            $class = explode(' ', $class);
            $pieces = array_unique($class);
            $pieces = array_map('trim', $pieces);
            $this->attr('class', implode(' ', $pieces));
        }
        return $this;
    }

    public function append($html)
    {
        $this->html($html, true);
        return $this;
    }

    public function html2text($value)
    {
        $value = is_array($value) ?
            array_map([$this, 'html2text'], $value) :
            preg_replace(['/<[\/\!]*?[^<>]*?>/is'], '', $value);

        return $value;
    }

    public function render()
    {
        $this->context = str_replace(['{attr}', '{html}'], '', $this->context);
        return $this->context;
    }

    public function __toString()
    {
        return  $this->render();
    }

    public function __destruct()
    {
        return  $this->render();
    }
}
