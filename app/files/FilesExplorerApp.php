<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class FilesExplorerApp extends AppsBase
{
	public static $LOGIN = ['browse'];
	/**
	 * 文件管理器
	 *
	 * @return void
	 */
	public function api_browse()
	{
        if(Request::isAjax()){
            return $this->browse();
        }
		return self::view();
	}
	public function browse()
	{
        $lastId = (int)Request::spost('lastId', 0);
        $rows = FilesFunc::lists([
            'userid'=> User::$id,
            'lastId'=> $lastId,
            'page'=> true,
        ]);
        $pageData = MessageFunc::$pageData;
        
        if ($rows) {
            $rows && $rows = array_map(function ($row) {
                // $todayDate = date('Y-m-d');
                // $row['sendtimeText'] = date('Y-m-d H:i', $row['sendtime']);
                // if (substr($row['sendtimeText'], 0, 10) == $todayDate) {
                //     $row['sendtimeText'] = date('H:i', $row['sendtime']);
                // }
                return $row;
            }, $rows);
            $lastArr = end($rows);
            $lastId = $lastArr ? $lastArr['id'] : $lastId;
        }

        return compact('rows', 'lastId','pageData');
	}

}
