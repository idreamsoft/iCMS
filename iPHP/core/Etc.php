<?php
class Etc
{
    public static function path($app, $name = 'route', $flag = false)
    {
        $path = iAPP::path($app);
        if (strpos($name, '||') !== false) {
            $names = explode('||', $name);
            $result = [];
            foreach ($names as $key => $name) {
                $pattern = sprintf('%setc/%s.json', $path, $name);
                $files = glob($pattern, GLOB_BRACE);
                $result = array_merge($result, $files);
            }
            return $result;
        }
        $file = sprintf('%setc/%s.json', $path, $name);

        if ($flag) return $file;

        if ($app == '*' || strpos($name, '*') !== false) {
            return glob($file, GLOB_BRACE);
        } else {
            return is_file($file) ? $file : null;
        }
    }
    public static function many($app, $name = 'route', $flag = false, $sort = 'sort')
    {
        $paths = self::path($app, $name);
        // iDebug::$DATA['etc.many'][] = $paths;
        // var_dump($app, $name);
        // var_dump($paths);
        $result = [];
        if ($paths) {
            $paths = (array)$paths;
            usort($paths, function ($a, $b) {
                $al = strlen($a);
                $bl = strlen($b);
                if ($al == $bl) {
                    return 0;
                }
                return ($al < $bl) ? -1 : 1;
            });
            // var_dump($paths);

            foreach ($paths as $key => $path) {
                if (is_file($path)) {
                    $json = file_get_contents($path);
                    $array = json_decode($json, true);
                    // var_dump($json);
                    // exit;
                    // var_dump($array);
                    // exit;
                    // $array = array_column($array, null, 'id');
                    if (json_last_error()) {
                        $msg = sprintf('%s %s', $path, json_last_error_msg());
                        continue;
                        // throw new sException($msg);
                    }
                    if ($array) {
                        if ($flag === true) {
                            $result = array_merge($result, $array);
                        } else if ($flag === 1) {
                            $path = str_replace(iPHP_PATH, '', $path);
                            $result[$path] = $array;
                        } else {
                            $result[] = $array;
                        }
                    }
                }
            }
            if ($flag === true) {
                $sort && sortKey($result, $sort);
            }
        }
        return $result;
    }
    public static function get($app, $name = 'route')
    {
        return self::data($app, $name);
    }
    public static function set($app, $name = 'route', $data = null)
    {
        return self::data($app, $name, $data);
    }
    public static function data($app, $name = 'route', $data = null)
    {
        $path = self::path($app, $name,true);

        if ($data === null) {
            if (is_file($path)) {
                $json = file_get_contents($path);
                return json_decode($json, true);
            }
            return [];
        } else {
            if (empty($data)) {
                return is_file($path) ? File::rm($path) : false;
            } else {
                File::mkdir(dirname($path));
                is_array($data) or $data = json_decode($data, true);
                $json = jsonFormat($data);
                file_put_contents($path, $json);
                return $path;
            }
        }
    }
    public static function mergeRecursive($many, &$array, $unique = true)
    {
        foreach ($many as $key => $value) {
            $array = array_merge_recursive($array, $value);
        }
        $unique && array_walk($array, array(__CLASS__, 'arrayUnique'));
    }
    public static function arrayUnique(&$items)
    {
        if (is_array($items)) foreach ($items as $key => $value) {
            if (is_array($value)) {
                $value = array_unique($value);
                is_null($value[0]) or $value = $value[0];
                $items[$key] = $value;
            }
        }
    }
}
