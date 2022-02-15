<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class AppsCommon
{
    public static $primary = 'id';
    public static $data    = array();
    public static $vars    = array();
    public static $app    = null;
    public static $routeKey  = 'api';

    public static function init(&$data, $vars = null, $primary = 'id')
    {
        self::$data    = &$data;
        self::$app     = $data['app']['app'];
        self::$vars    = $vars;
        self::$primary = $primary;
        return new self();
    }
    // public function all()
    // {
    //     $this->link()->text2link()->user()->comment()->pic()->hits()->param()->fields();
    //     return $this;
    // }
    public static function setRouteKey($key = null)
    {
        self::$routeKey = $key;
    }
    public function link($title = null)
    {
        $title === null && $title = self::$data['title'];
        self::$data['link']  = sprintf(
            '<a href="%s" class="%s_link" target="_blank">%s</a>',
            self::$data['url'],
            self::$app,
            $title
        );
        return $this;
    }
    public function text2link()
    {
        self::$data['source'] = text2link(self::$data['source']);
        self::$data['author'] = text2link(self::$data['author']);
        return $this;
    }

    public function comment()
    {
        $url = sprintf(
            "%s?app=%s&do=comment&appid=%d&cid=%d&iid=%d",
            Route::routing(self::$routeKey),
            self::$app,
            self::$data['appid'],
            self::$data['cid'],
            self::$data[self::$primary]
        );
        self::$data['comment_array'] = array(
            'url' => $url,
            'count' => self::$data['comment'],
        );
        return $this;
    }
    public function pic()
    {
        $picArray = array();
        isset(self::$data['picdata']) && $picArray = self::$data['picdata'];

        if (isset(self::$data['pic'])) {
            self::$data['pic']  = FilesPic::getArray(
                self::$data['pic'],
                $picArray['p'],
                [self::$vars['ptw'], self::$vars['pth']]
            );
        }
        $sizeMap = array('b', 'm', 's');
        foreach ($sizeMap as $key => $size) {
            $k = $size . 'pic';
            if (isset(self::$data[$k])) {
                self::$data[$k] = FilesPic::getArray(
                    self::$data[$k],
                    $picArray[$size],
                    [self::$vars[$size . 'tw'], self::$vars[$size . 'th']]
                );
            }
        }
        unset(self::$data['picdata'], $picArray);
        return $this;
    }
    public function user()
    {
        $author = self::$data['author'];
        self::$data['postype'] && $author = self::$data['editor'];
        self::$data['user'] = User::info(self::$data['userid'], $author);
        return $this;
    }
    public function hits()
    {
        $url = sprintf(
            '%s?app=%s&do=hits&cid=%d&id=%d',
            Route::routing(self::$routeKey),
            self::$app,
            self::$data['cid'],
            self::$data[self::$primary]
        );
        self::$data['hits'] = array(
            'script' => $url,
            'count'  => self::$data['hits'],
            'today'  => self::$data['hits_today'],
            'yday'   => self::$data['hits_yday'],
            'week'   => self::$data['hits_week'],
            'month'  => self::$data['hits_month'],
        );
        return $this;
    }
    public function param($title = null)
    {
        $title === null && $title = self::$data['title'];
        self::$data['param'] = array(
            "appid"    => self::$data['appid'],
            "app"      => self::$app,
            "id"       => self::$data['id'],
            "iid"      => self::$data['id'],
            "cid"      => self::$data['cid'],
            "userid"   => self::$data['user']['uid'],
            "username" => self::$data['user']['name'],
            "url"      => self::$data['url'],
            "title"    => $title,
        );
        return $this;
    }

    public function fields()
    {
        if (is_array(self::$data)) {
            $app  = Apps::getData(self::$app);
            self::$data['SAPP'] = Apps::getDataLite($app);
            $app['fields'] && FormerApp::data(self::$data['id'], $app, self::$app, self::$data, self::$vars, self::$data['node']);
        }
    }
}
