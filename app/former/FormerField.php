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

class FormerField
{
    public static $DATAS = [
        ["text"=>"隐藏字段","tag" => "input", "type" => "hidden", "field" => "VARCHAR", "len" => "255", "label" => "隐藏字段", "icon" => "input"],
        ["text"=>"单行字符串","tag" => "input", "type" => "text", "field" => "VARCHAR", "len" => "255", "label" => "字符串", "icon" => "input"],
        ["text"=>"单行长字符串","tag" => "input", "type" => "text", "field" => "VARCHAR", "len" => "5120", "label" => "长字符串", "icon" => "input"],
        ["text"=>"多行字符串","tag" => "textarea", "type" => "textarea", "field" => "TEXT", "label" => "多行", "icon" => "textarea"],
        ["text"=>"json","tag" => "input", "type" => "json", "field" => "VARCHAR", "len" => "5120", "label" => "json", "icon" => "input"],
        ["text"=>"邮箱","tag" => "input", "type" => "text", "field" => "VARCHAR", "len" => "255", "label" => "邮箱", "icon" => "mail"],
        ["text"=>"日期","tag" => "input", "type" => "date", "field" => "INT", "len" => "10", "label" => "日期", "icon" => "date"],
        ["text"=>"日期时间","tag" => "input", "type" => "datetime", "field" => "INT", "len" => "10", "label" => "时间", "icon" => "datetime"],
        ["text"=>"单选框","tag" => "input", "type" => "radio", "field" => "VARCHAR", "len" => "255", "label" => "单选", "icon" => "radio"],
        ["text"=>"复选框","tag" => "input", "type" => "checkbox", "field" => "VARCHAR", "len" => "255", "label" => "复选", "icon" => "checkbox"],
        ["text"=>"下拉列表","tag" => "select", "type" => "select", "field" => "VARCHAR", "len" => "255", "label" => "列表", "icon" => "dropdown"],
        ["text"=>"多选列表","tag" => "select", "type" => "multiple", "field" => "VARCHAR", "len" => "255", "label" => "多选", "icon" => "multiselect"],
        ["text"=>"数字","tag" => "input", "type" => "number", "field" => "TINYINT", "len" => "1", "label" => "数字", "icon" => "number"],
        ["text"=>"大数字","tag" => "input", "type" => "number", "field" => "INT", "len" => "10", "label" => "大数字", "icon" => "number"],
        ["text"=>"超大数字","tag" => "input", "type" => "number", "field" => "BIGINT", "len" => "20", "label" => "超大数字", "icon" => "number"],
        ["text"=>"小数","tag" => "input", "type" => "decimal", "field" => "DECIMAL", "len" => "6,2", "label" => "小数", "icon" => "decimal"],
        ["text"=>"百分比","tag" => "input", "type" => "percentage", "field" => "VARCHAR", "len" => "10", "label" => "百分比", "label-after" => "%", "icon" => "percentage"],
        ["text"=>"货币","tag" => "input", "type" => "currency", "field" => "INT", "len" => "10", "label" => "货币", "label-after" => "¥", "icon" => "currency"],
        ["text"=>"Url","tag" => "input", "type" => "text", "field" => "VARCHAR", "len" => "255", "label" => "链接", "icon" => "url"],
        
        ["text"=>"选择框-模板目录","tag" => "tpldir", "type" => "tpldir", "field" => "VARCHAR", "len" => "255", "label" => "模板目录", "icon" => "template"],
        ["text"=>"选择框-模板文件","tag" => "tplfile", "type" => "tplfile", "field" => "VARCHAR", "len" => "255", "label" => "模板文件", "icon" => "template"],
        ["text"=>"栏目","tag" => "node", "type" => "node", "field" => "INT", "len" => "10", "label" => "栏目", "icon" => "multiselect"],
        ["text"=>"栏目(多选)","tag" => "multi_node", "type" => "multi_node", "field" => "VARCHAR", "len" => "255", "label" => "多选栏目", "icon" => "multiselect"],
        ["text"=>"图片上传","tag" => "image", "type" => "image", "field" => "VARCHAR", "len" => "255", "label" => "图片", "icon" => "image"],
        ["text"=>"多图上传","tag" => "multi_image", "type" => "multi_image", "field" => "TEXT", "label" => "多图", "icon" => "image"],
        ["text"=>"上传文件","tag" => "file", "type" => "file", "field" => "VARCHAR", "len" => "255", "label" => "上传", "icon" => "fileupload"],
        ["text"=>"批量上传","tag" => "multi_file", "type" => "multi_file", "field" => "TEXT", "label" => "批量上传", "icon" => "fileupload"],
        ["text"=>"属性(下拉列表)","tag" => "prop", "type" => "prop", "field" => "VARCHAR", "len" => "255", "label" => "属性", "icon" => "prop"],
        ["text"=>"属性(多选列表)","tag" => "multi_prop", "type" => "multi_prop", "field" => "VARCHAR", "len" => "255", "label" => "多选属性", "icon" => "prop"],
        ["text"=>"属性(单选框)","tag" => "multi_prop", "type" => "radio_prop", "field" => "VARCHAR", "len" => "200", "label" => "单选属性", "icon" => "prop"],
        ["text"=>"属性(复选框)","tag" => "multi_prop", "type" => "checkbox_prop", "field" => "VARCHAR", "len" => "255", "label" => "复选属性", "icon" => "prop"],
        ["text"=>"标签","tag" => "tag", "type" => "tag", "field" => "VARCHAR", "len" => "255", "label" => "标签", "ui-class" => "form-control", "icon" => "tag"],
        ["text"=>"用户名","tag" => "username", "type" => "username", "field" => "VARCHAR", "len" => "255", "label" => "用户名", "icon" => "username"],
        ["text"=>"用户昵称","tag" => "nickname", "type" => "nickname", "field" => "VARCHAR", "len" => "255", "label" => "用户昵称", "icon" => "username"],
        ["text"=>"用户ID","tag" => "userid", "type" => "userid", "field" => "INT", "len" => "10", "label" => "用户ID", "icon" => "userid"],
        ["text"=>"关联父应用ID","tag" => "relation:id", "type" => "relation:id", "field" => "INT", "len" => "10", "label" => "内容ID", "icon" => "fa fa-fw fa-link"],
        ["text"=>"IP地址","tag" => "ip", "type" => "ip:hidden", "field" => "VARCHAR", "len" => "255", "label" => "IP地址", "icon" => "username"],
        ["text"=>"来路","tag" => "referer", "type" => "referer:hidden", "field" => "VARCHAR", "len" => "255", "label" => "来路", "icon" => "username"],
        ["text"=>"验证码","tag" => "captcha", "type" => "captcha", "len" => "8", "label" => "验证码", "icon" => "url"],
        ["text"=>"大文本","tag" => "textarea", "type" => "multitext", "field" => "MEDIUMTEXT", "label" => "大文本", "icon" => "textarea"],
        ["text"=>"编辑器","tag" => "editor", "type" => "editor", "field" => "MEDIUMTEXT", "label" => "编辑器", "icon" => "richtext"],
        ["text"=>"markdown","tag" => "markdown", "type" => "markdown", "field" => "MEDIUMTEXT", "label" => "md编辑器", "icon" => "richtext"],
    ];
}
