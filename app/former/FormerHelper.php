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
    public static function checkbox($data, $attr)
    {
        if (is_array($data)) {
            $flag = true;
        } else {
            $flag = false;
            $data = explode(";", $data);
        }
        $html = '';
        foreach ($data as $optk => $val) {
            $val = trim($val, "\r\n");
            if ($val === '') continue;

            if ($flag) {
                $opt_text  = $optk;
                $opt_value = $val;
            } else {
                list($opt_text, $opt_value) = explode("=", $val);
            }

            $opt_value === null && $opt_value = $opt_text;
            $attr2 = $attr;
            $attr2['value'] = $opt_value;
            $attr2['class'] .= ' custom-control-input ' . $attr2['id'];
            $attr2['id'] .= '_' . $optk;

            $label = self::element(
                'label',
                [
                    'for' => $attr2['id'],
                    // 'class' => $attr['type'] . '-inline'
                    'class' => 'custom-control-label'
                ]
            )->html($opt_text);

            $input = self::element('input', $attr2);

            if (Former::$template['class']['input']) {
                $input->removeClass(Former::$template['class']['input']);
            }
            $element = $input . $label;
            $html .= self::element("div", [
                'class' => sprintf('custom-control custom-%s custom-control-lg custom-control-inline', $attr['type']),
            ])->html($element);
        }
        return $html;
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
        foreach ($data as $ok => $val) {
            $val = trim($val, "\r\n");
            if ($val) {
                list($opt_text, $opt_value) = explode("=", $val);
                $opt_value === null && $opt_value = $opt_text;
                $selected = $opt_value == $sval ? 'selected' : '';
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
