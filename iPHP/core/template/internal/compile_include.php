<?php

/**
 * Template Lite
 *
 * Type:	 compile
 * Name:	 section_start
 *
 * ADDED: { include file='./filename' import=true }

 * compile_include description
 *
 * @param   [type]  $arguments  [$arguments description]
 * @param   [object]  $object     TemplateLite
 *
 * @return  [type]              [return description]
 */

function compile_include($arguments, TemplateLiteCompiler &$object)
{
    $arg_list = [];
    $vars_output = '';

    // 解析标签参数
    $_args = $object->_parse_arguments($arguments);

    // 必须包含 file 属性
    if (empty($_args['file'])) {
        $object->throwError("missing 'file' attribute in include tag", __FILE__, __LINE__);
    }

    // 防止路径穿越攻击
    if (strpos($_args['file'], '..') !== false) {
        $object->throwError("'file' attribute contains '..'");
    }

    $include_file = $_args['file'];
    $assign = isset($_args['assign']) ? $_args['assign'] : null;
    unset($_args['file'], $_args['assign']);

    // 处理传入的参数
    foreach ($_args as $arg_name => $arg_value) {
        // 布尔值转换为 true/false 字符串
        if (is_bool($arg_value)) {
            $arg_value = $arg_value ? 'true' : 'false';
        }

        if ($assign) {
            $arg_list[] = "'$arg_name' => $arg_value";
        } else {
            $value = $object->_dequote($arg_value);
            if (strpos($value, '$this') === false) {
                $value = '"' . $value . '"';
            }
            $vars_output .= sprintf('<?php $this->_vars["%s"] = %s; ?>', $arg_name, $value);
        }

        // 保存当前作用域参数
        // $object->_args[$arg_name] = $object->_dequote($arg_value);
    }

    // 设置标记表示正在处理 include 文件
    $object->_include_file = true;

    // 判断是否需要 assign 结果到变量
    if ($assign) {
        $output = sprintf(
            '<?php 
                $this->_include_file = true;
                $this->assign(%s, $this->_fetch_compile_include(%s, [%s]));
                $this->_include_file = false;
            ?>',
            $assign,
            var_export($include_file, true),
            implode(',', $arg_list)
        );
    } else {
        $include_file = $object->_dequote($include_file);
        if (strpos($include_file, '$this') !== false) {
            // 动态文件名
            $output = sprintf(
                '<?php echo $this->_fetch_compile_include(%s, [%s]); ?>',
                $include_file,
                implode(',', $arg_list)
            );
        } else {
            // 静态文件名
            if ($_args['import']) {
                if ($_args['import'] === 'html') {
                    // 直接导入 HTML 内容
                    $output = $object->_fetch_compile($include_file, true);
                } else {
                    // 使用 include 导入文件
                    $file_path = $object->_fetch_compile($include_file, 'file');
                    $output = sprintf('<?php include "%s"; ?>', $file_path);
                }
            } else {
                try {
                    // 正常编译并嵌入模板内容
                    $output = $object->_fetch_compile($include_file, 'code');
                } catch (\Exception $ex) {
                    // 捕获异常并输出错误信息
                    if (!$_args['throw'] === 'false') {
                        throw $ex;
                    }
                }
            }
        }
    }

    // 清除标记
    $object->_include_file = false;

    return $vars_output . $output;
}
