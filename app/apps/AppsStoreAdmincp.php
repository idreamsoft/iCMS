<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */
defined('iPHP') or exit('What are you doing?');

class AppsStoreAdmincp extends AdmincpBase
{
    public function __construct()
    {
        parent::__construct();
        $developer = Developer::getConfig();
        $url = Admincp::url('/developer/login', '?forward=' . urlencode($_SERVER['REQUEST_URL']));
        if (empty($developer)) {
            Utils::jumpto($url);
        }
    }

    /**
     * autoCache 在更新所有缓存时，将会自动执行
     */
    public static function autoCache()
    {
        $pattern = AppsStore::getPkg('*');
        $list = glob($pattern);
        if ($list) {
            foreach ($list as $key => $file) {
                File::rm($file);
            }
        }
    }

    /**
     * 获取本地安装列表
     *
     * @return void
     */
    public function done_lists()
    {
        $list = AppsStoreModel::field('id,sid,git_time')->select();
        $list = array_column($list, null, 'sid');
        return $list;
    }

    /**
     * 获取本地安装信息
     *
     * @return void
     */
    public function done_sget()
    {
        $sid = Request::post('sid');
        $store = AppsStoreModel::where('sid', $sid)->get();
        if ($store) {
            $store['addtime_text'] = date('Y-m-d H:i:s', $store['addtime']);
            $store['git_time_text'] = date('Y-m-d H:i:s', $store['git_time']);
        }
        return $store;
    }

    public function done_proxy()
    {
        // $sdo = Request::sget('sdo');
        // echo call_user_func(['DeveloperStoreProxy', $sdo]);
    }

    /**
     * 获取远程静态资源
     *
     * @return string
     */
    public function do_assets()
    {
        if ($js = Request::sget('js')) {
            header(sprintf('Content-Type: %s; charset=UTF-8', FilesMime::get('js')));
            $html = DeveloperStoreProxy::get('getjs', ['path'=>$js]);
        }
        if ($css = Request::sget('css')) {
            header(sprintf('Content-Type: %s; charset=UTF-8', FilesMime::get('css')));
            $html = DeveloperStoreProxy::get('getcss', ['path'=>$css]);
        }
        exit($html);
    }

    // public function do_buy()
    // {
    //     $id = Request::sget('id');
    //     $store = DeveloperStoreProxy::buy($id);
    //     var_dump($store);
    // }
    /**
     * 应用页面
     *
     */
    public function do_app()
    {
        return $this->index('app');
    }

    /**
     * 插件页面
     *
     */
    public function do_plugin()
    {
        return $this->index('plugin');
    }

    /**
     * 模板页面
     *
     */
    public function do_template()
    {
        return $this->index('template');
    }

    /**
     * 详情页面
     *
     */
    public function do_detail()
    {
        $id = Request::sget('id');
        $html = DeveloperStoreProxy::get('store', ['id'=>$id]);
        self::setJsPath('appsStore/assets/js/detail');
        include self::view('store');
    }

    public function index($name = null, $title = null)
    {
        if (Request::isAjax()) {
            return DeveloperStoreProxy::ajax($name);
        }
        $html = DeveloperStoreProxy::index($name);
        self::setJsPath('appsStore/assets/js/list');
        include self::view('list');
    }

    /**
     * 更新应用/模板/插件
     */
    public function done_update()
    {
        $sid = Request::post('sid');
        $force = Request::post('force'); //强制更新
        $store = AppsStoreModel::where('sid', $sid)->get();
        $args = [
            'package'=> Request::post('package')
        ];
        if ($store) {
            $args += [
                'update'  => 1,
                'git_sha' => $store['git_sha'],
                'order'   => $store['authkey'],
            ];
        }
        $param = $this->makeParam('update', $sid, $args);
        $name = File::name($param['package']);
        $pkgPath = AppsStore::getPkg($name);

        if (!File::exist($pkgPath) || (filemtime($pkgPath) - time() > 3600)) {
            self::alert('更新出错0');
        }

        $arr = DeveloperStoreProxy::post('update', $param, true);
        $store = $arr['data']['store'];
        empty($store) && self::alert('更新出错1');

        if ($store && $name) {
            AppsStore::getPkg($name);
            // AppsStore::$IS_TEST = 1;
            AppsStore::$IS_FORCE = $force;
            AppsStore::$IS_UPDATE = 1;

            try {
                AppsStore::$DATA = $store;
                AppsStore::install($sid, $param['order']);
                DB::commit();

                AppsStore::$appId && $url = Admincp::url('apps/edit', ['id'=>AppsStore::$appId]);
                AppsStore::$MESSAGES = Security::filterPath(AppsStore::$MESSAGES);
                return [
                    'data' => [
                        'log'  => AppsStore::$MESSAGES,
                        'store'=> $store
                    ],
                    'message' => '安装完成',
                    'url'     => $url
                ];
            } catch (\Exception $ex) {
                DB::rollBack();
                $message = '出现错误中止更新<br />' . $ex->getMessage();
                // $msg .= "以下是安装过程信息<pre class='alert alert-info'>\n";
                // if (AppsStore::$MESSAGES) {
                //     $msg .= implode("\n", AppsStore::$MESSAGES);
                // }
                // $msg .= "</pre>";
                // self::alert($msg);
                return [false, $message];
                // iJson::error($message);
            }
        }
        return false;
    }

