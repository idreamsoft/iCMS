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

class SpiderProject
{

    public static function get($id)
    {
        $key = 'spider:project:' . $id;
        return Cache::temp($key, function () use ($id) {
            $data = SpiderProjectModel::get($id);
            $data += $data['config'];
            return $data;
        });
    }
    public static function option($id = 0, &$output = null)
    {
        $rs = SpiderProjectModel::select();
        $opt = '';
        $output = array();
        if (is_array($rs)) foreach ($rs as $proj) {
            $output[$proj['id']] = $proj['name'];
            $selected = ($id == $proj['id'] ? "selected" : '');
            $opt .= sprintf(
                '<option value="%s" %s>%s[id:="%s"]</option>',
                $proj['id'],
                $selected,
                $proj['name'],
                $proj['id']
            );
        }
        return $opt;
    }
}
