<?php
/*
 * template_lite plugin
 *
 * Type:     function
 * Name:     array
 * Version:  0.1
 * Examples:
 * e1.{array key="value" key1="value1"}
 * e2.{array as="ar" flag="col" a[]="1" b[]="2"}
 * e3.{array as[]="ar" a="1" b="2"} //as[]追加合并
 * {array as="ar" {json}}
 * {array as="ar" []={json}}
 * {array as="ar" [a]='aa'}
 * {array as="ar" a[b][c]='aa'}
 */
function tpl_function_array($params, &$tpl)
{
    $key = $params['assign'] ?: 'array';
    $params['as'] && $key = $params['as'];
    if ($params['as[0]']) {
        $mas = $key = $params['as[0]'];
        $array = $tpl->_vars[$key]?:[];
    }
    $merge = $params['merge'] ? true : false;
    $recursive = $params['recursive'] ? true : false;
    unset($params['assign'],$params['merge'],$params['as'],$params['as[0]'],$params['recursive']);
    View::parseVars($params);
    // Examples:e1
    $merge && $params = array_merge((array)$tpl->_vars[$key], (array)$params);

    // Examples:e2
    // if ($params['flag'] == 'col') {
    //     unset($params['flag']);
    //     foreach ((array)$params as $pk => $pv) {
    //         if ($array[$pk]) {
    //             array_push($array[$pk], $pv);
    //         } else {
    //             $array[$pk] = $pv;
    //         }
    //     }
    //     $tpl->assign($key, $array);
    //     return;
    // }
    // Examples:e3
    if ($mas) {
        if (isset($params['_array'])) {
            $params = $params['_array'];
        }
        if ($array) {
            if($recursive){
                $array = array_merge_recursive($array, $params);
            }else{
                array_push($array, $params);
            }
            $params = $array;
        } else {
            $params = [$params];
        }
    }

    $tpl->assign($key, $params);
}
