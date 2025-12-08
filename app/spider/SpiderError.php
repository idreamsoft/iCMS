<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

use SebastianBergmann\CodeCoverage\Report\PHP;

defined('iPHP') or exit('What are you doing?');

class SpiderError
{
    public static function log($msg, $url = null, $type = '', $a = null, $alert = true)
    {
        if (Spider::$isTest) {
            self::alert($msg);
            return;
        }
        $data = array(
            'work'    => Spider::$work,
            'rid'     => (int)Spider::$rid,
            'pid'     => (int)Spider::$pid,
            'url'     => ($url ? $url : Spider::$url),
            'urlId'   => (int)Spider::$urlId,
            'msg'     => $msg,
            'date'    => date("Y-m-d H:i:s"),
            'addtime' => time(),
            'type'    => $type
        );
        $a && $data = array_merge($data, (array)$a);
        SpiderErrorModel::create($data, true);
        $alert && self::alert($msg);
    }
    public static function alert($msg, $url = null, $type = null,$ex=null)
    {
        if (Spider::$isTest) {
            $msg = nl2br($msg);
            echo '<h2>' . $msg . '</h2>';
            return;
        }
        if (Spider::$isShell) {
            SpiderTools::prints('%s', [$msg], $ex===false?'y':'r');
        }

        if($ex===false){
            return;
        }
        if($ex){
            throw new $ex($msg);
        }

        iPHP::alert($msg.PHP_EOL.PHP_EOL);
    }
}
