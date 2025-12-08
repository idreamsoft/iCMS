<?php

/**
 * iCMS - i Content Management System
 * Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
 *
 * @author icmsdev <master@icmsdev.com>
 * @site https://www.icmsdev.com
 * @licence https://www.icmsdev.com/LICENSE.html
 */

class AppsAccess
{
    public static $callback = null;
    public static $docMap = [
        iPHP_APP      => '{title}管理',
        'manage'      => '{title}管理',
        'index'       => '{title}管理',
        'add'         => '添加{title}',
        'save'        => '保存{title}',
        'update'      => '更新{title}',
        'del'         => '删除{title}',
        'delete'      => '删除{title}',
        'detail'      => '查看{title}',
        'check'       => '检测{title}',
        'copy'        => '克隆{title}',
        'download'    => '下载{title}',
        'get'         => '获取{title}',
        'batch'       => '{title}批量处理',
        'cache'       => '更新{title}缓存',
        'inbox'       => '{title}草稿箱',
        'trash'       => '{title}回收站',
        'route'       => '{title}路由',
        'examine'     => '审核{title}',
        'off'         => '淘汰{title}',
        'config'      => '{title}配置',
        'save_config' => '保存{title}系统配置',
        'user'        => '用户{title}列表',
        'update_sort' => '更新排序',
    ];

    public static function checkbox($url, $name, $reqType)
    {
        $id = substr(md5($url), 0, 16);
        echo '<div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" value="' . strtolower($url) . '" id="' . $id . '" name="access[app][]">
        <label class="form-check-label" for="' . $id . '">
            ' . $name . '
            <span class="badge bg-black-50">/' . ucfirst($url) . '</span>
            <a href="' . admincp::url($url) . '" target="_blank">_</a>
            <span class="badge bg-info-' . $reqType . '">' . $reqType . '</span>
        </label>
        </div>';
    }

    /**
     * 获取所有应用权限
     *
     * @return Array
     */
    public static function all()
    {
        $appArray = Apps::getArray();
        $appArray = array_column($appArray, null, 'app');

        $pattern = iPHP::getAppPath('*', '*Admincp');
        $lists = glob($pattern, GLOB_BRACE);
        $access = [];
        foreach ($lists as $key => $path) {
            preg_match('@.+/app/(\w+)/((\w+)Admincp)\.php@i', $path, $match);
            $app = $match[1];
            $class = $match[2];
            $name = $match[3];
            if ($class) {
                $apps = $appArray[$app];
                $methods = get_class_methods($class);
                $doc = Helper::getDocComment($class);
                $title = $doc['title'] ? str_replace(['{app.name}'], [$apps['title']], $doc['title']) : '';

                if ($doc['auth'] === false || $doc['login'] === false || $doc['role'] === Role::SUPER_RID) {
                    continue;
                }
                if ($class == sprintf('%sAdmincp', ucfirst($app))) {
                    $title = $apps['title'];
                }
                $access[$app][$class] = [
                    'title'  => $title,
                    'class'  => $class,
                    'name'   => $name,
                    'role'   => $doc['role'],
                    'method' => []
                ];
                /**
                 * 获取有批量操作的方法
                 */
                $pattern = sprintf('admincp/%s.%s.batch*', $name, '*');
                $batchPaths = Etc::path($app, $pattern);
                $batchMethods = [];
                foreach ($batchPaths as $key => $value) {
                    preg_match("@/\w+.(\w+).batch.json@", $value, $matchs);
                    $matchs[1] && $batchMethods[$matchs[1]] = true;
                }

                $methodAccess = [];
                foreach ($methods as $key => $method) {
                    $isGet = strpos($method, iPHP_GET_PREFIX) !== false;
                    $isPost = strpos($method, iPHP_POST_PREFIX) !== false;
                    if (strpos($method, '__') === false) {
                        $reflectionMethod = new ReflectionMethod($class, $method);
                        // var_dump($reflectionMethod->class != $class);
                        $do = str_replace([iPHP_POST_PREFIX, iPHP_GET_PREFIX], '', $method);
                        $mdoc = Helper::getDocComment($class, $method);

                        if ($mdoc['auth'] === false || $mdoc['login'] === false || $mdoc['role'] === Role::SUPER_RID) {
                            continue;
                        }
                        $docTitle = AppsAccess::$docMap[$do];
                        $title = $mdoc['title'] ?: $docTitle ?: $method;
                        $title = str_replace(['{title}'], [$doc['title'] ?: $apps['title']], $title);
                        $data = [
                            'request' => '',
                            'title'   => $title,
                            'do'      => $do,
                            'method'  => $method,
                            'role'    => $mdoc['role'],
                            'uri'     => [],
                            'url'     => sprintf('%s/%s', $name, $do),
                        ];
                        if ($mdoc['uri']) {
                            $data['uri'] = $mdoc['uri'];
                        }
                        if ($batchMethods[$do]) {
                            $data['batch'] = AdmincpBatch::getEtcs($name, $do, false);
                        }
                        if ($isGet) {
                            $data['request'] = 'G';
                            $methodAccess[$do] = $data;
                        }
                        if ($isPost) {
                            if ($methodAccess[$do]) {
                                $methodAccess[$do]['request'] = 'GP';
                            } else {
                                $data['request'] = 'P';
                                $methodAccess[$do] = $data;
                            }
                        }
                        // if($reflectionMethod->class != $class){
                        //     $access[$app][$class]['reflection'] = $methodAccess;
                        // }else{
                        // }
                        $access[$app][$class]['method'] = $methodAccess;
                    }
                }
            }
        }
        return [$access, $appArray];
    }

