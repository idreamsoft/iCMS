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
    public static $data = [];
    public static $vars = [];
    public static $app = null;

    public static function init(&$data, $vars = null, $primary = 'id')
    {
        self::$data = &$data;
        self::$app = $data['APP']['app'];
        self::$vars = $vars;
        self::$primary = $primary;
        return new self();
    }

    // public function all()
    // {
    //     $this->link()->text2link()->user()->comment()->pic()->hits()->param()->fields();
    //     return $this;
    // }

    public function link($title = null)
    {
        $title === null && $title = self::$data['title'];
        self::$data['link'] = sprintf(
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
        $url = sprintf('{API}/%s/comment', self::$app);
        $query = sprintf(
            'appid=%d&cid=%d&iid=%d',
            self::$data['appid'],
            self::$data['cid'],
            self::$data[self::$primary]
        );
        $url = Route::create($url,$query);

        self::$data['comment'] = [
            'url'   => $url,
            'count' => self::$data['comment'],
        ];
        return $this;
    }

    public function pic()
    {
        $picArray = [];
        isset(self::$data['picdata']) && $picArray = self::$data['picdata'];

        if (isset(self::$data['pic']) && is_string(self::$data['pic'])) {
            if (strpos(self::$data['pic'], ',') !== false) {
                $picArray = explode(',', self::$data['pic']);
                self::$data['gallery'] = [];
                foreach ($picArray as $key => $pic) {
                    self::$data['gallery'][] = FilesPic::getArray(
                        $pic,
                        $picArray['p'],
                        [self::$vars['ptw'], self::$vars['pth']]
                    );
                }
                self::$data['pic'] = self::$data['gallery'][0];
            } else {
                self::$data['pic'] = FilesPic::getArray(
                    self::$data['pic'],
                    $picArray['p'],
                    [self::$vars['ptw'], self::$vars['pth']]
                );
            }
        }
        $sizeMap = ['b', 'm', 's'];
        foreach ($sizeMap as $key => $size) {
            $k = $size . 'pic';
            if (isset(self::$data[$k]) && is_string(self::$data[$k])) {
                $ak = $k.'_array';
                self::$data[$ak] = (array)FilesPic::getArray(
                    self::$data[$k],
                    $picArray[$size],
                    [self::$vars[$size . 'tw'], self::$vars[$size . 'th']],
                    true
                );
                self::$data[$k] = self::$data[$ak][0];
            }
        }
        unset(self::$data['picdata'], $picArray);
        return $this;
    }

    public function user()
    {
        $name = self::$data['author'];
        empty($author) && $name = self::$data['editor'];
        self::$data['user'] = User::info(self::$data['userid'], $name);
        return $this;
    }

    public function hits()
    {
        $url = sprintf('{API}/%s/hits', self::$app);
        $query = sprintf('cid=%d&id=%d', self::$data['cid'], self::$data[self::$primary]);
        $url = Route::create($url, $query);
        self::$data['hits'] = [
            'script' => $url,
            'count'  => self::$data['hits'],
            'today'  => self::$data['hits_today'],
            'yday'   => self::$data['hits_yday'],
            'week'   => self::$data['hits_week'],
            'month'  => self::$data['hits_month'],
        ];
        return $this;
    }

    public function params($title = null)
    {
        $title === null && $title = self::$data['title'];
        self::$data['params'] = [
            'appid'    => self::$data['appid'],
            'app'      => self::$app,
            'id'       => self::$data['id'],
            'iid'      => self::$data['id'],
            'cid'      => self::$data['cid'],
            'userid'   => self::$data['user']['id'],
            'username' => self::$data['user']['name'],
            'url'      => self::$data['url'],
            'title'    => $title,
        ];
        // self::$data['params_code'] = Crypt::encrypt(self::$data['params']);
        return $this;
    }

    public function fields()
    {
        if (is_array(self::$data)) {
            $app = Apps::getData(self::$app);
            self::$data['SAPP'] = Apps::getDataLite($app);
            $app['fields'] && FormerApp::data(self::$data['id'], $app, self::$app, self::$data, self::$vars, self::$data['node']);
        }
    }
}
