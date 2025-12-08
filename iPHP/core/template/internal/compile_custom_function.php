<?php

/**
 * Template Lite compile custom function - template internal module
 *
 * Type:	 template
 * Name:	 compile_custom_function
 */

function compile_custom_function($function, $modifiers, $arguments, &$_result, &$object)
{
    // 检查插件函数是否存在
    if (!($func = $object->_plugin_exists($function, 'function'))) {
        return false;
    }

    // 解析参数并处理引用
    $_args = $object->_parse_arguments($arguments);
    $reference = false;
    $ref_args = null;

    if (isset($_args['&'])) {
        $reference = true;
        $ref_args = $_args['&'];
        unset($_args['&']); // 移除引用标记避免后续处理
    }
    // 构建参数数组代码
    $params = [];
    foreach ($_args as $key => $value) {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value === null) {
            $value = 'null';
        }

        $is_ref = (strpos($value, '$') !== false);
        $is_exp = preg_match('/\S+\s*(?:=|!=|>|<|>=|<=)\s*\S+/i', $value);
        $params[] = sprintf("'%s' => %s$value", $key, ($is_ref &&!$is_exp) ? '&' : '');
    }
    $params_code = 'array(' . implode(',', $params) . ')';
    // 生成PHP代码
    $_result = '<?php ';
    if (!$reference) {
        $_result .= 'echo ';
    }

    if ($modifiers) {
        $func_call = $func . '(' . $params_code . ', $this)';
        $_result .= $object->_parse_modifier($func_call, $modifiers) . '; ';
    } else {
        $func_call = $reference
            ? $func . '(' . $ref_args . ', $this, "' . $function . '")'
            : $func . '(' . $params_code . ', $this, "' . $function . '")';
        $_result .= $func_call . ';';
    }
    $_result .= '?>';
    return $_result;
}
