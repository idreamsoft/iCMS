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
    public function __construct()
    {
        $this->appid = iCMS_APP_FORMER;
    }

    /**
     * [创建表单]
     * @param  Array  $app        [app数据/appid]
     * @param  [type]  $rs         [数据]
     * @return [type]              [description]
     */
    public static function add($app, $rs, $flag = null)
    {
        Former::$APP = $app;
        if ($app['fields']) {
            // Former::set_template_class(array(
            //     'group'    => 'input-prepend input-append',
            //     'label'    => 'add-on',
            //     'label2'   => 'add-on',
            //     'radio'    => 'add-on',
            //     'checkbox' => 'add-on',
            // ));

            Former::$GATEWAY = 1;
            Former::$config['option'] = true;
            Former::create($app, $rs);
        }
    }
    /**
     * [保存表单]
     * @param  [type] $app    [app数据/appid]
     * @param  [type] $id [主键值]
     * @return [type]         [description]
     */
    public static function save($app, $id = null, &$flag = null)
    {
        if ($app['fields']) {
            $appid = (int)Request::post('appid');
            $postdata = Former::postData($app);
            extract($postdata);
            $origData = Content::save($id, $content, $contentData);
            return $content;
        } else {
            iPHP::throwError('Fields Not Found');
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
                    $where[ContentDataModel::$unionKey] = $id;
                    $data = ContentDataModel::getData($where);
                    is_array($data) && $resource = array_merge($resource, $data);
                }
            }
            foreach ((array) $fieldArray as $fkey => $fields) {
                self::vars($fields, $fkey, $resource, $vars, $node, $name);
            }
        }
    }
    public static function vars($field, $key, &$rs, $vars = null, $node = null, $app = null)
    {
        $option_array = array();
        $value        = $rs[$key];
        $ret          = array();
        $nkey         = null;
        switch ($field['type']) {
            case 'multi_image':
                $nkey     = $key . '_array';
                // $valArray = unserialize($value);
                if (!is_array($value)) {
                    // if (preg_match('/^a:\d+:\{/', $value)) {
                    if (substr($value, 0, 2) == 'a:') {
                        $valArray = unserialize($value);
                    } else {
                        $valArray = json_decode($value, true);
                    }
                    if ($value && empty($valArray)) {
                        $valArray = explode("\n", $value);
                    }
                } else {
                    $valArray = $value;
                }
                if (is_array($valArray)) foreach ($valArray as $i => $val) {
                    $val && $ret[$i] = FilesPic::getArray(trim($val));
                }
                break;
            case 'image':
                $nkey   = $key . '_array';
                $ret = FilesPic::getArray($value);
                break;
            case 'file':
                $nkey = $key . '_file';
                $pi   = pathinfo($value);
                $ret   = array(
                    'name' => $pi['filename'],
                    'ext'  => $pi['extension'],
                    'dir'  => $pi['dirname'],
                    'url'  => FilesApp::getUrl($pi['filename'])
                );
                break;
            case 'multi_file':
                $nkey = $key . '_file';
                // $valArray = unserialize($value);
                // if (preg_match('/^a:\d+:\{/', $value)) {
                if (substr($value, 0, 2) == 'a:') {
                    $valArray = unserialize($value);
                } else {
                    $valArray = json_decode($value, true);
                }
                if ($value && empty($valArray)) {
                    $valArray = explode("\n", $value);
                }
                if (is_array($valArray)) foreach ($valArray as $i => $val) {
                    if ($val) {
                        $pi   = pathinfo($val);
                        $ret[$i]   = array(
                            'name' => $pi['filename'],
                            'ext'  => $pi['extension'],
                            'dir'  => $pi['dirname'],
                            'url'  => FilesApp::getUrl($pi['filename'])
                        );
                    }
                }
                break;
            case 'node':
                if ($key == 'cid') {
                    break;
                }
                $nkey = $key . '_node';
                $ret = NodeCache::getId($value);
                break;
            case 'multi_node':
                $nkey   = $key . '_node';
                $valArray = explode(",", $value);
                foreach ($valArray as $i => $val) {
                    $ret[$i] = NodeCache::getId($val);
                }
                break;
            case 'userid':
                if ($vars['user']) {
                    $nkey   = $key . '_user';
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
                $nkey   = $key . '_prop';
                $propArray = propApp::field($key, $app);
                // empty($ret['prop']) && $propArray = propApp::value($key);
                if ($field['type'] == 'multi_prop' || $field['type'] == 'checkbox_prop') {
                    $valArray = explode(",", $value);
                    if ($propArray) foreach ($propArray as $i => $val) {
                        if (in_array($val['val'], $valArray)) {
                            $ret[$val['val']] = $val;
                        }
                    }
                } else {
                    $ret = $propArray[$value];
                }
                empty($ret) && $ret = array();
                $field['option'] = null;
                break;
            case 'tag':
                $vars['tag'] && TagApp::getArray($rs, $node['name'], $key, $value);
                break;
            case 'editor';
                if ($value) {
                    $rs[$key . '_pics'] = FilesPic::findImgUrl($value, $pic_array);
                }
                break;
            case 'markdown';
                if ($value) {
                    $rs[$key] = PluginMarkdown::parser($value);
                }
                break;
            case 'json';
                if ($value) {
                    $rs[$key]  = json_decode($value, true);
                }
                break;
            default:
                // $ret = $value;
                break;
        }
        if ($field['option'] && !in_array($key, array('creative', 'status'))) {
            $nkey = $key . '_array';
            $optionArray = explode(";", $field['option']);
            $valArray = explode(",", $value);
            foreach ($optionArray as $ok => $val) {
                $val = trim($val, "\r\n");
                if ($val) {
                    list($opt_text, $opt_value) = explode("=", $val);
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
