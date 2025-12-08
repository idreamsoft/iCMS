<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class ArticleHook
{
    public static function rewrite_clink($clink,&$obj)
    {
        $check = Article::check($clink, 0, 'clink');
        $check && $obj->URI = sprintf('article.php?clink=%s',$clink);
    }
    
}
