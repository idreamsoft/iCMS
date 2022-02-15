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

class AppsHelper
{
    /**
     * 将post字段数据转换成分组数组[字段json,字段json....]
     */
    public static function transFields(&$data = null, $callfunc = null)
    {
        $fieldArray = array();
        $groupArray = array();
        if (is_array($data)) foreach ($data as $key => $value) {
            $arr = json_decode($value, true);
            if ($arr['name']) {
                if ($callfunc && is_callable($callfunc)) {
                    call_user_func_array($callfunc, array($arr));
                }
                $key = $arr['name'];
            }
            $fieldArray[$key] = $arr;

            //转换成字段分组数据,类 etc/default/field.json 数据
            $tabs = $arr['tabs'];
            unset($arr['tabs']);
            $groupArray[$tabs[0]]['label'] = $tabs[1];
            $groupArray[$tabs[0]]['fields'][$key] = $arr;
        }
        $data = $groupArray;
        // var_dump($fieldArray);
        return $fieldArray;
    }
    /**
     * 分析字段格工数据，解析出主表字段，附加表字段
     * @param $groupArray 字段分组数据
     * @param $masterFields 主表字段
     * @param $dataFields 附加表字段
     * @return Array array(主表字段,附加表字段)
     */
    public static function parseFields($groupArray = null, &$masterFields, &$dataFields)
    {
        foreach ($groupArray as $key => $group) {
            $fields = $group['fields'];
            foreach ($fields as $k => $value) {
                if ($value['name']) {
                    if (strtoupper($value['field']) == "MEDIUMTEXT") {
                        $dataFields[$k] = $value;
                    } elseif (strtoupper($value['type']) == "CAPTCHA") {
                        //移除验证码之字段,不参与表字段增改
                    } else {
                        $masterFields[$k] = $value;
                    }
                }
            }
        }
        // var_dump($masterFields, $dataFields);
        // exit;
    }
    //兼容旧v7
    public static function compatibleV7(&$fieldArray = null)
    {
        $isv7d = array_column($fieldArray, 'name');
        $isv7d && $fieldArray = array('base' => array(
            'label' => '基本信息',
            'icon' => 'fa fa-info-circle',
            'fields' => $fieldArray
        ));
    }
}
