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
    const NO_REQ_ACCESS = '#NO:ACCESS#';
    public static $docMap = array(
        iPHP_APP      => "{title}管理",
        'manage'      => "{title}管理",
        'index'       => "{title}管理",
        'add'         => "添加{title}",
        'save'        => "保存{title}",
        'update'      => "更新{title}",
        'del'         => "删除{title}",
        'delete'      => "删除{title}",
        'check'       => "检测{title}",
        'copy'        => "克隆{title}",
        'download'    => "下载{title}",
        'get'         => "获取{title}",
        'batch'       => "{title}批量处理",
        'cache'       => "更新{title}缓存",
        'ajaxtree'    => "获取{title}JSON数据",
        'inbox'       => "{title}草稿箱",
        'trash'       => "{title}回收站",
        'route'       => "{title}路由",
        'examine'     => "审核{title}",
        'off'         => "淘汰{title}",
        'config'      => "{title}配置",
        'save_config' => "保存{title}系统配置",
        'user'        => "用户{title}列表",
        'update_sort' => "更新排序",
    );
    public static function get(&$dataArray = null)
    {
        $accessArray = array();
        $htmlArray = array();
        // $dataArray = array(); 
        foreach (Apps::getArray() as $key => $appData) {
            //  content 为虚拟APP ，node
            if ($appData['app'] == 'content' || $appData['app'] == 'node') {
                continue;
            }
            list($path, $obj_name) = Apps::getPathArr($appData['app'], 'admincp');

            $method_access = array();
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
                    $method_access[$appData['app']] = array(
                        'data' => self::getMethod($obj_name, $appData, $accessArray, $vars), //方法信息
                        'app' => $appData //应用信息
                    );
                } catch (\Exception $ex) {
                    //throw $ex;
                }

                //查找 ArticleCategoryAdmincp.php 这样的子应用
                // $pattern = Apps::getPathArr($app_name, 'admincp', '*');
                $pattern = iAPP::path($app_name, ucfirst($app_name) . '*Admincp');
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
                                if (empty($pieces[2])) continue;

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
                                $method_access[$subAppData['app']] = array(
                                    'data' => self::getMethod($obj_name, $subAppData, $accessArray, $vars),
                                    'app' => $subAppData,
                                );
                            } catch (\Exception $ex) {
                                //throw $ex;
                            }
                        }
                    }
                }
            }
            $needa = array_fill_keys(array("id", "app", "name", "title"), 1);
            foreach ($method_access as $makey => $access) {
                $dataArray[$makey] = array_intersect_key($access['app'], $needa);
                //下面多级模式 使用 access.app2.html
                // list($a,$b) = explode('_',$makey);
                // $b OR $b = $a; 
                // $dataArray[$a][$b] = array_intersect_key($access['app'], $needa);
                if (self::$callback['app:access'] && is_callable(self::$callback['app:access'])) {
                    $htmlArray[$makey] = call_user_func_array(
                        self::$callback['app:access'],
                        array($access['data'], $access['app'])
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
                    $baccess = array();
                    $bi = 0;
                    if ($batchArray) foreach ($batchArray as $batch => $item) {
                        $baccess = array($url . '&batch=' . $batch, $appData['title'] . '批量：');
                        $baccess[1] = $item['name'];
                        $item['name'] == 'divider' && $baccess[1] = '--分隔符--';
                        $accessArray[$baccess[0]] = $baccess[1];
                        $access[iPHP_GET_PREFIX]['batch:access'][] = $baccess;
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
            $doc = iPHP::getDocComment($obj_name, $method);
            $do = str_replace($prefix, '', $method);
            if ($doc) {
                $title = (string) $doc['desc'];
                if (stripos($title, self::NO_REQ_ACCESS) !== false) {
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
            $url = Admincp::makeQS(array(lcfirst($app), $ukey => $do));
            Menu::$TITLES[$url] && $title = Menu::$TITLES[$url];

            if ($prefix == iPHP_POST_PREFIX) {
                // $title .= sprintf("%s::%s",$obj_name,$method);
                // $title .= sprintf(" app=%s&action=%s",lcfirst($app),$do);
            } else {
                // $title .= sprintf(" app=%s",lcfirst($url));
            }

            $html = call_user_func_array(self::$callback['method:access'], array($url, $title, $do));
            $text = $appData['name'] . ' - ' . $title;
            return array($do, $html, $url, $text, $doc);
        }
    }
}
