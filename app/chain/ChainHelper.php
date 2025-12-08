<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ChainHelper
{
    /**
     * 标签管理，设置为内链回调
     */
    public static function batchTag($ids, $data, $batch)
    {
        $result = TagModel::select($ids);
        $nodeArray  = Node::many($result, 'cid');
        $tnodeArray = Node::many($result, 'tcid', Tag::APPID);
        foreach ($result as $tag) {
            $C = (array) $nodeArray[$tag['cid']];
            $TC = (array) $tnodeArray[$tag['tcid']];
            $url = Route::get('tag', array($tag, $C, $TC))->href;

            $data = ['keyword' => $tag['name']];
            $check = ChainModel::field('id')->where($data)->value();
            if (!$check) {
                $data['replace'] = $url;
                $flag = ChainModel::create($data, true);
            }
        }
        // return $flag;
    }
}
