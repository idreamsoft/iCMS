<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PropWidget
{
    public static $app   = null;
    public static $field = null;

    public static function app($app)
    {
        $self = new self;
        $self::$app = $app;
        return $self;
    }

    public static function option($array, $field, $sd = null)
    {
        $opt = '';
        foreach ($array as $key => $value) {
            $selected = null;
            if ($sd === true || $sd == $key) {
                $selected = ' selected';
            }
            $opt .= sprintf(
                '<option value="%s" %s>%s[%s:="%s"]</option>',
                $key,
                $selected,
                $value,
                $field,
                $key
            );
        }
        $opt .= self::getOption($field);
        return $opt;
    }
    public static function btn($title, $field = null, $app = null, $class = "btn btn-alt-primary", $text = '<i class="fa fa-fw fa-plus"></i>')
    {
        $app or $app = Admincp::$APP_NAME;
        $field or $field = self::$field;
        $text or $text = $title;
        // $class = "btn btn-block btn-square btn-dark";
        ob_start();
        include AdmincpView::display("widget/btn", "prop");
        return AdmincpView::html();
    }
    public static function btnGroup($field, $title = '属性', $target = null, $app = null)
    {
        self::$field = $field;
        $app or $app = Admincp::$APP_NAME;
        $target or $target = $field;
        $propArray = Cache::get("prop/{$app}/{$field}");
        ob_start();
        include AdmincpView::display("widget/btnGroup", "prop");
        return AdmincpView::html();
    }
    public static function select($field, $target = null, $class = 'span3', $title = '请选择或填写', $app = null)
    {
        self::$field = $field;
        $app or $app = Admincp::$APP_NAME;
        $target or $target = $field;
        $propArray = Cache::get("prop/{$app}/{$field}");

        $div = '<select data-toggle="select_insert"
                data-target="#' . $target . '"
                class="chosen-select ' . $class . '"
                data-placeholder="' . $title . '">';
        $div .= '<option></option>';
        if ($propArray) foreach ((array) $propArray as $prop) {
            $div .= sprintf(
                '<option value="%s">%s</option>',
                $prop['val'],
                $prop['name']
            );
        }
        $div .= '</select>';
        return $div;
    }
    /**
     * @return Array|String
     */
    public static function get(
        $field,
        $valArray = NULL,
        $out = 'option',
        $url = "",
        $app = "",
        $isopt = true
    ) {
        self::$field = $field;
        $app or $app = Admincp::$APP_NAME;
        self::$app && $app = self::$app;
        is_array($valArray) or $valArray  = explode(',', $valArray);
        $opt = array();
        $propArray = Cache::get("prop/{$app}/{$field}");
        // empty($propArray) && $propArray = Cache::get("prop/{$field}");
        if ($propArray) foreach ((array) $propArray as $k => $P) {
            if ($out == 'option') {
                $selected = array_search($P['val'], $valArray) !== FALSE ? 'selected' : '';
                $isopt && $optText = "[{$field}='{$P['val']}']";
                $opt[] = sprintf(
                    '<option value="%s" title="%s=%s" %s>%s %s</option>',
                    $P['val'],
                    $field,
                    $P['val'],
                    $selected,
                    $P['name'],
                    $optText
                );
            } elseif ($out == 'text') {
                // if (array_search($P['val'],$valArray)!==FALSE) {
                if (array_search($P['val'], $valArray) !== FALSE) {
                    $flag = '<i class="fa fa-fw fa-flag"></i> ' . $P['name'];
                    $opt[] = ($url ? '<a href="' . str_replace('{id}', $P['val'], $url) . '">' . $flag . '</a>' : $flag) . '<br />';
                }
            }
        }
        return implode('', $opt);
    }
    public static function getOption($field, $valArray = NULL, $isopt = true)
    {
        self::$field = $field;
        $app = Admincp::$APP_NAME;
        self::$app && $app = self::$app;
        is_array($valArray) or $valArray  = explode(',', $valArray);
        $opt = array();
        $propArray = Cache::get("prop/{$app}/{$field}");
        // empty($propArray) && $propArray = Cache::get("prop/{$field}");
        if ($propArray) foreach ((array) $propArray as $k => $P) {
            $selected = array_search($P['val'], $valArray) !== FALSE ? 'selected' : '';
            $isopt && $optText = "[{$field}='{$P['val']}']";
            $opt[] = sprintf(
                '<option value="%s" title="%s=%s" %s>%s %s</option>',
                $P['val'],
                $field,
                $P['val'],
                $selected,
                $P['name'],
                $optText
            );
        }
        return implode('', $opt);
    }
    public static function flag($pids, $field = null)
    {
        empty(Prop::$DATA) && Prop::get("pid");
        $pidArray = is_string($pids) ? explode(',', $pids) : $pids;
        foreach ((array) $pidArray as $key => $id) {
            $name = Prop::$DATA[$id];
            if ($id != '0') {
                $url = Route::make($field . '=' . $id);
                $flag = '<i class="fa fa-fw fa-flag"></i> ' . $name;
                echo ($url ? '<a href="' . $url . '">' . $flag . '</a>' : $flag) . '<br />';
            }
        }
    }
}
