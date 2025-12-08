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

class ArticleModel extends Model
{
    public static $statusMap = [
        '0' => '草稿',
        '1' => '正常',
        '2' => '回收站',
        '3' => '待审核',
        '4' => '未通过',
        '5' => '删除',
        '6' => '锁定',
        '7' => '隐藏'
    ];
    public static $postypeMap = [
        '0' => '用户',
        '1' => '管理',
    ];
    protected $casts = [
        // 'scid'    => 'array',
        // 'pid'     => 'array',
        'picdata' => 'array',
        'nodeAttrs' => 'array',
        // 'related' => 'array',
    ];
    protected $events = [
        // 'titleGeted' => [__CLASS__, 'titleGeted'],
        // 'geted' => ['ArticleEvent', 'geted'],
        // 'created' => ['ArticleEvent', 'created'],
        // 'updated' => ['ArticleEvent', 'updated'],
        'delete' => ['ArticleEvent', 'delete'],//删除前
        // 'deleted' => ['ArticleEvent', 'deleted'],//删除后
        'changed' => ['ArticleEvent', 'changed'],//created + updated
    ];
    // public static function titleGeted($value, $Builder){
    //     debug_print_backtrace();
    // }
    // 
    // public static function geted($response, $Builder, $event, $isMulti)
    // public static function created($response, $Builder, $event, $isMulti)
    // public static function updated($response, $Builder, $event, $isMulti)
    //删除前
    // public static function delete($response, $Builder, $event, $isMulti)
    //删除后
    // public static function deleted($response, $Builder, $event, $isMulti)
    // 字段事件
    // public static function titleGeted($value, $Builder){
    //     debug_print_backtrace();
    // }
    //创建前处理字段
    // public static function titleCreating($value, $Builder, $event, $isMulti){
    //     debug_print_backtrace();
    //     $response = $Builder->toArray();
    //     var_dump($value,$response);
    // }
    //创建后处理字段
    // public static function titleCreated($value, $Builder, $event, $isMulti){
    //     // debug_print_backtrace();
    //     $response = $Builder->toArray();
    //     var_dump($value,$response);
    // }
    // public static function titleUpdated($value, $Builder, $event, $isMulti){
    //     debug_print_backtrace();
    // }
    // public static function titleDelete($value, $Builder, $event, $isMulti){
    //     debug_print_backtrace();
    // }
    // public static function titleEvent($value, $Builder, $event, $isMulti){
    //     debug_print_backtrace();
    // }
    //更新事件
    // public static function updating($row, $Builder, $event, $isMulti)
    // {
    //     // $row['updated'] = time();
    //     // $Builder->setResponse($row);
    // }
    //新增事件
    // public static function creating($row, $Builder, $event, $isMulti)
    // {
    //     // if (empty($row['created'])) {
    //     //     $row['created'] = time();
    //     // }
    //     // $row['updated'] = time();
    //     // $Builder->setResponse($row);
    // }
    // 包含 created 和 updated事件
    // public static function changed($response, $Builder, $event, $isMulti)
    // public static function titleChanged($value, $Builder, $event, $isMulti)
}