    /**
     * 获取有批量操作的方法
     */
    public static function batchMethods($app, $name)
    {
        $pattern = sprintf('admincp/%s.%s.batch*', $name, '*');
        $batchPaths = Etc::path($app, $pattern);
        $batchMethods = [];
        foreach ($batchPaths as $key => $value) {
            preg_match("@/\w+.(\w+).batch.json@", $value, $matchs);
            $matchs[1] && $batchMethods[$matchs[1]] = true;
        }
        return $batchMethods;
    }

    public static function methods($app, $name, $class, $title)
    {
        $batchMethods = self::batchMethods($app, $name);
        $methods = get_class_methods($class);
        $methodAccess = [];
        foreach ($methods as $key => $method) {
            $isGet = strpos($method, iPHP_GET_PREFIX) !== false;
            $isPost = strpos($method, iPHP_POST_PREFIX) !== false;
            if (strpos($method, '__') === false) {
                $reflectionMethod = new ReflectionMethod($class, $method);
                // var_dump($reflectionMethod->class != $class);
                $do = str_replace([iPHP_POST_PREFIX, iPHP_GET_PREFIX], '', $method);
                $mdoc = Helper::getDocComment($class, $method);

                if ($mdoc['auth'] === false || $mdoc['login'] === false || $mdoc['role'] === Role::SUPER_RID) {
                    continue;
                }
                $docTitle = AppsAccess::$docMap[$do];
                $mtitle = $mdoc['title'] ?: $docTitle ?: $method;
                $mtitle = str_replace(['{title}'], [$title], $mtitle);
                $data = [
                    'request'    => '',
                    'title'      => $mtitle,
                    'do'         => $do,
                    'method'     => $method,
                    'role'       => $mdoc['role'],
                    'uriList'    => [],
                    'batchList'  => [],
                    'url'        => sprintf('/%s/%s', $name, $do),
                ];
                $data['id'] = substr(md5($data['url']), 0, 16);
                if ($mdoc['uri']) {
                    foreach ((array)$mdoc['uri'] as $_url => $desc) {
                        if (strpos($_url, '/') !== 0) {
                            $_url = $data['url'] . '/' . $_url;
                        }
                        $data['uriList'][] = [
                            'id'   => substr(md5($_url), 0, 16),
                            'title'=> $desc,
                            'url'  => $_url
                        ];
                    }
                }
                if ($batchMethods[$do]) {
                    $batchArr = AdmincpBatch::getEtcs($name, $do, false);
                    $batch_url = sprintf('/%s/%s', $name, 'batch');
                    $data['batch'] = [
                        'id'      => substr(md5($batch_url), 0, 16),
                        'title'   => lang('batch'),
                        'url'     => $batch_url,
                        'request' => 'P',
                    ];
                    foreach ($batchArr as $bk => $bv) {
                        $_url = sprintf('%s/%s', $batch_url, $bk);
                        $data['batchList'][] = [
                            'id'      => substr(md5($_url), 0, 16),
                            'title'   => $bv['name'],
                            'url'     => $_url,
                            'request' => 'P',
                        ];
                    }
                    // $data['batch'] = AdmincpBatch::getEtcs($name, $do, false);
                }
                if ($isGet) {
                    $data['request'] = 'G';
                    $methodAccess[$do] = $data;
                }
                if ($isPost) {
                    if ($methodAccess[$do]) {
                        $methodAccess[$do]['request'] = 'GP';
                    } else {
                        $data['request'] = 'P';
                        $methodAccess[$do] = $data;
                    }
                }
                // if($reflectionMethod->class != $class){
                //     $access[$app][$class]['reflection'] = $methodAccess;
                // }else{
                // }
            }
        }
        // $methodAccess = array_values($methodAccess);
        return $methodAccess;
    }

