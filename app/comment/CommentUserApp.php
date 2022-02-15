<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class CommentUserApp extends UserContentApp
{

    public function __construct()
    {
        parent::__construct();
    }
    public function API_manage()
    {
        return $this->display();
    }
    public function do_delete(){
        $id = (int) Request::post('id');
        $where = ['id' => $id, 'userid' => User::$id];
        
        DB::beginTransaction();
        try {
            CommentModel::update(['status'=>2],$where);
            DB::commit();
        } catch (\sException $ex) {
            DB::rollBack();
            $msg = $ex->getMessage();
            iJson::error($msg);
        }
        iJson::success();
    }
}
