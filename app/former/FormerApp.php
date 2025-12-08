<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FormerApp
{
    public $appid = 0;

    public function __construct()
    {
        $this->appid = iCMS_APP_FORMER;
    }

    /**
     * 创建表单
     * @param  Array  $app        [app数据/appid]
     * @param  Array|null  $data         [数据]
     * @return void
     */
    public static function add($apps, $data = null, $option = true)
    {
        if (empty($apps['fields'])) {
            return;
        }
        self::gateway();
        Former::$APP = $apps;
        Former::$config['option'] = $option;
        Former::create($apps, $data);
    }

    public static function gateway()
    {
        Former::$GATEWAY = (int)defined('ADMINCP');
    }

    /**
     * 保存表单
     * @param  [type] $app    [app数据/appid]
     * @param  [type] $id [主键值]
     * @return void
     */
    public static function save($app, $id = null, &$flag = null)
    {
        if ($app['fields']) {
            self::gateway();
            $appid = (int)Request::post('appid');
            $data = Former::postData($app);
            $content = $data['content'];
            $contentData = $data['contentData'];
            $origData = Content::save($id, $content, $contentData);
            return $content;
        } else {
            iPHP::alert('Fields Not Found');
        }
    }

    public static function data($id, $apps, $name, &$resource, $vars = null, $node = null)
    {
        if ($apps['fields']) {
            $dataFields = [];
            $fieldArray = Former::fields($apps['fields'], $dataFields);
            if ($dataFields) {
                $table = $apps['app'];
                if (AppsTable::getDataTable($apps, $table)) {
                    ContentDataModel::setUnionKey($apps['app']);
                    ContentDataModel::setTable($table);
                    Content::cdata($resource);
                }
            }
            foreach ((array) $fieldArray as $fkey => $fields) {
                self::vars($fields, $fkey, $resource, $vars, $node, $name);
            }
        }
    }
    //自定义字段 前端输出处理
    public static function vars($field, $key, &$rs, $vars = null, $node = null, $app = null)
    {
        $option_array = [];
        $value = $rs[$key];
        $ret = [];
        $nkey = null;
        switch ($field['type']) {
            case 'image':
            case 'multi_image':
                $nkey = $key . '_array';
                $ret = FilesPic::getArray($value, 0, 0, true);
                $rs[$key] = $ret[0];
                break;
            case 'file':
            case 'multi_file':
                $nkey = $key . '_files';
                $ret = FilesApp::getArray($value);
                $rs[$key] = $ret[0];
                break;
            case 'node':
                if ($key == 'cid') {
                    break;
                }
                $nkey = $key . '_node';
                $ret = NodeCache::getId($value);
                break;
            case 'multi_node':
                $nkey = $key . '_node';
                $valArray = explode(',', $value);
                foreach ($valArray as $i => $val) {
                    $ret[$i] = NodeCache::getId($val);
                }
                break;
            case 'userid':
                if ($vars['user']) {
                    $nkey = $key . '_user';
                    $ret = User::info($value);
                }
                break;
            case 'radio_prop':
            case 'checkbox_prop':
            case 'multi_prop':
            case 'prop':
                if ($key == 'pid') {
                    break;
                }
                $nkey = $key . '_prop';
                $propArray = propApp::field($key, $app);
                // empty($ret['prop']) && $propArray = propApp::value($key);
                if ($field['type'] == 'multi_prop' || $field['type'] == 'checkbox_prop') {
                    $valArray = explode(',', $value);
                    if ($propArray) {
                        foreach ($propArray as $i => $val) {
                            if (in_array($val['val'], $valArray)) {
                                $ret[$val['val']] = $val;
                            }
                        }
                    }
                } else {
                    $ret = $propArray[$value];
                }
                empty($ret) && $ret = [];
                $field['option'] = null;
                break;
            case 'tag':
                $vars['tag'] && TagApp::getArray($rs, $node['name'], $key, $value);
                break;
            case 'editor':
                if ($value) {
                    $rs[$key . '_pics'] = FilesPic::findImgUrl($value, $pic_array);
                }
                break;
            case 'markdown':
                $rs[$key] = $value;
                if ($vars['markdown'] !== false) {
                    if ($value) {
                        $rs[$key] = PluginMarkdown::parser($value);
                    }
                }
                break;
            case 'kv':
            case 'json':
                if ($value) {
                    $rs[$key] = json_decode($value, true);
                }
                break;
            default:
                // $ret = $value;
                break;
        }
        if ($field['option'] && !in_array($key, ['creative', 'status'])) {
            $nkey = $key . '_array';
            $optionArray = explode(';', $field['option']);
            $valArray = explode(',', $value);
            foreach ($optionArray as $ok => $val) {
                $val = trim($val, "\r\n");
                if ($val) {
                    list($opt_text, $opt_value) = explode('=', $val);
                    $option_array[$key][$opt_value] = $opt_text;
                    // $ret['option'][$opt_value] = $opt_text;
                    if ($field['multiple']) {
                        if (in_array($opt_value, $valArray)) {
                            $ret[$opt_value] = $opt_text;
                        }
                    } else {
                        if ($opt_value == $value) {
                            $nkey = $key . '_value';
                            $ret = $opt_text;
                            break;
                        }
                    }
                }
            }
        }
        $nkey && $rs[$nkey] = $ret;
        $option_array && View::assign('option_array', $option_array);
    }
}
