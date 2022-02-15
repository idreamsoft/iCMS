<?php
/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
class Vendor {
    public static $name = null;
    public static $dir = 'src';

    public static function loader($class) {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $path = str_replace(self::$name . DIRECTORY_SEPARATOR, '', $path);
        $file = iPHP_LIB . '/'.self::$name.'/'.self::$dir.'/' . $path . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
    public static function register($name,$dir='src') {
        self::$name = $name;
        self::$dir  = $dir;
        spl_autoload_register(array(__CLASS__, 'loader'));
    }
    public static function run($name, $args = null,$self=false) {
        $vendor = sprintf('/%s.php',$name);
        $path = iPHP_APP_VENDOR.$vendor;
        $paths[] = $path;
        is_file($path) OR $path = iPHP_VENDOR.$vendor;
        if(!is_file($path)){
            $paths[] = $path;
            $msg = sprintf('Unable to load Class/Function "%s",in paths "%s"', $name, implode(' Or ', $paths));
            throw new sException($msg, 1);
        }
        require_once $path;
        
        if (function_exists($name)) {
            return call_user_func_array($name, is_null($args)?$args:(array)$args);
        } else {
            $class_name = 'Vendor'.ucfirst($name);
            $flag = class_exists($class_name,false);
            
            if(!$flag && $self){
                $class_name = $name;
                $flag = class_exists($class_name,false);
            }
            if($flag) {
                if($args === null){
                    return new $class_name;
                }
                if (method_exists($class_name, '__initialize')){
                    return call_user_func_array(array($class_name,'__initialize'), (array)$args);
                }else{
                    return new $class_name($args);
                }
            }else{
                return false;
            }
        }
    }
}
