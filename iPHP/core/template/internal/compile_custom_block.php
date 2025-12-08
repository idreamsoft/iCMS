<?php

/**
 * Template Lite compile custom block - template internal module
 *
 * Type:	 template
 * Name:	 compile_custom_block
 */

function compile_custom_block($function, $modifiers, $arguments, &$_result, &$object)
{
    if ($function[0] == '/') {
        $start_tag = false;
        $function = substr($function, 1);
    } else {
        $start_tag = true;
    }

    // 检查插件函数是否存在
    if (!($func = $object->_plugin_exists($function, 'block'))) {
        return false;
    }
    $reference = false;
    if ($start_tag) {
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
            $params[] = sprintf("'%s' => %s$value", $key, $is_ref ? '&' : '');
        }
        $params_code = 'array(' . implode(',', $params) . ')';

        if ($reference) {
            $_result = '<?php $block_args = &' . $ref_args . ';';
        } else {
            $_result = '<?php $block_args = ' . $params_code . ';';
        }
        // $_result .= '$block_content = '.$function . '($block_args, null, $this); ';
        // $_result .= 'if(!$block_content){';
        $_result .= 'ob_start(); ?>';
    } else {
        $_result .= '<?php $block_content = ob_get_contents(); ob_end_clean(); ';
        $_result .= '$block_flag = ' . $func . '($block_args, $block_content, $this);';
        $modifiers && $_result .= '$block_content = ' . $object->_parse_modifier('$block_content', $modifiers) . '; ';
        $_result .= '?>';
        // $_result .= '}? >';
        $_result .= '<?php if($block_flag===true){ echo $block_content;} ?>';
        $_result .= '<?php unset($block_args,$block_content,$block_flag);?>';
    }
    return true;
}
