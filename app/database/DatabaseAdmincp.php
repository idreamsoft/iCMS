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
 * 数据库管理
 * 
 * @role superadmin
 */
class DatabaseAdmincp extends AdmincpBase
{
	public $tableid = 0;
	public $volume = 1024;
	public $stop = 0;
	public $rows = 0;
	public $start = 0;

	public $bakdir;
	public $noCommit = true; //不提交事务
	public function __construct()
	{
		parent::__construct();
		$this->bakdir = Request::get('dir');
		File::check($this->bakdir);
		$driver = DB::getDriverName();
		if($driver!='mysql'){
			self::alert('仅支持mysql数据库，Sqlite请直接备份数据库文件');
		}
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
	 * 数据库恢复页
	 * @return void
	 */
	public function do_recover()
	{
		$res = FilesClient::folder('cache', 'backup');
		$dirRs = $res['dirList'];
		$fileRs = $res['fileList'];
		$pwd = $res['pwd'];
		$parent = $res['parent'];
		$URI = $res['URI'];
		include self::view();
	}
	/**
	 * 修复数据库
	 * @return void
	 */
	public function do_repair()
	{
		$this->do_backup();
	}
	/**
	 * 数据库备份页
	 * @return void
	 */
	public function do_backup()
	{
		self::setJsPath('database/assets/js/backup');
		include self::view('backup');
	}
	public function do_getTables()
	{
		$database = DB::getDatabaseName();
		$rows = DB::select("SHOW TABLE STATUS FROM `{$database}` WHERE ENGINE IS NOT NULL;");
		foreach ($rows as $key => &$value) {
			$value['Length'] = File::sizeUnit($value['Data_length'] + $value['Index_length']);
			$value['Data_length_text'] = File::sizeUnit($value['Data_length']);
			$value['Index_length_text'] = File::sizeUnit($value['Index_length']);
			$value['id'] = $key;
		}
		return [
			"total" => 1,
			"rows" => $rows,
		];
	}
	/**
	 * 数据替换页
	 * @return void
	 */
	public function do_replace()
	{
		include self::view();
	}
    /**
     * 批处理{title}
     */
    public function done_batch()
    {
        $model = self::$MODEL; //兼容php5
        $actions = [
            'default' => function ($ids, $data, $batch) use ($model) {
                $data && $model::update($data, $ids);
            }
        ];
        $actions2 = (array)$this->batching();
        $actions = array_merge($actions, $actions2);
        $result = AdmincpBatch::run($actions);

        return $result;
    }
	public function batching()
	{
		return array(
			'backup' => function ($ids, $data, $batch) {
				return $this->done_backup();
			},
			'optimize' => function ($ids, $data, $batch) {
				$tableArr = Request::param('tables');
				$msg = [];
				foreach ($ids as $key => $id) {
					$tables[] = $tableArr[$id];
					if ($table = $tableArr[$id]) {
						$_msg = $this->optimize($table);
						$msg[] = implode('<br />', $_msg);
					}
				}
				return $msg ? implode('<br />', $msg) : true;
			},
			'repair' => function ($ids, $data, $batch) {
				$tableArr = Request::param('tables');
				$msg = [];
				foreach ($ids as $key => $id) {
					$tables[] = $tableArr[$id];
					if ($table = $tableArr[$id]) {
						$_msg = $this->repair($table);
						$msg[] = implode('<br />', $_msg);
					}
				}
				return $msg ? implode('<br />', $msg) : true;
			},
		);
	}
	/**
	 * 数据备份
	 * @return void
	 */

	public function done_backup()
	{
		$ckey = Request::sparam('ckey');
		if ($ckey) {
			$data = Cache::get($ckey);
			$tables = $data['tables'];
			$bdir = $data['bdir'];
			$random = $data['random'];
		} else {
			$ids = Request::param('ids');
			$tableArr = Request::param('tables');
			$tables = [];
			foreach ($ids as $key => $id) {
				$tables[] = $tableArr[$id];
			}
		}
		$step = (int) Request::sparam('step', 0);
		$this->volume = (int) Request::sparam('volume');
		$this->tableid = (int) Request::sparam('tableid', 0);
		$this->start = (int) Request::sparam('start', 0);
		$this->rows = (int) Request::sparam('rows', 0);

		empty($tables) && self::alert('没有选择操作对象');
		// !$step && $this->sizelimit /= 2;
		$bdir && File::check($bdir);

		DB::query("SET SQL_QUOTE_SHOW_CREATE = 0");

		$bakTag = sprintf(
			"# iCMS Backup SQL File\n# Version:iCMS %s\n# Time: %s\n# iCMS: https://www.icmsdev.com\n# %s\n\n\n",
			iCMS_VERSION,
			get_date(0, "Y-m-d H:i:s"),
			str_repeat('-', 32)
		);
		if (empty($step)) {
			$random = random(10);
			$sql = $this->bakuptable($tables);
			$bdir = get_date(0, "Y-m-d-His") . '~' . random(10);
			empty($ckey) && $ckey = 'database_backup';
			Cache::set($ckey, [
				"tables" => $tables,
				"bdir" => $bdir,
				"random" => $random
			]);
			$this->stop = 1;
		} else {
			$sql = $this->bakupdata($tables, $this->start);
		}

		$f_num = ceil($step / 2) ?: 1;
		$filename = 'iCMS_' . $random . '_' . $f_num . '.sql';
		// $sql = $bakuptable ? $bakuptable . $bakupdata : $bakupdata;
		$t_name = $tables[$this->tableid];

		$backupdir = iPHP_APP_CACHE . '/backup/' . $bdir . '/';
		$sqlpath = $backupdir . $filename;
		File::mkdir($backupdir);
		trim($sql) && File::put($sqlpath, $bakTag . $sql, true, 'ab');
		$step++;
		if ($this->stop == 1) {
			$msg = sprintf(
				'<div class="alert alert-primary" role="alert">
				正在备份数据库,
				表<span class="badge bg-success">%s</span>
				共有<span class="badge bg-info">%d</span>条记录
				<br />
				已备份至<span class="badge bg-info">%d</span>条记录,
				生成第<span class="badge bg-info">%d</span>个备份文件，
				<br />
				程序将自动备份余下部分
				</div>
				',
				$t_name,
				$this->rows,
				$this->start,
				$f_num
			);
			$data = [
				'loop' => true,
				'btn' => [[
					"id" => "btn_stop",
					"text" => "停止",
					"url" => "./backup"
				], [
					"id" => "btn_next",
					"text" => "继续",
					"url" => Admincp::url('database/backup'),
					"params" => [
						'CSRF_TOKEN' => Security::$CSRF_TOKEN,
						'step' => $step,
						'start' => $this->start,
						'tableid' => $this->tableid,
						'rows' => $this->rows,
						'volume' => $this->volume,
						'ckey' => $ckey,
					],
					"next" => true,
					"time" => 1000
				]]
			];
		} else {
			$msg = '<div class="alert alert-success" role="alert">';
			$msg .= '已全部备份完成,备份文件保存在backup目录下!';
			$msg .= '</div>';
			$data = [
				'loop' => 'end',
				'btn' => [[
					"id" => "btn_next",
					"text" => "完成",
					"url" => "./backup"
				]]
			];
		}
		return [
			'code' => 1,
			'message' => $msg,
			"data" => $data
		];
	}
	/**
	 * 删除备份卷
	 *
	 * @return void
	 */
	public function done_delete($id = null)
	{
		$this->bakdir or self::alert('请选择要删除的备份卷');
		$backupdir = iPHP_APP_CACHE . '/backup/' . $this->bakdir;
		if (File::rmdir($backupdir)) {
			$zip = $backupdir . '.zip';
			file_exists($zip) && File::rm($zip);
		}
	}
	/**
	 * 下载备份
	 * @return void
	 */
	public function do_download()
	{
		$this->bakdir or self::alert('请选择要下载的备份卷');
		// Vendor::run('PclZip'); //加载zip操作类
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
	 * 备份恢复
	 * @return void
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
		// Script::dialog(
		// 	$msg,
		// 	$loopurl ? "src:" . $loopurl : 'js:1',
		// 	$dtime,
		// 	$moreBtn,
		// 	$updateMsg
		// );
	}
	/**
	 * 执行查询
	 * @return void
	 */
	public function done_query()
	{
		$field = Request::spost('field');
		$pattern = Request::post("pattern");
		$replacement = Request::post("replacement");
		$where = Request::post("where");
		$pattern or self::alert("查找项不能为空~");
		$params = [$pattern, $replacement];
		if ($field == "body") {
			for ($i=1; $i < 10; $i++) { 
				$dataModel = ArticleDataModel::sharding($i);
				$dataModel->where($where)->update([
					"body" => [
						'raw' => 'REPLACE(body,?,?)', $params
					]
				]);
			}
		} else {
			if ($field == "tkd") {
				$rows_affected = ArticleModel::where($where)->update([
					"title" => [
						'raw' => 'REPLACE(`title`,?,?)', $params
					],
					"keywords" => [
						'raw' => 'REPLACE(`keywords`,?,?)', $params
					],
					"description" => [
						'raw' => 'REPLACE(`description`,?,?)', $params
					]
				]);
			} else {
				$rows_affected = ArticleModel::where($where)->update([
					$field => [
						'raw' => 'REPLACE(`' . $field . '`,?,?)', $params
					]
				]);
			}
		}
		return $rows_affected . "条记录被替换完成";
	}
	/**
	 * 优化分表
	 * @return void
	 */
	public function do_sharding()
	{
		include self::view();
	}
	public static function bakuptable($tables, $exists = true)
	{
		return AppsTable::makeDDL($tables, $exists);
	}
	public function bakupdata($tableArr, $start = 0)
	{
		$this->stop = 0;
		$t_count = count($tableArr);
		$bakupdata = '';
		$volume = $this->volume * 1024;

		for ($i = $this->tableid; $i < $t_count; $i++) {
			$table = $tableArr[$i];
			$ts = DB::row("SHOW TABLE STATUS LIKE '$table'");
			$this->rows = $ts['Rows'];
			$pageSize = 10000;
			try {
				$query = "SELECT * FROM $table LIMIT $start,$pageSize";
				$option = array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL);
				$stmt = DB::execute($query, [], null, $option);
				while ($row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
					$keys   = array_keys($row);
					$values = array_values($row);
					$values = array_map('addslashes', $values);
					$bakupdata .= sprintf(
						"INSERT INTO %s (`%s`) VALUES ('%s');\n",
						$table,
						implode('`,`', $keys),
						implode("','", $values)
					);
					$start++;
					if ($volume && strlen($bakupdata) > $volume) {
						break;
					}
				}
				if ($start >= $this->rows) {
					$start = 0;
					$this->rows = 0;
				}
				if ($volume && strlen($bakupdata) > $volume) {
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
			$this->tableid = $i;
			$this->start = $start;
			$start = 0;
		}
		return $bakupdata;
	}
	public function repair($table)
	{
		$rs = DB::select("REPAIR TABLE `$table`");
		$_count = count($rs);
		for ($i = 0; $i < $_count; $i++) {
			$msg[] = sprintf(
				'表：%s 操作：%s 状态：%s<br />',
				substr(strrchr($rs[$i]['Table'], '.'), 1),
				$rs[$i]['Op'],
				$rs[$i]['Msg_text']
			);
		}
		return $msg;
	}
	public function optimize($table)
	{
		$rs = DB::select("OPTIMIZE TABLE `$table`");
		$_count = count($rs);
		for ($i = 0; $i < $_count; $i++) {
			$msg[] = sprintf(
				'表：%s 操作：%s 状态：%s<br />',
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
