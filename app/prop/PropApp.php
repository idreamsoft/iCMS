<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class PropApp
{
    public $methods = array(iPHP_APP);

    public static function value($field = null, $app = null, $sort = true)
    {
        $app  && $pieces[] = $app;
        $field && $pieces[] = $field;
        if (empty($pieces)) return false;

        $keys = implode('/', $pieces);
        $propArray     = Cache::get("prop/{$keys}");
        $propArray && $sort && sort($propArray);
        return $propArray;
    }
    public static function field($field, $app = null)
    {
        $variable = self::value($field, $app, false);
        $propArray = array();
        if ($variable) foreach ($variable as $key => $value) {
            $propArray[$value['val']] = $value;
        }
        return $propArray;
    }
    public static function app($app)
    {
        return self::value(null, $app, false);
    }
    public static function url($value, $url = null)
    {
        $query = array();
        $query[$value['field']] = $value['val'];
        return Route::make($query, $url);
    }
    public static function items($vars, $variable)
    {
        foreach ($variable as $key => $value) {
            if ($vars['field']) {
                $value['url'] = PropApp::url($value, $vars['url']);
                $value['link'] = '<a href="' . $value['url'] . '" />' . $value['name'] . '</a>';
            } else {
                foreach ($value as $k => $v) {
                    $v['url'] = PropApp::url($v, $vars['url']);
                    $v['link'] = '<a href="' . $v['url'] . '" />' . $v['name'] . '</a>';
                    $value[$k] = $v;
                }
            }
            $variable[$key] = $value;
        }
        return $variable;
    }
}