    /**
     * 卸载应用/模板/插件
     */
    public function done_uninstall()
    {
        $id = Request::post('id');
        $id or self::alert('请选择要删除的项目');
        $data = AppsStoreModel::get($id);
        switch ($data['type']) {
            case '0':
                $appid = $data['appid'];
                $admincp = new AppsAdmincp();
                $admincp->do_uninstall($appid);
                break;
            case '1': //template
                $path = iPHP_TEMPLATE_DIR . '/' . $data['app'];
                if ($data['app'] && File::checkDir($path)) {
                    File::rmdir($path);
                }
                break;
            default:
                if ($data['files']) {
                    foreach ($data['files'] as $key => $value) {
                        $path = iPHP_APP_PATH . $value;
                        if ($value && File::check($path)) {
                            if (is_dir($path)) {
                                File::rmdir($path);
                            } elseif (is_file($path)) {
                                File::rm($path);
                            }
                        }
                    }
                }
        }
        AppsStoreModel::delete($id);
        $param = $this->makeParam('delete', $id);
        DeveloperStoreProxy::post('delete', $param);
    }

    /**
     * 安装应用/模板/插件
     */
    public function done_install()
    {
        $sid = Request::post('sid');
        $force = Request::post('force'); //强制安装
        $param = $this->makeParam('install', $sid, [
            'package'=> Request::post('package')
        ]);
        $name = File::name($param['package']);
        $pkgPath = AppsStore::getPkg($name);

        if (!File::exist($pkgPath) || (filemtime($pkgPath) - time() > 3600)) {
            self::alert('安装出错0');
        }

        $arr = DeveloperStoreProxy::post('install', $param, true);
        $store = $arr['data']['store'];
        empty($store) && self::alert('安装出错1');

        $force = 1;
        $backup = '_backup_' . get_date(0, 'YmdHi');
        $apps = Apps::get($store['application']);
        if ($apps) {
            if ($force) {
                AppsModel::update(
                    ['app' => $store['application'] . $backup],
                    ['app' => $store['application']]
                );
            } else {
                $msg = sprintf('%s[%s] 应用已存在', $store['name'], $store['application']);
                self::alert($msg);
            }
        }

        if (is_array($store['data']) && $store['data']['tables']) {
            foreach ($store['data']['tables'] as $tkey => $table) {
                if (DB::hasTable($table)) {
                    if ($force) {
                        DB::rename($table, $table . $backup);
                    } else {
                        $msg = sprintf('[%s] 表已经存在', $table);
                        self::alert($msg);
                    }
                }
            }
        }
        if ($store['type'] == '0') { //app
            $path = iPHP_APP_DIR . '/' . strtolower($store['application']);
        }
        if ($store['type'] == '1') { //template
            $path = iPHP_TEMPLATE_DIR . '/' . $store['application'];
        }

        if (File::checkDir($path)) {
            if ($force) {
                rename($path, $path . $backup);
            } else {
                $ptext = Security::filterPath($path);
                $msg = sprintf(
                    '%s[%s]<br />[%s]目录已存在,<br />程序无法继续安装',
                    $store['name'],
                    $store['application'],
                    $ptext
                );
                self::alert($msg);
            }
        }
        if ($store && $name) {
            AppsStore::getPkg($name);
            // AppsStore::$test = 1;
            AppsStore::$IS_FORCE = $force;

            // DB::beginTransaction();
            try {
                AppsStore::$DATA = $store;
                AppsStore::install($sid, $param['order']);

                // AppsStore::$appId && $url = Admincp::url('apps/edit', ['id'=>AppsStore::$appId]);
                $url = Admincp::url('apps/index');
                AppsStore::$MESSAGES = Security::filterPath(AppsStore::$MESSAGES);
                return [
                    'data' => [
                        'log'  => AppsStore::$MESSAGES,
                        'store'=> $store
                    ],
                    'message' => '安装完成',
                    'url'     => $url
                ];
            } catch (\Exception $ex) {
                DB::rollBack();
                $message = '出现错误中止安装<br />' . $ex->getMessage();
                // $msg .= "以下是安装过程信息<pre class='alert alert-info'>\n";
                // if (AppsStore::$MESSAGES) {
                //     $msg .= implode("\n", AppsStore::$MESSAGES);
                // }
                // $msg .= "</pre>";
                // self::alert($msg);
                return [false, $message];
                // iJson::error($message);
            }
        }
        return false;
    }

