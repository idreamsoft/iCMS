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

class AppsMap
{
    /**
     * [delete description]
     *
     * @param   [type]  $iid       [$iid description]
     * @param   [type]  $Model     [$Model description]
     * @param   [type]  $ModelMap  [$ModelMap description]
     *
     * @return  [type]             [return description]
     */
    // public static function delete($appid, $iid, $Model, $ModelMap)
    public static function delete($appid, $iid, $name)
    {
        $Model = sprintf('%sModel',$name);
        $Model = new $Model;
        
        $mapModel = sprintf('%sMapModel',$name);
        $mapModel = new $mapModel;

        $mWhere = compact('appid', 'iid');
        $nodes = $mapModel->field('node')->where($mWhere)->pluck();
        if ($nodes) {
            $Model = $Model->where(array('id' => $nodes));
            $Model->where('count', '>', '0')->dec('count');
            // $Model->where('count', '<', '1')->delete();
        }
        $mapModel->where($mWhere)->delete();
    }
    /**
     * [change description]
     *
     * @param   [type]  $field     [$field description]
     * @param   [type]  $appid     [$appid description]
     * @param   Array  $nodes     [$nodes description]
     * @param   [type]  $event     [$event description]
     * @param   [type]  $builder   [$builder description]
     * @param   Model  $Model     [$Model description]
     * @param   Model  $mapModel  [$mapModel description]
     *
     * @return  [type]             [return description]
     */
    public static function change($field, $appid, $nodes, $event, $iid, $name, $model = true)
    {
        if($model){
            $Model = sprintf('%sModel',$name);
            $Model = new $Model;
        }
        
        $mapModel = sprintf('%sMapModel',$name);
        $mapModel = new $mapModel;

        is_array($nodes) or $nodes = [$nodes];
        $nodes = array_filter($nodes);
        if (empty($nodes)) return;
        // $field = 'scid';
        // $appid = iCMS_APP_ARTICLE;
        
        $Model && $Model->where(array('id' => $nodes))->inc('count');
        if ($event == 'created') {
            // $iid = $builder->getResponse('id');
            if ($nodes) foreach ($nodes as $node) {
                $node = (int)$node;
                $mapModel->create(compact('node', 'iid', 'field', 'appid'));
            }
        } elseif ($event == 'updated') {
            // $row = $builder->field('id')->get();
            // $iid = $builder->getResponse('id');

            $mWhere = compact('appid', 'iid', 'field');
            $_nodes = (array)$mapModel->field('node')->where($mWhere)->pluck();
            $_nodes && $Model && $Model->where(array('id' => $_nodes))->where('count', '>', '0')->dec('count');

            $diff = array_diff_values($nodes, $_nodes);
            if ($diff['+']) foreach ($diff['+'] as $node) {
                $node = (int)$node;
                $mapModel->create(compact('node', 'iid', 'field', 'appid'));
            }
            if ($diff['-']) {
                $mWhere['node'] = $diff['-'];
                $mapModel->where($mWhere)->delete();
            }
        }
    }
    public static function join($app, $node, $appid, $field, $alias = null)
    {
        $map = ucfirst($app) . 'MapModel';
        $where = compact('node', 'appid', 'field');
        $alias === null && $alias = $map;
        is_numeric($alias) && $alias = $map . $alias;
        $model = new $map;
        $raw = $model->alias($alias)->field('iid')->where($where)->getWhere();
        return [
            ['id', '=', DB::raw($alias . '.iid')],
            [$model->getTableName(), $alias],
            DB::raw($raw)
        ];
    }
}
