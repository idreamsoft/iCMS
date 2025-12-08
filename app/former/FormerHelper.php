<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');

class FormerHelper
{
    public static function element($name, $attr = null)
    {
        $element = new iQuery($name);
        $attr && $element->attr($attr);
        return $element;
    }
    public static function checkbox($data, $attr, $selected = null)
    {
        if (!is_array($data)) {
            $data = explode(";", $data);
        }
        ksort($data);
        $html = '';
        foreach ($data as $value => $text) {
            if ($text === '') continue;

            if (strpos($text, '=') !== false) {
                list($text, $value) = explode("=", $text);
            }
            $text = trim($text, "\r\n");

            $value === null && $value = $text;
            $attr2 = $attr;
            $attr2['value'] = $value;
            $attr2['class'] .= ' form-check-input ' . $attr2['id'];
            $attr2['id'] .= '_' . $value;
            $attr2['data-checked'] = $selected;
            if(!is_null($value) && $selected==$value){
                // $attr2['checked'] = 'checked';
            }
            $label = self::element(
                'label',
                [
                    'for' => $attr2['id'],
                    // 'class' => $attr['type'] . '-inline'
                    'class' => 'form-check-label'
                ]
            )->html($text);

            $input = self::element('input', $attr2);

            if (Former::$template['class']['input']) {
                $input->removeClass(Former::$template['class']['input']);
            }
            $element = $input . $label;
            $html .= self::element("div", [
                'class' => sprintf('custom-control custom-%s form-check-lg form-check-inline', $attr['type']),
            ])->html($element);
        }
        return $html;
    }
    public static function json($fields)
    {
        $output = [];
        if ($fields) {
            // $field_array = apps_mod::get_field_array($fields,true);
            $baseFieldsKeys = AppsTable::getBaseFieldsKeys();
            foreach ($fields as $key => $item) {
                $tabs = array($key, $item['label']);
                $fieldArray = $item['fields'];
                foreach ($fieldArray as $k => $value) {
                    $value['tabs'] = $tabs;
                    $readonly = in_array($value['name'], $baseFieldsKeys) ? true : false;
                    $output[] = [$key, $value, $readonly];
                }
            }
        }
        echo json_encode($output);
    }
    public static function makejs($fields, $format = null)
    {
        if ($fields) {
            // $field_array = apps_mod::get_field_array($fields,true);
            $baseFieldsKeys = AppsTable::getBaseFieldsKeys();
            foreach ($fields as $key => $item) {
                $tabs = array($key, $item['label']);
                $fieldArray = $item['fields'];
                foreach ($fieldArray as $k => $value) {
                    $value['tabs'] = $tabs;
                    $readonly = in_array($value['name'], $baseFieldsKeys) ? 'true' : 'false';
                    printf(
                        $format,
                        json_encode($value),
                        $value['id'],
                        $readonly,
                        $key
                    );
                }
            }
        }
    }
    public static function option($data, $name, $sval = null)
    {
        is_array($data) or $data = explode(";", $data);
        $option = '';
        $sval = explode(',',$sval);
        foreach ($data as $ok => $val) {
            $val = trim($val, "\r\n");
            if ($val) {
                list($opt_text, $opt_value) = explode("=", $val);
                $opt_value === null && $opt_value = $opt_text;
                $selected = in_array($opt_value,$sval);
                $selected = $selected? 'selected' : '';
                $opt_type = Former::$config['option'] ? '[' . $name . '="' . $opt_value . '"]' : '';
                $option .= sprintf(
                    '<option value="%s" %s>%s %s</option>',
                    $opt_value,
                    $selected,
                    $opt_text,
                    $opt_type
                );
            }
        }
        return $option;
    }
}
