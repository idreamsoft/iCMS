<?php

/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
class Paging
{
    public static $config   = array();
    public static $callback = array();

    public static $rownum  = 0;
    public static $pageNav = NULL;
    public static $pageSize  = 20;
    public static $count   = 0;
    public static $lastId  = 0;
    public static $INSTANCE  = NULL;

    public static $total_cache = 'G';

    public static function setLastId($id)
    {
        self::$lastId = $id;
    }
    public static function getLastId()
    {
        if (self::$lastId) {
            return self::$lastId;
        }
        $lastId = Request::get('lastId');
        return is_null($lastId) ? null : (int)$lastId;
    }
    public static function setRownum($a)
    {
        self::$rownum = is_array($a) ? count($a) : $a;
    }
    public static function getPageSize($default = 20)
    {
        self::$pageSize = Request::get('pageSize') > 0 ? (int)Request::get('pageSize') : $default;
        return self::$pageSize;
    }
    public static function getTotal()
    {
        return (int)Request::get('pageTotal');
    }

    //分页数缓存
    public static function totalCache($call, $hash = null, $type = null, $cachetime = 3600)
    {
        $total = (int) Request::get('pageTotal');
        if ($type == "G") {
            empty($total) && $total = call_user_func($call);
        } else {
            $cacheKey = 'page_total/' . substr($hash, 8, 16);
            $total = Cache::get($cacheKey);
            if (is_null($total)||$total===false) {
                if (
                    Request::get('page_total_cache') === null  ||
                    $type === 'nocache' || !$cachetime
                ) {
                    $total = call_user_func($call);
                    $type === null && Cache::set($cacheKey, $total, $cachetime);
                }
            }
        }
        return (int)$total;
    }
    //动态翻页函数
    public static function get($count, $size = 20, $unit = "条记录", $url = '', $target = '')
    {
        $conf = array(
            'url'       => $url,
            'target'    => $target,
            'count'     => $count,
            // 'lastId'    => self::getLastId(),
            'size'      => $size,
            'totalType' => 'G',
            'lang'      => Lang::get(iPHP_APP . ':page'),
            'unit'      => $unit,
            'item'      => '<li class="%s">%s</li>',
            'link'      => '<a href="%s" data-pageno="%d" %s>%s</a>',
        );
        $obj = new Pages($conf);

        $nav = $obj->show(3);
        $nav .= sprintf(
            '<li> <span class="muted">%s %s/页 共%d页</span></li>',
            $count . $obj->unit,
            $size . $obj->unit,
            $obj->total
        );
        if ($obj->total > 50) {
            $url = $obj->get_url(1);
            $nav .= sprintf(
                '<li> 
                <span class="muted">跳到 
                <input type="text" id="iPageNum" style="width:24px;margin-bottom: 0px;line-height: 12px;border: #dddddd 1px solid;" value="%d"/> 页 
                <button type="button" onClick="window.location=\'%s&page=\'+$(\'#iPageNum\').val();" style="line-height: 18px;border: #dddddd 1px solid;background-color: #f5f5f5;"/>跳转</button>
                </span>
                </li>',
                ($obj->nowindex + 1),
                $url
            );
        } else {
            $nav .= sprintf(
                '<li> <span class="muted">跳到%s页</span></li>',
                $obj->select()
            );
        }
        self::$pageNav = sprintf('<ul>%s</ul>',$nav);
        self::$count = $count;
        return $obj->offset();
    }
    //模板翻页函数
    public static function make($conf)
    {
        empty($conf['lang']) && $conf['lang'] = Lang::get(iPHP_APP . ':page');
        empty($conf['unit']) && $conf['unit'] = Lang::get(iPHP_APP . ':page:list');
        self::$INSTANCE = new Pages($conf);
        View::setGlobal(array(
            'PAGES' => self::$INSTANCE,
            'PAGE'  => self::$INSTANCE->vars()
        ));
        return self::$INSTANCE;
    }
    //模板静态分页配置
    public static function url($param)
    {
        if (!empty(Pages::$setting)) return;

        $param = (array)$param;
        Pages::$setting = array(
            'enable' => true,
            'url'    => $param['pageurl'],
            'index'  => $param['href'],
            'ext'    => $param['ext']
        );
    }
    /**
     * 内容分页
     *
     * @param [type] $content
     * @param [type] $pageNo 当前页码
     * @param [type] $count 展示条数
     * @param [type] $realCount 真实条数
     * @param [type] $chapterArray
     * @return void
     */
    public static function content(&$content, $pageNo, $count, $realCount, $chapterArray = null)
    {
        $pageArray = array();
        $pageurl = $content['iurl']['pageurl'];

        if ($count > 1) {
            $pageSetting = Pages::$setting;

            $content['node']['mode'] && self::url($content['iurl']);

            $conf = array(
                'name'      => 'p',
                'url'       => $pageurl,
                'count'     => $count,
                'size'      => 1,
                'nowindex'  => (int) Request::get('p'),
                'lang'      => Lang::get(iPHP_APP . ':page'),
            );
            if ($content['chapter']) foreach ((array) $chapterArray as $key => $value) {
                $conf['titles'][$key + 1] = $value['subtitle'];
            }
            $PAGES = new Pages($conf);
            // $PAGES->lang['index'] = '第一页';
            $pageArray['list']  = $PAGES->list_page();
            $pageArray['index'] = $PAGES->first_page([]);
            $pageArray['prev']  = $PAGES->prev_page([]);
            $pageArray['next']  = $PAGES->next_page([]);
            $pageArray['endof'] = $PAGES->last_page([]);
            $pagenav = $PAGES->show(0);
            $pagetext = $PAGES->show(10);

            Pages::$setting = $pageSetting;
        }
        $pageArray += array(
            'pn'      => $pageNo,
            'total'   => $count, //总页数
            'count'   => $realCount, //实际页数
            'current' => $pageNo,
            'nav'     => $pagenav,
            'url'     => Route::pageNum($pageurl, Request::get('p')),
            'pageurl' => $pageurl,
            'text'    => $pagetext,
            'args'    => Request::get('pageargs'),
            'first'   => ($pageNo == "1" ? true : false),
            'last'    => ($pageNo == $realCount ? true : false), //实际最后一页
            'end'     => ($pageNo == $count ? true : false)
        );
        $content['page'] = $pageArray;
    }
}
