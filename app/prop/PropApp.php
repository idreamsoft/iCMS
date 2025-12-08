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
    public $methods = [iPHP_APP];

    public static function value($field = null, $app = null, $sort = true)
    {
        $app && $pieces[] = $app;
        $field && $pieces[] = $field;
        if (empty($pieces)) {
            return false;
        }

        $keys = implode('/', $pieces);
        $propArray = Cache::get(sprintf('%s/%s', Prop::PREFIX, $keys));
        $propArray && $sort && sort($propArray);
        return $propArray;
    }

    public static function field($field, $app = null)
    {
        $variable = self::value($field, $app, false);
        $propArray = [];
        if ($variable) {
            foreach ($variable as $key => $value) {
                $propArray[$value['val']] = $value;
            }
        }
        return $propArray;
    }

    public static function app($app)
    {
        return self::value(null, $app, false);
    }

    public static function url($value, $url = null)
    {
        $query = [];
        $query[$value['field']] = $value['val'];
        return Route::make($query, $url);
    }
    
    public static function item($vars, &$value)
    {
        $value['url'] = PropApp::url($value, $vars['url']);
        $value['link'] = sprintf('<a href="%s">%s</a>',$value['url'],$value['name']) ;
        $value['hash'] = md5($value['name'] . $value['val']);
        //兼容
        $value['value'] = $value['val'];
        $value['text'] = $value['name'];
    }

    public static function items($vars, &$resource = [])
    {
        !isset($vars['judge']) && $vars['judge'] = Request::get();
        foreach ($resource as $key => &$value) {
            if ($vars['field'] or isset($value['id'])) {
                self::item($vars, $value);
            } else {
                foreach ($value as $k => &$v) {
                    self::item($vars, $v);
                }
            }
        }
        return $resource;
    }
}
