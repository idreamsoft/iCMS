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
 * 文件管理器
 */
class FilesExplorerAdmincp extends AdmincpBase
{
	/**
	 * 文件管理器
	 *
	 * @return void
	 */
	public function do_browse()
	{
		$this->explorer(null, null, 'browse');
	}
	public function do_grid()
	{
		$this->explorer(null, null, 'grid');
	}
	/**
	 * 图片管理器
	 *
	 * @return void
	 */
	public function do_photo()
	{
		$this->explorer(null, FilesPic::$EXTS, 'grid');
	}
	/**
	 * 创建目录
	 *
	 * @return void
	 */
	public function done_mkdir()
	{
		return FilesClient::mkdir();
	}
	/**
	 * 删除目录
	 *
	 * @return void
	 */
	public function done_rmdir()
	{
		// return FilesClient::rmdir();
	}
	/**
	 * 删除文件
	 * @return void
	 */
	public function done_rmfile()
	{
		return FilesClient::rmfile();
	}

	/**
	 * 模板文件管理器
	 *
	 * @return void
	 */
	public function do_template()
	{
		$this->explorer('template', null, 'template');
	}
	/**
	 * 创建模板目录
	 *
	 * @return void
	 */
	public function done_template_mkdir()
	{
		return FilesClient::mkdir('template');
	}
	/**
	 * 删除模板目录
	 *
	 * @return void
	 */
	public function done_template_rmdir()
	{
		// return FilesClient::rmdir("template");
	}
	/**
	 * 创建模板文件
	 *
	 * @return void
	 */
	public function done_template_rmfile()
	{
		return FilesClient::rmfile("template");
	}


	public function explorer($dir = NULL, $type = NULL, $view = 'browse')
	{
		$folder    = FilesClient::folder($dir, $type);
		$dirList  = $folder['dirList'];
		$fileList = $folder['fileList'];
		$pwd    = $folder['pwd'];
		$parent = $folder['parent'];
		$URI    = $folder['URI'];
		$navbar = false;
		$param = ['dir' => $pwd];
		self::$IS_MODAL && $param['modal'] = true;
		$urls = [
			'browse' => self::url($param, Admincp::url("filesExplorer/browse")),
			'grid' => self::url($param, Admincp::url("filesExplorer/grid"))
		];
		include self::view($view);
	}
}
