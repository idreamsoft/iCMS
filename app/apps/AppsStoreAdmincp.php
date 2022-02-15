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
        $this->id = (int) Request::get('id');
        Menu::setData('getNav.APP', Admincp::$APP_NAME);
    }
    public function do_app(){
        $this->do_index('app');
    }
    public function do_plugin(){
        $this->do_index('plugin');
    }
    public function do_template(){
        $this->do_index('template');
    }
    public function do_index($stype=null)
    {
        $stype = $stype?:Request::sget('stype');
        $uri = sprintf('%s&stype=%s', APP_URL, $stype?:'app');
        Menu::setData('nav.active', $uri);
        $this->displaying($stype);
    }

    public function displaying($name = null, $title = null)
    {
        // $storeArray = AppsStore::get_array(array('type' => $type));
        // $dataArray  = AppsStore::remote_all($app);
        AdmincpView::set('breadcrumb', false);
        $html = self::getHtml($name);
        $storeList = AppsStoreModel::withoutField('files')->select();
        $storeList = array_column($storeList, null, 'sid');
        $id = Request::get('id');
        $storeData = [];
        if ($id) {
            $storeData = AppsStoreModel::withoutField('files')->get(['sid' => $id]);
            if ($storeData) {
                $storeData['addTimeText'] = get_date($storeData['addtime'], 'Y-m-d H:i:s');
                $storeData['upTimeText']  = get_date($storeData['uptime'], 'Y-m-d H:i:s');
                $storeData['gitTimeText'] = get_date($storeData['git_time'], 'Y-m-d H:i:s');
            }
        }
        include self::view("store", "apps");
    }
    public static function getHtml($url = 'home')
    {
        $id   =  Request::get('id');
        $id   && $url = sprintf('store/%d',  $id);
        $cid  =  Request::get('cid');
        $cid  && $url = sprintf('list/%d',   $cid);
        $list =  Request::get('list');
        $list && $url = sprintf('list/%s',  Request::get('do'));

        $param  = array(
            'pageSize' => Request::get('pageSize'),
            'orderBy'  => Request::get('orderBy'),
            'page'     => Request::get('page'),
            'keywords' => Request::get('keywords'),
            'tid'      => Request::get('tid'),
        );
        $html = DeveloperStore::get($url, $param);
        return $html;
    }
    /**
     * [卸载应用/模板/插件]
     * @return [type] [description]
     */
    public function do_update()
    {
        $sid = Request::post('id');
        $url = Request::post('url');
        $orderkey = Request::post('orderkey');
        $force = Request::post('force'); //强制安装
        // $force = 1;
        $store = Cache::get('store/' . $sid);
        $name = File::name($url);
        empty($store) && self::alert($store['name'] . '信息已经过期，请重新获取');

        if ($store && $name) {
            AppsStore::getPkg($name);
            // AppsStore::$IS_TEST = 1;
            AppsStore::$IS_FORCE = $force;
            AppsStore::$IS_UPDATE = 1;

            DB::beginTransaction();
            try {
                AppsStore::$DATA = $store;
                AppsStore::install($sid, $orderkey);
                DB::commit();
                return ['message' => AppsStore::$MESSAGES];
            } catch (\Exception $ex) {
                DB::rollBack();
                $msg = "出现错误中止更新\n";
                $msg .= $ex->getMessage() . "\n";
                return $msg;
                // self::alert($msg);
            }
        }
    }
    /**
     * [卸载应用/模板/插件]
     * @return [type] [description]
     */
    public function do_uninstall()
    {
        $id = Request::post('id');
        $id or self::alert("请选择要删除的项目");
        $data = AppsStoreModel::get($id);
        switch ($data['type']) {
            case '0':
                $appid = $data['appid'];
                $admincp = new AppsAdmincp();
                $admincp->do_uninstall($appid);
                break;
            case '1': //template
                $path = iPHP_TPL_DIR . '/' . $data['app'];
                if (File::checkDir($path)) {
                    File::rmdir($path);
                }
                break;
            default:
                if ($data['files']) foreach ($data['files'] as $key => $value) {
                    $path = iPHP_PATH . $value;
                    if (File::check($path)) {
                        if (is_dir($path)) {
                            File::rmdir($path);
                        } elseif (is_file($path)) {
                            File::rm($path);
                        }
                    }
                }
        }
        AppsStoreModel::delete($id);
        $param = $this->makeParam($id);
        DeveloperStore::post('delete', $param);
        // self::success('卸载完成');
    }
    /**
     * [安装应用/模板/插件]
     * @return [type] [description]
     */
    public function do_install()
    {
        $sid = Request::post('id');
        $url = Request::post('url');
        $orderkey = Request::post('orderkey');
        $force = Request::post('force'); //强制安装
        $force = 1;
        $store = Cache::get('store/' . $sid);
        $name = File::name($url);
        empty($store) && self::alert($store['name'] . '信息已经过期，请重新获取');

        $backup = '_backup_' . get_date(0, "YmdHi");
        $apps = Apps::get($store['application']);
        if ($apps) {
            if ($force) {
                AppsModel::update(
                    array('app' => $store['application'] . $backup),
                    array('app' => $store['application'])
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
            $path = iPHP_TPL_DIR . '/' . $store['application'];
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

            DB::beginTransaction();
            try {
                AppsStore::$DATA = $store;
                AppsStore::install($sid, $orderkey);
                DB::commit();

                AppsStore::$appId && $url = ADMINCP_URL . "=apps&do=edit&id=" . AppsStore::$appId;
                AppsStore::$MESSAGES = Security::filterPath(AppsStore::$MESSAGES);
                return ['message' => AppsStore::$MESSAGES, 'url' => $url];
            } catch (\Exception $ex) {
                DB::rollBack();
                $message = "出现错误中止安装<br />" . $ex->getMessage();
                // $msg .= "以下是安装过程信息<pre class='alert alert-info'>\n";
                // if (AppsStore::$MESSAGES) {
                //     $msg .= implode("\n", AppsStore::$MESSAGES);
                // }
                // $msg .= "</pre>";
                // self::alert($msg);
                // return ['message' => $message];
                iJson::error($message);
            }
        }
    }
    /**
     * [do_getPayQrcode description]
     *
     * @return  [json]  {orderkey,qrcode}
     */
    public function do_getPayQrcode()
    {
        $id = (int)Request::post('id');
        $param = $this->makeParam($id);
        echo DeveloperStore::post('getPayQrcode', $param);
    }
    /**
     * [do_payNotify description]
     *
     * @return  [json]  {hash: "ad532779298bde94016612e8ceeea578",orderkey: "d655a72963cee346ff92a46f79c478c3",time: 1604292900,transaction_id: "2020110222001466145718928179"}
     */
    public function do_payNotify()
    {
        $id = (int)Request::post('id');
        $param = $this->makeParam($id);
        echo DeveloperStore::post('payNotify', $param);
    }

    public function do_get()
    {
        $id = (int)Request::post('id');
        $param = $this->makeParam($id);
        $json = DeveloperStore::post('get', $param);
        $array = json_decode($json, true);
        $array['data'] && Cache::set('store/' . $id, $array['data'], 86400);
        echo $json;
    }
    public function do_download()
    {
        $id      = (int)Request::post('id');
        $param   = $this->makeParam($id);
        $name    = File::name($param['url']);
        $pkgPath = AppsStore::getPkg($name);

        if (File::exist($pkgPath) && (filemtime($pkgPath) - time() < 3600)) {
            return $param;
        }

        $response = DeveloperStore::post($param['url'], $param);
        if ($response) {
            if ($response[0] == '{') {
                exit($response);
            } else {
                $dir = dirname($pkgPath);
                File::mkdir($dir);
                File::put($pkgPath, $response); //下载包
                return $param;
            }
        }
    }

    public function makeParam($id, $args = null)
    {
        $post  = Request::post();
        unset($post['id'], $post['key'], $post['host'], $post['time']);
        $time  = time();
        $host  = $_SERVER['HTTP_HOST'];
        $key   = md5(iPHP_KEY . $id . $host . $time);
        $param = compact('id', 'key', 'host', 'time');
        $post && $param += $post;
        ksort($param);
        $param['sign'] = md5(http_build_query($param));
        // $param['query'] = http_build_query($param);
        // print_R($param);
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