    public static function get(&$dataArray = null)
    {
        $accessArray = [];
        $htmlArray = [];
        // $dataArray = array();
        foreach (Apps::getArray() as $key => $appData) {
            //  content 为虚拟APP ，node
            if ($appData['app'] == 'content' || $appData['app'] == 'node') {
                continue;
            }
            list($path, $obj_name) = Apps::getPathArr($appData['app'], 'admincp');

            $method_access = [];
            if (is_file($path) || $appData['apptype'] == Apps::CONTENT_TYPE) {
                $app_name = $appData['app'];
                if ($appData['apptype'] == Apps::CONTENT_TYPE) {
                    $obj_name = 'contentAdmincp';
                    $app_name = 'content';
                }
                try {
                    $vars = get_class_vars($obj_name);
                    //自定义基础权限
                    $vars['ACCESC_BASE'] && $appData['ACCESC_BASE'] = $vars['ACCESC_BASE'];
                    $method_access[$appData['app']] = [
                        'data' => self::getMethod($obj_name, $appData, $accessArray, $vars), //方法信息
                        'app'  => $appData //应用信息
                    ];
                } catch (\Exception $ex) {
                    //throw $ex;
                }

                //查找 ArticleCategoryAdmincp.php 这样的子应用
                // $pattern = Apps::getPathArr($app_name, 'admincp', '*');
                $pattern = Application::path($app_name, ucfirst($app_name) . '*Admincp');
                // var_dump($pattern);
                $subPaths = glob($pattern);
                // var_dump($subPaths);

                if ($subPaths) {
                    foreach ($subPaths as $pkey => $subPath) {
                        preg_match('/(\w+)\.php/', $subPath, $match);
                        $subAppData = $appData;
                        if ($match[1]) {
                            if ($appData['apptype'] == Apps::CONTENT_TYPE) {
                                $subAppData['app'] = $appData['app'] . 'Node';
                                $obj_name = 'ContentNodeAdmincp';
                            } else {
                                $pieces = preg_split('/(?<=\w)(?=[A-Z])/', $match[1]);
                                if (empty($pieces[2])) {
                                    continue;
                                }

                                // $subAppData['app'] = lcfirst($pieces[0]);
                                // var_dump($pieces);
                                // $subAppData['asd'] = $pieces[0];
                                $obj_name = $match[1];
                                $subAppData['app'] = lcfirst(substr($obj_name, 0, -7));
                            }

                            try {
                                $vars = get_class_vars($obj_name);
                                //自定义基础权限
                                $vars['ACCESC_BASE'] && $subAppData['ACCESC_BASE'] = $vars['ACCESC_BASE'];
                                if ($vars['ACCESC_TITLE']) {
                                    $vars['ACCESC_TITLE'] = str_replace('{app.name}', $subAppData['name'], $vars['ACCESC_TITLE']);
                                    $subAppData['title'] = $vars['ACCESC_TITLE'];
                                    $subAppData['name'] = $vars['ACCESC_TITLE'];
                                }
                                $method_access[$subAppData['app']] = [
                                    'data' => self::getMethod($obj_name, $subAppData, $accessArray, $vars),
                                    'app'  => $subAppData,
                                ];
                            } catch (\Exception $ex) {
                                //throw $ex;
                            }
                        }
                    }
                }
            }
            $needa = array_fill_keys(['id', 'app', 'name', 'title'], 1);
            foreach ($method_access as $makey => $access) {
                $dataArray[$makey] = array_intersect_key($access['app'], $needa);
                //下面多级模式 使用 access.app2.html
                // list($a,$b) = explode('_',$makey);
                // $b OR $b = $a;
                // $dataArray[$a][$b] = array_intersect_key($access['app'], $needa);
                if (self::$callback['app:access'] && is_callable(self::$callback['app:access'])) {
                    $htmlArray[$makey] = call_user_func_array(
                        self::$callback['app:access'],
                        [$access['data'], $access['app']]
                    );
                }
            }
        }
        // var_dump($accessArray);
        // exit;
        Cache::set('app/access', $accessArray, 0);
        return $htmlArray;
    }

