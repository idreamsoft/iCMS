<?php

/**
 * Template Lite
 *
 * Type:	 compile
 * Name:	 section_start
 *
 * ADDED: { include file='./filename' import=true }
 */
/**
 * [compile_include description]
 *
 * @param   [type]  $arguments  [$arguments description]
 * @param   [object]  $object     TemplateLite
 *
 * @return  [type]              [return description]
 */
function compile_include($arguments, &$object)
{
	$_args            = $object->_parse_arguments($arguments);
	$arg_list         = array();
	$_args['file'] or $object->throwError("missing 'file' attribute in include tag", __FILE__, __LINE__);
	$vars_output = '';
	foreach ($_args as $arg_name => $arg_value) {
		if ($arg_name == 'file') {
			strpos($arg_value, '..') && $object->throwError("'file' attribute has '..'");
			$include_file = $arg_value;
			continue;
		} else if ($arg_name == 'assign') {
			$assign_var = $arg_value;
			continue;
		}
		if (is_bool($arg_value)) {
			$arg_value = $arg_value ? 'true' : 'false';
		}
		if (isset($assign_var)) {
			$arg_list[] = "'$arg_name' => $arg_value";
		} else {
			$value = $object->_dequote($arg_value);
			if (strpos($value, '$this') === false) {
				$value = sprintf('"%s"', $value);
			}
			$vars_output .= sprintf('<?php $this->_vars["%s"] = %s; ?>', $arg_name, $value);
		}
	}

	$object->_include_file = true;
	if (isset($assign_var)) {
		$output = sprintf(
			'<?php 
			$_templatelite_tpl_vars = $this->_vars; 
			$this->_include_file = true;
			$this->assign(%s, $this->_fetch_compile_include(%s, [%s]));
			$this->_vars = $_templatelite_tpl_vars;
			$this->_include_file = false;
			unset($_templatelite_tpl_vars);
			?>',
			$assign_var,
			$include_file,
			implode(',', (array)$arg_list)
		);
	} else {
		$include_file = $object->_dequote($include_file);
		if (strpos($include_file, '$this') !== false) {
			$output = sprintf(
				'<?php echo $this->_fetch_compile_include(%s,[%s]);?>',
				$include_file,
				implode(',', (array)$arg_list)
			);
		} else {
			if ($_args['import']) {
				if ($_args['import'] == "html") {
					$output = $object->_fetch_compile($include_file, true);
				} else {
					$file   = $object->_fetch_compile($include_file, 'file');
					$output = sprintf('<?php include "%s"; ?>', $file);
				}
			} else {
				$output = $object->_fetch_compile($include_file, 'code');
			}
		}
	}
	$object->_include_file = false;
	return $vars_output . $output;
}
