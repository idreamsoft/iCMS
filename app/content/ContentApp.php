<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ContentApp extends AppsBase
{
    use AppsAppTrait;
    public $tables = null;
    public $table = null;

    public function __construct($data = null)
    {
        if ($data === null) {
            $id = Request::param('appid');
            $data = Apps::getData($id);
        } elseif (!is_array($data)) {
            $data = Apps::getData($data);
        }

        Content::$APP_DATA = $data;
        Content::$APP = $data['app'];
        Content::$APPID = $data['id'];

        $model = Content::model($data);
        $this->id = (int) Request::get('id');
        parent::__construct();
        $this->inited('content', 'id', $model);
        unset($data);
    }

    public function display($value, $field = 'id', $tpl = true)
    {
        $vars = ['tag'  => true, 'user' => true];
        $content = $this->getData($value, $field);
        Content::cdata($content);
        self::values($content, $vars, $tpl);
        // //自定义应用模板信息
        // Content::$APP_DATA['apptype'] == Apps::CONTENT_TYPE && Content::setAppData(Content::$APP_DATA);
        return self::render($content, $tpl);
    }

    public static function values(&$content, $vars = [], $tpl = false)
    {
        self::initialize($content, $tpl);

        $vars['tag'] && TagApp::getArray($content, $content['node']['name'], 'tags');

        $content['statusText'] = ContentModel::$statusMap[$content['status']];
        $content['postypeText'] = ContentModel::$postypeMap[$content['postype']];

        self::getCustomData($content, $vars);
        // if (Content::$APP_DATA['fields']) {
        //     $fields = Former::fields(Content::$APP_DATA['fields']);
        //     foreach ((array) $fields as $key => $field) {
        //         FormerApp::vars($field, $key, $content, $vars, $content['node'], Content::$APP);
        //     }
        // }

        AppsCommon::init($content, $vars)
            ->link()
            ->text2link()
            ->user()
            ->comment()
            ->pic()
            ->hits()
            ->params();

        return $content;
    }

    public function data($ids = 0, &$content = [])
    {
        if (empty($ids)) {
            return [];
        }
        if (ContentDataModel::$unionKey) {
            $where[ContentDataModel::$unionKey] = $ids;
            $model = ContentDataModel::where($where);
            // $data = ContentDataModel::getData($where);
            // is_array($data) && $content = array_merge($content, $data);
        }
        if (is_numeric($ids)) {
            $result = $model->get();
        } else {
            $result = $model->select();
            $result = array_column($result, null, ContentDataModel::$unionKey);
            // $result = array_map([__CLASS__, 'item'], $result);
        }
        return $result;
    }
}