    public static function getMethod($obj_name, $appData, &$accessArray, $vars = null)
    {
        $class_methods = get_class_methods($obj_name);
        foreach ($class_methods as $key => $method) {
            if (isset($vars['ACCESC_SELF_METHOD']) && $vars['ACCESC_SELF_METHOD']) {
                $reflectionMethod = new ReflectionMethod($obj_name, $method);
                // var_dump($reflectionMethod->class);
                $flag = is_array($vars['ACCESC_SELF_METHOD']) ?
                    in_array($method, $vars['ACCESC_SELF_METHOD']) :
                    $vars['ACCESC_SELF_METHOD'];
                if ($reflectionMethod->class != $obj_name && $flag) { //方法只继承于父类 自身无定义
                    continue;
                }
            }

            $res = self::getMethodDoc($appData, iPHP_GET_PREFIX, $obj_name, $method);
            if (is_array($res)) {
                list($do, $html, $url, $text, $doc) = $res;
                if (isset($doc['access']) && $doc['access'] === false) {
                    continue;
                }
                $do && $access[iPHP_GET_PREFIX][$do] = $html;
                $accessArray[$url] = $text;
                if ($do == 'batch') {
                    // var_dump($url,$text);
                    $batchArray = AdmincpBatch::getEtcs($appData['app']);
                    $baccess = [];
                    $bi = 0;
                    if ($batchArray) {
                        foreach ($batchArray as $batch => $item) {
                            $baccess = [$url . '/' . $batch, $appData['title'] . '批量：'];
                            $baccess[1] = $item['name'];
                            $item['name'] == 'divider' && $baccess[1] = '--分隔符--';
                            $accessArray[$baccess[0]] = $baccess[1];
                            $access[iPHP_GET_PREFIX]['batch:access'][] = $baccess;
                        }
                    }
                }
            }
            $res = self::getMethodDoc($appData, iPHP_POST_PREFIX, $obj_name, $method);
            if (is_array($res)) {
                list($do, $html, $url, $text, $doc) = $res;
                if (isset($doc['access']) && $doc['access'] === false) {
                    continue;
                }
                $do && $access[iPHP_POST_PREFIX][$do] = $html;
                $accessArray[$url] = $text;
            }
        }
        return $access;
    }

    public static function getMethodDoc($appData, $prefix, $obj_name, $method)
    {
        if (stripos($method, $prefix) !== false && stripos($method, '__') === false) {
            $doc = Helper::getDocComment($obj_name, $method);
            $do = str_replace($prefix, '', $method);
            if ($doc) {
                $title = (string) $doc['title'];
                if (!$doc['access']) {
                    return false;
                }
            } else {
                $title = self::$docMap[$do];
            }
            $title = $title ?: $method;
            $title = str_replace('{title}', $appData['title'], $title);
            $ukey = strtolower(substr($prefix, 0, -1));
            $app = substr($obj_name, 0, -7);
            // if(stripos($obj_name, 'content')!==false){
            //     var_dump($appData);
            // }
            // $app = $appData['app'];
            // var_dump($app,$obj_name);
            $url = Admincp::url([lcfirst($app), $do]);
            $url = Admincp::urlPath($url);
            Menu::$TITLES[$url] && $title = Menu::$TITLES[$url];

            if ($prefix == iPHP_POST_PREFIX) {
                // $title .= sprintf("%s::%s",$obj_name,$method);
                // $title .= sprintf(" app=%s&action=%s",lcfirst($app),$do);
            } else {
                // $title .= sprintf(" app=%s",lcfirst($url));
            }

            $html = call_user_func_array(self::$callback['method:access'], [$url, $title, $do]);
            $text = $appData['name'] . ' - ' . $title;
            return [$do, $html, $url, $text, $doc];
        }
    }
}
