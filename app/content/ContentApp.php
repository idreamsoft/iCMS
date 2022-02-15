<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ContentApp extends AppsApp
{
    public $tables  = null;
    public $table   = null;

    public function __construct($data = null)
    {
        if ($data === null) {
            $id = Request::param('appid');
            $data = Apps::getData($id);
        } else if (!is_array($data)) {
            $data = Apps::getData($data);
        }

        self::$APPDATA = $data;
        self::$_app    = $data['app'];
        self::$appid   = $data['id'];

        $model = Content::model($data);
        $this->id = (int) Request::get(Content::$primaryKey);
        parent::__construct('content', Content::$primaryKey, $model);
        unset($data);
    }

    public function display($value, $field = 'id', $tpl = true)
    {
        try {
            $vars = ['tag'  => true, 'user' => true];
            $content = $this->getData($value, $field);
            if (ContentDataModel::$unionKey) {
                $where[ContentDataModel::$unionKey] = $content['id'];
                $ContentData = ContentDataModel::getData($where);
                is_array($ContentData) && $content = array_merge($content, $ContentData);
            }            
            self::values($content, $vars, $tpl);
            self::getCustomData($content, $vars);
            self::hooked($content);
            //自定义应用模板信息
            self::$APPDATA['type'] == "2" && contentFunc::interfaced(self::$APPDATA);
            return self::render($content, $tpl);
        } catch (\FalseEx $ex) {
            // var_dump($ex->getMessage());
            return false;
        }
    }
    public static function values(&$content, $vars = array(), $tpl = false)
    {
        self::initialize($content, $tpl);

        $vars['tag'] && TagApp::getArray($content, $content['node']['name'], 'tags');

        $content['statusText']  = Content::$statusMap[$content['status']];
        $content['postypeText'] = Content::$postypeMap[$content['postype']];

        if (self::$APPDATA['fields']) {
            $fields = Former::fields(self::$APPDATA['fields']);
            foreach ((array) $fields as $key => $field) {
                FormerApp::vars($field, $key, $content, $vars, $content['node'], self::$_app);
            }
        }
        AppsCommon::init($content, $vars)
            ->link()
            ->text2link()
            ->user()
            ->comment()
            ->pic()
            ->hits()
            ->param();

        return $content;
    }

    public function data($ids = 0, &$content = [])
    {
        if (empty($ids)) return array();
        if (ContentDataModel::$unionKey) {
            $where[ContentDataModel::$unionKey] = $ids;
            $model = ContentDataModel::where($where);
            // $data = ContentDataModel::getData($where);
            // is_array($data) && $content = array_merge($content, $data);
        }
        if (is_numeric($ids)) {
            $result = $model->get();
        } else {
            $result  = $model->select();
            $result = array_column($result, null, ContentDataModel::$unionKey);
            // $result = array_map([__CLASS__, 'item'], $result);
        }
        return $result;
    }
    /**
     * [iAPP::run回调]
     * @param  [type] $app [description]
     * @return [type]      [description]
     */
    public static function run($app)
    {
        $data = Apps::getData($app);
        if ($data) {
            iPHP::callback(iApp::$callback['begin']);
            iAPP::$PATH = iAPP::path('content');
            iAPP::$FILE = iAPP::$PATH . '/ContentApp.php';
            iAPP::$CLASS = __CLASS__;
            iAPP::$INSTANCE = new self($data);
        } else {
            self::throwError('Unable to find custom application <b>' . $app . '</b>', '0003');
        }
    }
}
