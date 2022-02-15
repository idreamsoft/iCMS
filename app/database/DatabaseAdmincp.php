<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
class DatabaseAdmincp extends AdmincpBase
{
	public function __construct()
	{
		parent::__construct();
		$this->bakdir = Request::get('dir');
		File::check($this->bakdir);
	}
	public static function getData()
	{
		$prefix    = DB::getTablePrefix();
		$result    = DB::status();
		$dataSize  = 0;
		$indexSize = 0;
		$rows = 0;
		$tables = [];
		$oTables = [];

		foreach ($result as $key => $value) {
			$table = $value['Name'];
			if (strstr($table, $prefix)) {
				$tables[] = $table;
				$dataSize += $value['Data_length'];
				$indexSize += $value['Index_length'];
				$rows += $value['Rows'];
			} else {
				$oTables[] = $table;
			}
		}
		return compact('rows', 'dataSize', 'indexSize', 'tables', 'oTables');
	}
	/**
	 * [数据库恢复页]
	 * @return [type] [description]
	 */
	public function do_recover()
	{
		$res = FilesClient::folder('cache/backup', array('sql'));
		$dirRs = $res['DirArray'];
		$fileRs = $res['FileArray'];
		$pwd = $res['pwd'];
		$parent = $res['parent'];
		$URI = $res['URI'];
		include self::view("database.recover");
	}
	/**
	 * [修复数据库]
	 * @return [type] [description]
	 */
	public function do_repair()
	{
		$this->do_backup();
	}
	/**
	 * [数据库备份页]
	 * @return [type] [description]
	 */
	public function do_backup()
	{
		$database = DB::getDatabaseName();
		$result = DB::select("SHOW TABLE STATUS FROM `{$database}` WHERE ENGINE IS NOT NULL;");
		$_count = count($result);
		//		for($i=0;$i<$_count;$i++){
		//			if (preg_match ("/^".preg_quote(iPHP_DB_PREFIX,'/')."/i" ,$rs[$i]['Name'])){
		//				$iTable[] = $rs[$i];
		//			}else{
		//				$oTable[] = $rs[$i];
		//			}
		//		}
		include self::view("database.backup");
	}
	/**
	 * [数据替换页]
	 * @return [type] [description]
	 */
	public function do_replace()
	{
		include self::view("database.replace");
	}
	public function do_batch()
	{
		$stype = AdmincpBatch::$config['stype'];
		$actions = array(
			'backup' => function ($idArray, $ids, $batch) {
				return $this->do_savebackup();
			},
			'optimize' => function ($idArray, $ids, $batch) {
				$msg = $this->optimize($ids);
				return $msg ? implode('<br />', $msg) : true;
			},
			'repair' => function ($idArray, $ids, $batch) {
				$msg = $this->repair($ids);
				return $msg ? implode('<br />', $msg) : true;
			},
		);
		return AdmincpBatch::run($actions, "表", "table", null);
	}
	/**
	 * [数据备份]
	 * @return [type] [description]
	 */
	public function do_savebackup()
	{

		$tables = Request::sparam('table');
		$step = (int) Request::sparam('step');
		$ckey = Request::sparam('ckey');
		if ($ckey) {
			$data = Cache::get($ckey);
			$tables = $data['tables'];
			$bdir = $data['bdir'];
			$random = $data['random'];
		}

		$this->volume = (int) Request::sparam('volume');
		$this->tableid = (int) Request::sparam('tableid');
		$this->start = (int) Request::sparam('start') ?: 0;
		$this->rows = Request::sparam('rows');

		empty($tables) && self::alert('没有选择操作对象');
		// !$step && $this->sizelimit /= 2;
		File::check($bdir);

		DB::query("SET SQL_QUOTE_SHOW_CREATE = 0");
		$bakupdata = $this->bakupdata($tables, $this->start);
		$bakTag = sprintf(
			"# iCMS Backup SQL File\n# Version:iCMS %s\n# Time: %s\n# iCMS: https://www.icmsdev.com\n# %s\n\n\n",
			iCMS_VERSION,
			get_date(0, "Y-m-d H:i:s"),
			str_repeat('-', 32)
		);
		if (empty($step)) {
			$step = 1;
			$random = random(10);
			$bakuptable = $this->bakuptable($tables);
			$bdir = get_date(0, "Y-m-d-His") . '~' . random(10);
			empty($ckey) && $ckey = 'database_backup';
			Cache::set($ckey, [
				"tables" => $tables,
				"bdir" => $bdir,
				"random" => $random
			]);
		}
		$updateMsg = ($step == 1 ? false : 'FRAME');
		$f_num = ceil($step / 2);
		$filename = 'iCMS_' . $random . '_' . $f_num . '.sql';
		$step++;
		$writedata = $bakuptable ? $bakuptable . $bakupdata : $bakupdata;

		$t_name = $tables[$this->tableid];
		$backupdir = iPHP_APP_CACHE . '/backup/' . $bdir . '/';
		$sqlpath = $backupdir . $filename;
		File::mkdir($backupdir);
		trim($writedata) && File::put($sqlpath, $bakTag . $writedata, true, 'ab');
		if ($this->stop == 1) {
			// $loopurl = APP_URL . "&do=savebackup&start={$this->start}&tableid={$this->tableid}&sizelimit={$this->sizelimit}&step={$step}&rows={$this->rows}";
			$loopurl = sprintf(
				'%s&do=savebackup&start=%d&tableid=%d&step=%d&rows=%d&volume=%d&ckey=%s&CSRF_TOKEN=%s',
				APP_URL,
				$this->start,
				$this->tableid,
				$step,
				$this->rows,
				$this->volume,
				$ckey,
				Security::$CSRF_TOKEN
			);
			$moreBtn = array(
				array("id" => "btn_stop", "text" => "停止", "url" => APP_URL . "&do=backup"),
				array("id" => "btn_next", "text" => "继续", "src" => $loopurl, "next" => true),
			);
			$dtime = 1;
			$msg = sprintf(
				"正在备份数据库,
				表<span class='label label-success'>%s</span>
				共有<span class='label label-info'>%d</span>条记录
				<hr />
				已备份至<span class='label label-info'>%d</span>条记录,
				生成第<span class='label label-info'>%d</span>个备份文件，
				<hr />
				程序将自动备份余下部分",
				$t_name,
				$this->rows,
				$this->start,
				$f_num
			);
		} else {
			$msg = "success:#:check:#:已全部备份完成,备份文件保存在backup目录下!";
			$moreBtn = array(
				array("id" => "btn_next", "text" => "完成", "url" => APP_URL . "&do=backup"),
			);
			$dtime = 5;
		}

		Script::dialog(
			$msg,
			$loopurl ? "src:" . $loopurl : 'js:1',
			$dtime,
			$moreBtn,
			$updateMsg
		);
	}
	/**
	 * 删除备份卷
	 *
	 * @return void
	 */
	public function do_delete($id = null)
	{
		$this->bakdir or self::alert('请选择要删除的备份卷');
		$backupdir = iPHP_APP_CACHE . '/backup/' . $this->bakdir;
		if (File::rmdir($backupdir)) {
			$zip = $backupdir . '.zip';
			file_exists($zip) && File::rm($zip);
		}
	}
	/**
	 * [下载备份]
	 * @return [type] [description]
	 */
	public function do_download()
	{
		$this->bakdir or self::alert('请选择要下载的备份卷');
		Vendor::run('PclZip'); //加载zip操作类
		$zipname = $this->bakdir . ".zip"; //压缩包的名称
		$zipFile = iPHP_APP_CACHE . '/backup/' . $zipname; //压缩包所在路径
		$zip = new PclZip($zipFile);
		$backupdir = iPHP_APP_CACHE . '/backup/' . $this->bakdir;
		$fileArray = glob($backupdir . '/iCMS_*.sql');
		$filelist = implode(',', $fileArray);
		$v_list = $zip->create($filelist, PCLZIP_OPT_REMOVE_PATH, iPHP_APP_CACHE . '/backup/'); //将文件进行压缩
		$v_list == 0 && self::alert("压缩出错 : " . $zip->errorInfo(true)); //如果有误，提示错误信息。
		ob_end_clean();
		header("Content-Type: application/force-download");
		header("Content-Transfer-Encoding: binary");
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename=' . $zipname);
		header('Content-Length: ' . filesize($zipFile));
		readfile($zipFile);
		flush();
		ob_flush();
	}
	/**
	 * [备份恢复]
	 * @return [type] [description]
	 */
	public function do_recovery()
	{
		self::alert('请使用其它mysql管理软件恢复');
		$this->bakdir or self::alert('请选择要恢复的备份卷');
		$backupdir = iPHP_APP_CACHE . '/backup/' . $this->bakdir;
		$step = (int) $_GET['step'];
		$count = (int) $_GET['count'];
		if ($count == 0) {
			$fileArray = glob($backupdir . '/iCMS_*.sql');
			$count = count($fileArray);
		}
		$step or $step = 1;
		$this->bakindata($step);
		$i = $step;
		$step++;
		if ($count > 1 && $step <= $count) {
			$loopurl = APP_URL . "&do=renew&step={$step}&count={$count}&dir={$this->bakdir}";
			$moreBtn = array(
				array("id" => "btn_stop", "text" => "停止", "url" => APP_URL . "&do=recover"),
				array("id" => "btn_next", "text" => "继续", "src" => $loopurl, "next" => true),
			);
			$dtime = 1;
			$msg = "正在导入第<span class='label label-success'>{$i}</span>卷备份文件，<hr />程序将自动导入余下备份文件...";
		} else {
			$msg = "success:#:check:#:导入成功!";
			$moreBtn = array(
				array("id" => "btn_next", "text" => "完成", "url" => APP_URL . "&do=recover"),
			);
			$dtime = 5;
		}
		$updateMsg = ($i == 1 ? false : 'FRAME');
		Script::dialog(
			$msg,
			$loopurl ? "src:" . $loopurl : 'js:1',
			$dtime,
			$moreBtn,
			$updateMsg
		);
	}
	/**
	 * [执行查询]
	 * @return [type] [description]
	 */
	public function do_query()
	{
		$field = $_POST["field"];
		$pattern = $_POST["pattern"];
		$replacement = $_POST["replacement"];
		$where = $_POST["where"];
		$pattern or self::alert("查找项不能为空~");
		if ($field == "body") {
			$rows_affected = DB::query("UPDATE `#iCMS@__article_data` SET `body` = REPLACE(`body`, '$pattern', '$replacement') {$where}");
		} else {
			if ($field == "tkd") {
				$rows_affected = DB::query("UPDATE `#iCMS@__article` SET `title` = REPLACE(`title`, '$pattern', '$replacement'),
		    	`keywords` = REPLACE(`keywords`, '$pattern', '$replacement'),
		    	`description` = REPLACE(`description`, '$pattern', '$replacement'){$where}");
			} else {
				$rows_affected = DB::query("UPDATE `#iCMS@__article` SET `$field` = REPLACE(`$field`, '$pattern', '$replacement'){$where}");
			}
		}
		return $rows_affected . "条记录被替换完成";
		// self::success($rows_affected . "条记录被替换完成");
	}
	/**
	 * [优化分表]
	 * @return [type] [description]
	 */
	public function do_sharding()
	{
		self::head();
		print("暂无");
		self::foot();
	}
	public static function bakuptable($tables, $exists = true)
	{
		return AppsTable::makeDDL($tables, $exists);
	}
	public function bakupdata($tabledb, $start = 0)
	{
		$this->tableid = $this->tableid ? $this->tableid - 1 : 0;
		$this->stop = 0;
		$t_count = count($tabledb);
		$bakupdata = '';

		for ($i = $this->tableid; $i < $t_count; $i++) {
			$table = $tabledb[$i];
			$ts = DB::row("SHOW TABLE STATUS LIKE '$table'");
			$this->rows = $ts['Rows'];
			$pageSize = 10000;
			try {
				$query = "SELECT * FROM $table LIMIT $start,$pageSize";
				$option = array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL);
				$stmt = DB::execute($query, [], null, $option);
				while ($row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
					$start++;
					$keys   = array_keys($row);
					$values = array_values($row);
					$values = array_map('addslashes', $values);
					$bakupdata .= sprintf(
						"INSERT INTO %s (`%s`)VALUES('%s');\n",
						$table,
						implode('`,`', $keys),
						implode("','", $values)
					);
					if ($this->volume && strlen($bakupdata) > $this->volume * 1024) {
						break;
					}
				}
				if ($start >= $this->rows) {
					$start = 0;
					$this->rows = 0;
				}
				if ($this->volume && strlen($bakupdata) > $this->volume * 1024) {
					$start == 0 && $i++;
					$this->stop = 1;
					break;
				}
			} catch (\Exception $ex) {
				// print $e->getMessage();
			}
			$start = 0;
		}
		if ($this->stop == 1) {
			$i++;
			$this->tableid = $i;
			$this->start = $start;
			$start = 0;
		}
		return $bakupdata;
	}
	public function repair($tables)
	{
		$tableA = (array) $_POST['table'];
		$rs = DB::select("REPAIR TABLE $tables");
		$_count = count($rs);
		for ($i = 0; $i < $_count; $i++) {
			$msg[] = sprintf(
				'表：%s 操作：%s 状态：%s<hr />',
				substr(strrchr($rs[$i]['Table'], '.'), 1),
				$rs[$i]['Op'],
				$rs[$i]['Msg_text']
			);
		}
		return $msg;
	}
	public function optimize($tables)
	{
		$rs = DB::select("OPTIMIZE TABLE $tables");
		$_count = count($rs);
		for ($i = 0; $i < $_count; $i++) {
			$msg[] = sprintf(
				'表：%s 操作：%s 状态：%s<hr />',
				substr(strrchr($rs[$i]['Table'], '.'), 1),
				$rs[$i]['Op'],
				$rs[$i]['Msg_text']
			);
		}
		return $msg;
	}

	public function bakindata($num)
	{
		$backupdir = iPHP_APP_CACHE . '/backup/' . $this->bakdir;
		$fileList = glob($backupdir . '/iCMS_*_' . $num . '.sql');
		$fileArray = file($fileList[0]);
		$sql = '';
		$num = 0;
		foreach ($fileArray as $key => $line) {
			$line = trim($line);
			if (!$line || $line[0] == '#') {
				continue;
			}

			if (preg_match("/\;$/", $line)) {
				$sql .= $line;
				if (preg_match("/^CREATE/", $sql)) {
					$extra = substr(strrchr($sql, ')'), 1);
					$tabtype = substr(strchr($extra, '='), 1);
					$tabtype = substr($tabtype, 0, strpos($tabtype, strpos($tabtype, ' ') ? ' ' : ';'));
					$sql = str_replace($extra, '', $sql);
					$extra = " ENGINE=$tabtype DEFAULT CHARSET=" . DB::getCharset() . ';';
					$sql .= $extra;
				} elseif (preg_match("/^INSERT/", $sql)) {
					$sql = 'REPLACE ' . substr($sql, 6);
				}
				DB::query($sql);
				$sql = '';
			} else {
				$sql .= $line;
			}
		}
	}
}
