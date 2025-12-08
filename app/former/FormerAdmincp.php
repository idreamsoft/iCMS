<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
/**
 * 表单生成器
 */
class FormerAdmincp extends AdmincpBase
{
    /**
     * 表单编辑器
     *
     * @return void
     */
    public function do_editor(){
        include self::view();
    }
}
