<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class NodeWidget
{
    
    public static function btn($title="添加节点",$target=null)
    {
        include AdmincpView::display("widget/btn", "node");
        return AdmincpView::html();
    }

}