    /**
     * 创建订单
     *
     */
    public function done_create()
    {
        $sid = (int)Request::post('sid');
        $store = AppsStoreModel::where('sid', $sid)->get();
        $store && self::alert('已经安装过了');
        $param = $this->makeParam('create', $sid);
        echo DeveloperStoreProxy::post('create', $param);
    }

    /**
     * 获取订单
     *
     */
    public function done_order()
    {
        $sid = (int)Request::post('sid');
        $store = AppsStoreModel::where('sid', $sid)->get();
        if ($store) {
            $args = [
                'update'  => 1,
                'git_sha' => $store['git_sha'],
                'order'   => $store['authkey']
            ];
        }
        $param = $this->makeParam('order', $sid, $args);
        echo DeveloperStoreProxy::post('order', $param);
    }

    /**
     * 支付通知
     *
     */
    public function done_payNotify()
    {
        $sid = (int)Request::post('sid');
        $param = $this->makeParam('payNotify', $sid);
        echo DeveloperStoreProxy::post('payNotify', $param);
    }

    /**
     * 获取应用信息
     *
     * @return void
     */
    public function done_store()
    {
        $sid = (int)Request::post('sid');
        $param = $this->makeParam('store', $sid);
        echo DeveloperStoreProxy::post('store', $param);
    }

    public function done_download()
    {
        $sid = (int)Request::post('sid');
        $store = AppsStoreModel::where('sid', $sid)->get();
        $args = [
            'package'=> Request::post('package')
        ];
        if ($store) {
            $args += [
                'update'  => 1,
                'git_sha' => $store['git_sha'],
                'order'   => $store['authkey'],
            ];
        }
        $param = $this->makeParam('download', $sid, $args);
        $name = File::name($param['package']);
        $pkgPath = AppsStore::getPkg($name);

        if (File::exist($pkgPath) && (filemtime($pkgPath) - time() < 3600)) {
            return true;
        }

        $response = DeveloperStoreProxy::post('download', $param);

        if ($response) {
            if (strpos($response, '<html') !== false) {
                self::alert($response);
            } elseif ($response[0] == '{') {
                exit($response);
            } elseif (substr($response, 0, 2) == 'PK') {
                $dir = dirname($pkgPath);
                File::mkdir($dir);
                File::put($pkgPath, $response); //下载包
                // return $param;
                return true;
            }
        }
    }

    public function done_comment()
    {
        $sid = (int)Request::post('sid');
        $param = $this->makeParam('comment', $sid);
        $param['content'] = Request::post('content');
        echo DeveloperStoreProxy::post('comment', $param);
    }

    public function makeParam($proxy, $id, $args = [])
    {
        $time = time();
        $host = $_SERVER['HTTP_HOST'];
        $key = md5(iPHP_KEY . $id . $host . $time);
        $param = compact('id', 'key', 'host', 'time', 'proxy');
        ksort($param);
        $param['sign'] = md5(http_build_query($param));
        if ($order = Request::post('order')) {
            $param['order'] = $order;
        }
        $args && $param += $args;
        return $param;
    }
    // public static function check_update() {
    //     include self::view("check_update","apps");
    // }
    // public static function do_check_update() {
    //   $storeArray = AppsStoreModel::get_array(array('status'=>'1'));
    //   $dataArray  = apps_store::remote_all('all');
    //   $count = 0;
    //   foreach ((array)$dataArray as $key => $value) {
    //     $is_update = false;
    //     $sid       = $value['id'];
    //     $appconf   = $storeArray[$sid];
    //     if($appconf){
    //       version_compare($value['version'],$appconf['version'],'>')        && $is_update = true;
    //       ($appconf['git_time'] && $value['git_time']>$appconf['git_time']) && $is_update = true;
    //       ($appconf['git_sha'] && $value['git_sha']!=$appconf['git_sha'])   && $is_update = true;
    //     }
    //     if($is_update){
    //         $count++;
    //     }
    //   }
    //   echo '{"code":"1","count":"'.$count.'个更新"}';
    // }
}
