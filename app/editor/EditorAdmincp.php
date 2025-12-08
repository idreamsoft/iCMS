<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
/**
 * @auth no
 */
class EditorAdmincp extends AdmincpBase
{
    public function do_ueai()
    {
        ConfigView::display(self::$APPID, 'ueditor.ai');
    }
    public function done_config (){
        $data = Request::post('config');
        $name  = Request::post('config_name');
        Config::save(self::$APPID, $name, $data);
    }

    /**
     * 获取编辑器下拉数据
     *
     * @auth no
     * @return void
     */
    public function done_selectpage()
    {
        $arr = self::jsonScan('*/package', 'editor');
        if ($arr) {
            foreach ($arr as $key => $value) {
                $list[] = iJson::listItem($value['name'], $value['markdown'], $value['title']);
            }
        }
        return iJson::listData($list);
    }

    /**
     * 编辑器配置加载
     *
     * @auth no
     * @return void
     */
    public function done_load()
    {
        return Editor::loadjs();
    }
}
