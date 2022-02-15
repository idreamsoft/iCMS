<?php
// namespace iPHP\core;

/*
 * Project:	template_lite, a smarter template engine
 * Author:	Paul Lockaby <paul@paullockaby.com>, Mark Dickenson <akapanamajack@sourceforge.net>
 * Copyright:	2003,2004,2005 by Paul Lockaby, 2005,2006 Mark Dickenson
 */
define('iTEMPLATE_DIR', __DIR__ . '/template');

class TemplateLite
{
	// public configuration variables
	public $left_delimiter            = "{{";		// the left delimiter for template tags
	public $right_delimiter           = "}}";		// the right delimiter for template tags
	public $template_dir              = "template";	// where the templates are to be found
	public $plugins_dir               = "plugins";	// where the plugins are to be found
	public $compile_dir               = "cache";	// the directory to store the compiled files in
	public $template_callback         = null;	// the directory to store the compiled files in

	public $php_extract_vars          = false;	// Set this to true if you want the $this->_tpl variables to be extracted for use by PHP code inside the template.
	public $php_handling              = "PHP_QUOTE"; //2007-7-23 0:01 quote php tags
	public $default_modifiers         = array();
	public $debugging                 = false;
	public $remove_compile_eol        = true;
	public $error_reporting_header 	  = null;
	public $reserved_template_varname = 'TemplateLite';
	public $reserved_func_name 		  = 'FuncName';
	public $reserved_func_method      = 'FuncMethod';

	// private internal variables
	public $_vars                     = array();	// stores all internal assigned variables
	public $_plugins                  = array('modifier' => array(), 'function' => array(), 'block' => array(), 'compiler' => array());
	public $_linenum                  = 0;		// the current line number in the file we are processing
	public $_file                     = "";		// the current file we are processing
	public $_include_file             = false;		// the current file we are processing
	public $_version                  = 'V2.10 Template Lite 4 January 2007  (c) 2005-2007 Mark Dickenson. All rights reserved. Released LGPL.';

	public $_templatelite_debug_info  = array();
	public $_templatelite_debug_loop  = false;
	public $_templatelite_debug_dir   = "";
	public $_inclusion_depth          = 0;
	public $_null                     = null;
	public $_sections                 = array();
	public $_foreach                  = array();
	public $_global                   =	array();

	public function __construct()
	{
		$this->def_template_dir = $this->template_dir;
	}

	public function assign($key, $value = null)
	{
		if (is_array($key)) {
			foreach ($key as $var => $val)
				$var && $this->_vars[$var] = $val;
		} else {
			$key && $this->_vars[$key] = $value;
		}
	}

	public function assign_by_ref($key, $value = null)
	{
		$key && $this->_vars[$key] = &$value;
	}

	public function append($key, $value = null, $merge = false)
	{
		if (is_array($key)) {
			foreach ($key as $_key => $_value) {
				if ($_key != '') {
					if (!@is_array($this->_vars[$_key])) {
						settype($this->_vars[$_key], 'array');
					}
					if ($merge && is_array($_value)) {
						foreach ($_value as $_mergekey => $_mergevalue) {
							$this->_vars[$_key][$_mergekey] = $_mergevalue;
						}
					} else {
						$this->_vars[$_key][] = $_value;
					}
				}
			}
		} else {
			if ($key != '' && isset($value)) {
				if (!@is_array($this->_vars[$key])) {
					settype($this->_vars[$key], 'array');
				}
				if ($merge && is_array($value)) {
					foreach ($value as $_mergekey => $_mergevalue) {
						$this->_vars[$key][$_mergekey] = $_mergevalue;
					}
				} else {
					$this->_vars[$key][] = $value;
				}
			}
		}
	}

	public function append_by_ref($key, &$value, $merge = false)
	{
		if ($key != '' && isset($value)) {
			if (!@is_array($this->_vars[$key])) {
				settype($this->_vars[$key], 'array');
			}
			if ($merge && is_array($value)) {
				foreach ($value as $_key => $_val) {
					$this->_vars[$key][$_key] = &$value[$_key];
				}
			} else {
				$this->_vars[$key][] = &$value;
			}
		}
	}

	public function clear_assign($key = null)
	{
		if ($key == null) {
			$this->_vars = array();
		} else {
			if (is_array($key)) {
				foreach ($key as $index => $value) {
					if (in_array($value, $this->_vars)) {
						unset($this->_vars[$index]);
					}
				}
			} else {
				if (in_array($key, $this->_vars)) {
					unset($this->_vars[$key]);
				}
			}
		}
	}

	public function clear_all_assign()
	{
		$this->_vars = array();
	}
	public function get_vars($key = null)
	{
		return $this->get_template_vars($key);
	}
	public function &get_template_vars($key = null)
	{
		if ($key == null) {
			return $this->_vars;
		} else {
			if (isset($this->_vars[$key])) {
				return $this->_vars[$key];
			} else {
				return $this->_null;
			}
		}
	}

	public function clear_compiled_tpl($file = null)
	{
		$this->_destroy_dir($file);
	}
	public function register_callback($key, $called)
	{
		$this->template_callback[$key] = $called;
	}
	public function callback($key, $value)
	{
		if ($callback = $this->template_callback[$key]) {
			if (is_array($callback)) {
				if (class_exists($callback[0]) && method_exists($callback[0], $callback[1])) {
					$value = call_user_func_array($callback, is_array($value) ? $value : array($value));
				}
			} else {
				if (function_exists($callback)) {
					$value = $callback($value);
				}
			}
		}
		if (is_array($value)) {
			return $value[0];
		}
		return $value;
	}
	public function register_block($block, $implementation)
	{
		$this->_plugins['block'][$block] = $implementation;
	}

	public function unregister_block($block)
	{
		unset($this->_plugins['block'][$block]);
	}

	public function register_modifier($modifier, $implementation)
	{
		$this->_plugins['modifier'][$modifier] = $implementation;
	}

	public function unregister_modifier($modifier)
	{
		unset($this->_plugins['modifier'][$modifier]);
	}

	public function register_function($function, $implementation)
	{
		$this->_plugins['function'][$function] = $implementation;
	}

	public function unregister_function($function)
	{
		unset($this->_plugins['function'][$function]);
	}

	public function register_compiler($function, $implementation)
	{
		$this->_plugins['compiler'][$function] = $implementation;
	}

	public function unregister_compiler($function)
	{
		unset($this->_plugins['compiler'][$function]);
	}

	public function register_output($function, $implementation)
	{
		$this->_plugins['output'][$function] = $implementation;
	}

	public function unregister_output($function)
	{
		unset($this->_plugins['output'][$function]);
	}
	public function internal($fn, $param = array())
	{
		if (!function_exists($fn)) {
			require_once iTEMPLATE_DIR . "/internal/{$fn}.php";
		}
		return call_user_func_array($fn, $param);
	}
	public function display($file)
	{
		$this->fetch($file, true);
	}

	public function fetch($file, $display = false)
	{
		if ($this->debugging) {
			$this->_templatelite_debug_info[] = array(
				'type'      => 'template',
				'filename'  => $file,
				'depth'     => 0,
				'exec_time' => array_sum(explode(' ', microtime()))
			);
			$included_tpls_idx = count($this->_templatelite_debug_info) - 1;
		}

		if ($display) {
			$this->_fetch_compile($file);
			$this->debugging && $this->_templatelite_debug_info[$included_tpls_idx]['exec_time'] = array_sum(explode(' ', microtime())) - $this->_templatelite_debug_info[$included_tpls_idx]['exec_time'];
			if ($this->debugging && !$this->_templatelite_debug_loop) {
				$this->debugging = false;
				$this->internal('template_generate_debug_output', array(&$this));
				$this->debugging = true;
			}
		} else {
			return $this->_fetch_compile($file, true);
		}
	}

	public function _get_dir($dir)
	{
		return rtrim($dir, '/') . '/';
	}

	public function _get_resource($file)
	{
		if (strpos($file, 'debug.tpl') !== false) return 'debug.tpl';

		$file = $this->callback('resource', array($file, $this));

		$this->template_dir = $this->_get_dir($this->template_dir);
		$RootPath           = $this->template_dir . $file;
		is_file($RootPath) or $this->throwError("template file '$RootPath' does not exist");
		return $RootPath;
	}

	public function _get_compile_file($file)
	{
		$this->compile_dir = $this->_get_dir($this->compile_dir);
		$compile_file      = str_replace(array($this->template_dir, '/', '.'), array('', '_', '_'), $file) . '.php';
		$compile_file      = $this->compile_dir . $this->reserved_template_varname . '.' . $compile_file;
		return $compile_file;
	}

	public function _get_plugin_dir($name = null)
	{
		$path = $this->callback('plugin', array($name, $this));
		if ($path) {
			return $path;
		}
		return iTEMPLATE_DIR . '/' . $this->plugins_dir . '/' . $name;
	}

	public function _fetch_compile($file, $ret = false)
	{
		$template_file = $this->_get_resource($file);
		$compile_file  = $this->_get_compile_file($template_file);
		$this->_include_file or $this->_file = $file;

		if (!is_file($compile_file)) {
			$compiler = new TemplateLiteCompiler();
			$compiler->left_delimiter            = $this->left_delimiter;
			$compiler->right_delimiter           = $this->right_delimiter;
			$compiler->template_callback         = $this->template_callback;
			$compiler->error_reporting_header    = $this->error_reporting_header;
			$compiler->plugins_dir               = &$this->plugins_dir;
			$compiler->template_dir              = &$this->template_dir;
			$compiler->compile_dir               = &$this->compile_dir;
			$compiler->_vars                     = &$this->_vars;
			$compiler->_plugins                  = &$this->_plugins;
			$compiler->_linenum                  = &$this->_linenum;
			$compiler->_file                     = &$this->_file;
			$compiler->php_extract_vars          = &$this->php_extract_vars;
			$compiler->reserved_template_varname = &$this->reserved_template_varname;
			$compiler->reserved_func_name        = &$this->reserved_func_name;
			$compiler->reserved_func_method      = &$this->reserved_func_method;
			$compiler->_global                   = &$this->_global;
			$compiler->default_modifiers         = &$this->default_modifiers;
			$compile_code = $compiler->compile_file($template_file);
			$this->remove_compile_eol && $this->_remove_compile_eol($compile_code);
			if ($ret === 'code') return $compile_code;
			file_put_contents($compile_file, $compile_code);
		}

		if ($ret === 'file') return $compile_file;

		ob_start();
		include $compile_file;
		$output = ob_get_contents();
		ob_end_clean();

		$this->_plugins['output'] && $this->_run_output($output, $compile_file);

		if ($ret) {
			return $output;
		} else {
			echo $output;
		}
	}

	public function _fetch_compile_include($file, $vars)
	{
		return $this->internal('template_fetch_compile_include', array($file, $vars, &$this));
	}

	public function _remove_compile_eol(&$output)
	{
		$output = preg_replace(
			array(
				'/\n{2,}<\?php/is', '/\?>\n{2,}/is',
				'/\s{2,}<\?php/is', '/\?>\s{2,}/is',
			),
			array('<?php', "?>\n", '<?php', '?>'),
			$output
		);
	}

	public function _run_output(&$content, $file)
	{
		if (!$this->_plugins['output']) return;

		foreach ((array)$this->_plugins['output'] as $key => $value) {
			if (@is_callable($value)) {
				call_user_func_array($value, array(&$content, &$this));
			}
		}
	}

	public function runModifier()
	{
		$arguments = func_get_args();
		list($variable, $func, $flag, $multi) = array_splice($arguments, 0, 4);
		array_unshift($arguments, $variable);
		if (in_array($func, array("eval", "assert", "include", "system", "exec", "shell_exec", "passthru", "set_time_limit", "ini_alter", "dl", "openlog", "syslog", "readlink", "symlink", "link", "leak", "popen", "escapeshellcmd", "apache_child_terminate", "apache_get_modules", "apache_get_version", "apache_getenv", "apache_note", "apache_setenv", "virtual"))) {
			return false;
		}
		$param = $multi ? $arguments : array(&$arguments);
		if ($flag == "PHP") {
			$result = call_user_func_array($func, $param);
		} else {
			$result = call_user_func_array($this->_plugins["modifier"][$func], $param);
		}
		return $result;
	}

	public function _destroy_dir($file = null)
	{
		if ($file == null) {
			foreach ((array)glob($this->compile_dir . '/' . $this->reserved_template_varname . '.*.php') as $fpath) {
				@chmod($fpath, 0777);
				@unlink($fpath);
			}
		} else {
			$fpath = $this->_get_compile_file($file);
			@chmod($fpath, 0777);
			@unlink($fpath);
		}
	}

	public function throwError($error_msg, $file = null, $line = null)
	{
		$info = null;
		if (isset($file) && isset($line)) {
			$info = ' (in ' . basename($file) . ", line $line)";
		}

		throw new Exception('Template Error in <b>' . $this->_file . '</b> line ' . ($this->_linenum) . " [ Error: $error_msg$info ]");
	}
}
// class TemplateLiteCompiler extends TemplateLite {
class TemplateLiteCompiler extends TemplateLite
{
	// public configuration variables
	public $left_delimiter            = "";
	public $right_delimiter           = "";
	public $plugins_dir               = "";
	public $template_dir              = "";
	public $reserved_template_varname = "";
	public $reserved_func_name        = "";
	public $reserved_func_method      = "";
	public $default_modifiers         = array();
	public $template_callback         = null;
	public $error_reporting_header    = null;

	public $php_extract_vars          = true;	// Set this to false if you do not want the $this->_tpl variables to be extracted for use by PHP code inside the template.
	public $error                     = true;
	// private internal variables
	public $_vars                     = array();	// stores all internal assigned variables
	public $_plugins                  = array();	// stores all internal plugins
	public $_linenum                  = 0;		// the current line number in the file we are processing
	public $_file                     = "";		// the current file we are processing
	public $_literal                  = array();	// stores all literal blocks
	public $_foreachelse_stack        = array();
	public $_for_stack                = 0;
	public $_sectionelse_stack        = array();	// keeps track of whether section had 'else' part
	public $_iPHP_else_stack          = false;	// keeps track of whether section had 'else' part
	public $_iPHP_stack               = array();
	public $_iPHP_compile          	  = null;
	public $_switch_stack             = array();
	public $_tag_stack                = array();
	public $_require_stack            = array();	// stores all files that are "required" inside of the template
	public $_php_blocks               = array();	// stores all of the php blocks
	public $_error_level              = null;

	public $_db_qstr_regexp           = null;		// regexps are setup in the constructor
	public $_si_qstr_regexp           = null;
	public $_qstr_regexp              = null;
	public $_func_regexp              = null;
	public $_var_bracket_regexp       =	null;
	public $_dvar_regexp              =	null;
	public $_svar_regexp              =	null;
	public $_mod_regexp               =	null;
	public $_var_regexp               =	null;
	public $_obj_params_regexp        = null;
	public $_global                   =	array();

	public function __construct()
	{
		// matches double quoted strings:
		// "foobar"
		// "foo\"bar"
		// "foobar" . "foo\"bar"
		$this->_db_qstr_regexp = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';

		// matches single quoted strings:
		// 'foobar'
		// 'foo\'bar'
		$this->_si_qstr_regexp = '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'';

		// matches single or double quoted strings
		$this->_qstr_regexp = '(?:' . $this->_db_qstr_regexp . '|' . $this->_si_qstr_regexp . ')';

		// matches bracket portion of vars
		// [0]
		// [foo]
		// [$bar]
		// [#bar#]
		//		$this->_var_bracket_regexp = '\[[\$|\#]?\w+\#?\]';
		$this->_var_bracket_regexp = '\[\$?[\w\.]+\]';

		// matches section vars:
		// %foo.bar%
		$this->_svar_regexp = '\%\w+\.\w+\%';

		// matches $ vars (not objects):
		// &$foo
		// $foo
		// $foo[0]
		// $foo[$bar]
		// $foo[5][blah]
		//		$this->_dvar_regexp = '\$[a-zA-Z0-9_]{1,}(?:' . $this->_var_bracket_regexp . ')*(?:' . $this->_var_bracket_regexp . ')*';
		$this->_dvar_regexp = '&*\$\w{1,}(?:' . $this->_var_bracket_regexp . ')*(?:\.\$?\w+(?:' . $this->_var_bracket_regexp . ')*)*';

		// matches valid variable syntax:
		// $foo
		// 'text'
		// "text"
		$this->_var_regexp = '(?:(?:' . $this->_dvar_regexp . ')|' . $this->_qstr_regexp . ')';
		// matches valid modifier syntax:
		// |foo
		// |@foo
		// |foo:"bar"
		// |foo:$bar
		// |foo:"bar":$foobar
		// |foo|bar
		$this->_mod_regexp = '(?:\|@?\w+(?::(?>-?\w+|' . $this->_dvar_regexp . '|' . $this->_qstr_regexp . '))*)';

		// matches valid function name:
		// foo123
		// _foo_bar
		$this->_func_regexp = '[a-zA-Z_0-9:]+';
		//		$this->_func_regexp = '[a-zA-Z_]\w*';

	}

	public function compile_file($tfile)
	{
		$tfile == 'debug.tpl' && $tfile = iTEMPLATE_DIR . '/internal/debug.tpl';

		$contents = file_get_contents($tfile);
		$contents = $this->callback('compile', array($contents, $tfile, $this));

		$ldq           = preg_quote($this->left_delimiter);
		$rdq           = preg_quote($this->right_delimiter);
		$_match        = array();		// a temp variable for the current regex match
		$tags          = array();		// all original tags
		$text          = array();		// all original text
		$compiled_text = '';
		$compiled_tags = array();		// all tags and stuff

		$this->_require_stack = array();

		// remove all comments
		$contents = preg_replace("!{$ldq}\*.*?\*{$rdq}!s", "", $contents);

		// replace all php start and end tags
		//		$contents = preg_replace('%(<\?(?!php|=|$))%i', '<?php echo \'\\1\'? >'."\n", $contents);

		/* match anything resembling php tags */
		if (preg_match_all('~(<\?(?:\w+|=)?|\?>|language\s*=\s*[\"\']?\s*php\s*[\"\']?)~is', $contents, $sp_match)) {
			/* replace tags with placeholders to prevent recursive replacements */
			$sp_match[1] = array_unique($sp_match[1]);
			/* process each one */
			for ($curr_sp = 0, $for_max2 = count($sp_match[1]); $curr_sp < $for_max2; $curr_sp++) {
				if ($this->php_handling == "PHP_PASSTHRU") {
					/* echo php contents */
					$contents = str_replace($sp_match[1][$curr_sp], '<?php echo \'' . str_replace("'", "\'", $sp_match[1][$curr_sp]) . '\'; ?>', $contents);
				} else if ($this->php_handling == "PHP_QUOTE") {
					/* quote php tags */
					$contents = str_replace($sp_match[1][$curr_sp], htmlspecialchars($sp_match[1][$curr_sp]), $contents);
				} else if ($this->php_handling == "PHP_REMOVE") {
					/* remove php tags */
					$contents = str_replace($sp_match[1][$curr_sp], '', $contents);
				} else {
					/* PHP_ALLOW, but echo non php starting tags */
					$sp_match[1][$curr_sp] = preg_replace('~(<\?(?!php|=|$))~i', '<?php echo \'\\1\'?>', $sp_match[1][$curr_sp]);
					$contents = str_replace($sp_match[1][$curr_sp], $sp_match[1][$curr_sp], $contents);
				}
			}
		}
		// remove literal blocks

		preg_match_all("!{$ldq}\s*literal\s*{$rdq}(.*?){$ldq}\s*/literal\s*{$rdq}!s", $contents, $_match);
		$this->_literal = $_match[1];
		$contents = preg_replace("!{$ldq}\s*literal\s*{$rdq}(.*?){$ldq}\s*/literal\s*{$rdq}!s", stripslashes($ldq . "literal" . $rdq), $contents);

		// remove php blocks
		preg_match_all("!{$ldq}\s*php\s*{$rdq}(.*?){$ldq}\s*/php\s*{$rdq}!s", $contents, $_match);
		$this->_php_blocks = $_match[1];
		$contents = preg_replace("!{$ldq}\s*php\s*{$rdq}(.*?){$ldq}\s*/php\s*{$rdq}!s", stripslashes($ldq . "php" . $rdq), $contents);

		// gather all template tags
		preg_match_all("!{$ldq}\s*(.*?)\s*{$rdq}!s", $contents, $_match);
		$tags = $_match[1];

		// put all of the non-template tag text blocks into an array, using the template tags as delimiters
		$text = preg_split("!{$ldq}.*?{$rdq}!s", $contents);

		// compile template tags
		$count_tags = count($tags);
		for ($i = 0, $for_max = $count_tags; $i < $for_max; $i++) {
			$this->_linenum += substr_count($text[$i], "\n");
			$compiled_tags[] = $this->_compile_tag($tags[$i]);
			$this->_linenum += substr_count($tags[$i], "\n");
		}

		// build the compiled template by replacing and interleaving text blocks and compiled tags
		$count_compiled_tags = count($compiled_tags);
		for ($i = 0, $for_max = $count_compiled_tags; $i < $for_max; $i++) {
			if ($compiled_tags[$i] == '') {
				$text[$i + 1] = preg_replace('~^(\r\n|\r|\n)~', '', $text[$i + 1]);
			}
			$compiled_text .= $text[$i] . $compiled_tags[$i];
		}
		$compiled_text .= $text[$i];

		foreach ($this->_require_stack as $key => $value) {
			$compiled_text = '<?php require_once \'' . $this->_get_plugin_dir($key) . '\';'
				. '$this->register_' . $value[0] . '("' . $value[1] . '", "' . $value[2] . '"); ?>'
				. $compiled_text;
		}

		// remove unnecessary close/open tags
		$compiled_text = preg_replace('!\?>\n?<\?php!', "\n", $compiled_text);

		//2007-7-29 21:15 error_reporting_header
		$compiled_text = $this->error_reporting_header . $compiled_text;

		return $compiled_text;
	}

	public function _compile_tag($tag)
	{
		$_match		= array();		// stores the tags
		$_result	= "";			// the compiled tag
		$_variable	= "";			// the compiled variable

		// extract the tag command, modifier and arguments
		preg_match_all('/(?:(' . $this->_var_regexp . '|' . $this->_svar_regexp . '|\/?' . $this->_func_regexp . ')(' . $this->_mod_regexp . '*)(?:\s*[,\.]\s*)?)(?:\s+(.*))?/xs', $tag, $_match);

		if ($_match[1][0][0] == '&' || $_match[1][0][0] == '$' || $_match[1][0][0] == "'" || $_match[1][0][0] == '"' || $_match[1][0][0] == '%') {
			$_varname = $this->_parse_variables($_match[1], $_match[2]);
			$_result = implode('.', $_varname);
			//三元表达式 $a?true:false
			if (preg_match('/.*?\?.*?:.*?/', $tag) && strpos($_result, '$this->runModifier') === false) {
				$_result = str_replace($_match[1], $_varname, $tag);
				return "<?php echo ($_result); ?>";
			}
			// 引用赋值 &$test
			// 赋值 $aa = 11
			if ($_match[1][0][0] == '&' || preg_match('/"\$.*?=.*?"/', $_match[1][0])) {
				return "<?php $_result; ?>";
			}
			return "<?php echo $_result; ?>";
		}
		// process a function
		$tag_command   = $_match[1][0];
		$tag_modifiers = empty($_match[2][0]) ? null : $_match[2][0];
		$tag_arguments = empty($_match[3][0]) ? null : $_match[3][0];
		$_result       = $this->_parse_function($tag_command, $tag_modifiers, $tag_arguments);
		return $_result;
	}
	public function _compile_custom_output($function, $arguments, &$_result)
	{
		if ($function = $this->_plugin_exists($function, "output")) {
			$_result = '<?php ' . $function($_result, $this) . ' ?>';
			return true;
		} else {
			return false;
		}
	}

	public function _compile_compiler_function($function, $arguments, &$_result)
	{
		if ($function = $this->_plugin_exists($function, "compiler")) {
			$_args   = $this->_parse_arguments($arguments);
			$code = call_user_func_array($function, [$_args, $this]);
			$code && $_result = '<?php ' . $code . ' ?>';
			// $_result = sprintf('<?php %s(%s,$this); ? >', $function, var_export($_args, true));
			return true;
		} else {
			return false;
		}
	}

	public function _compile_custom_function($function, $modifiers, $arguments, &$_result)
	{
		return $this->internal('compile_custom_function', array($function, $modifiers, $arguments, &$_result, &$this));
	}

	public function _compile_custom_block($function, $modifiers, $arguments, &$_result)
	{
		return $this->internal('compile_custom_block', array($function, $modifiers, $arguments, &$_result, &$this));
	}

	public function _compile_if($arguments, $elseif = false, $while = false)
	{
		return $this->internal('compile_if', array($arguments, $elseif, $while, &$this));
	}

	public function _parse_function($function, $modifiers, $arguments)
	{
		if (strpos($function, $this->reserved_template_varname . ':') !== false) {
			list($function, $class, $method) = explode(':', $function);
		}
		//var_dump($function,$class,$method);
		switch ($function) {
			case 'include':
				$_args = $this->_parse_arguments($arguments);
				if(isset($_args['Maximum'])){
					$file = $this->_dequote($_args['file']);
					$GLOBALS['tpl:include'][$file]++;
					$Maximum = (int)$this->_dequote($_args['Maximum']);
					if($GLOBALS['tpl:include'][$file]>$Maximum){
						return;
					}
				}
				$include_file = $this->internal('compile_include', array($arguments, &$this));
				$include_file = str_replace($this->error_reporting_header, '', $include_file);
				return $include_file;
				break;
			case $this->reserved_template_varname:
				$_args = $this->_parse_arguments($arguments);
				$_args[$this->reserved_func_name] = '"' . $class . '"';
				// if(isset($_args['app'])){
				// 	$_args['_'.$this->reserved_func_name] = '"'.$class.'"';
				// 	$_args[$this->reserved_func_name] = '"'.$this->_dequote($_args['app']).'"';
				// }

				$method && $_args[$this->reserved_func_method] = '"' . $method . '"';

				isset($_args[$this->reserved_func_name]) or $this->throwError("missing 'app' attribute in '" . $this->reserved_template_varname . "'", __FILE__, __LINE__);

				foreach ($_args as $key => $value) {
					$arg_list[] = "'$key' => $value";
				}

				$code = '<?php $this->callback("func",array(array(' . implode(',', (array)$arg_list) . '),$this)); ?>';

				if ($class && isset($_args['loop'])) {
					$this->_iPHP_stack[count($this->_iPHP_stack) - 1] = true;
					$class_args = $this->_dequote($class);
					$_args[$this->reserved_func_method] && $class_args .= '_' . $this->_dequote($_args[$this->reserved_func_method]);
					$_args['as'] 	     && $class_args = $this->_dequote($_args['as']);

					$arguments = $this->reserved_func_name . '=$' . $class_args;
					isset($_args['start'])	&& $arguments .= " start={$_args['start']} ";
					isset($_args['step'])	&& $arguments .= " step={$_args['step']} ";
					isset($_args['max'])	&& $arguments .= " max={$_args['max']} ";
					$compile_iPHP = $this->internal('compile_iPHP', array($arguments, &$this));
				}

				if (isset($_args['if'])) {
					$this->_iPHP_compile = $compile_iPHP;
					unset($compile_iPHP);
					return $code;
				}
				return $code . $compile_iPHP;
				break;
			case $this->reserved_template_varname . 'else':
				$this->_iPHP_else_stack = true;
				return "<?php }}else{ ?>";
				break;
			case '/' . $this->reserved_template_varname:
				array_pop($this->_iPHP_stack) or $this->throwError("missing 'loop' attribute in '" . $this->reserved_template_varname . "'", __FILE__, __LINE__);
				if ($this->_iPHP_else_stack) {
					$this->_iPHP_else_stack = false;
					return "<?php } ?>";
				}
				return "<?php }} ?>";
				break;
			case 'ldelim':
				return $this->left_delimiter;
				break;
			case 'rdelim':
				return $this->right_delimiter;
				break;
			case 'literal':
				list(, $literal) = _each($this->_literal);
				$this->_linenum += substr_count($literal, "\n");
				return "<?php echo '" . str_replace("'", "\'", str_replace("\\", "\\\\", $literal)) . "'; ?>";
				break;
			case 'php':
				$php_extract = '';
				list(, $php_block) = _each($this->_php_blocks);

				$this->_linenum += substr_count($php_block, "\n");
				$this->php_extract_vars && $php_extract = '<?php extract($this->_vars, EXTR_REFS); ?>' . "\n";
				return $php_extract . '<?php ' . $php_block . ' ?>';
				break;
			case 'foreach':
				array_push($this->_foreachelse_stack, false);
				$_args = $this->_parse_arguments($arguments);
				isset($_args['from']) or $this->throwError("missing 'from' attribute in 'foreach'", __FILE__, __LINE__);
				isset($_args['value']) or $this->throwError("missing 'value' attribute in 'foreach'", __FILE__, __LINE__);
				isset($_args['value']) && $_args['value'] = $this->_dequote($_args['value']);
				isset($_args['start']) && $_args['start'] = $this->_dequote($_args['start']);
				isset($_args['end'])   && $_args['end'] = $this->_dequote($_args['end']);

				//				isset($_args['key']) ? $_args['key'] = "\$this->_vars['".$this->_dequote($_args['key'])."'] => " : $_args['key'] = '';
				isset($_args['key']) or $_args['key'] = 'key_' . rand(1, 999);
				$hash    = rand(1, 999);
				$keystr  = "\$this->_vars['" . $this->_dequote($_args['key']) . "'] => ";
				$_result = '<?php
				$_count_' . $hash . ' = is_array(' . $_args['from'] . ')?count(' . $_args['from'] . '):0;
				$this->_vars[\'' . $_args['value'] . '_first\'] = false;
				$this->_vars[\'' . $_args['value'] . '_last\']  = false;
				$this->_vars[\'' . $_args['value'] . '_count\'] = $_count_' . $hash . ';
				$fec_' . $hash . ' = 1;
				if ($_count_' . $hash . '){
					foreach ((array)' . $_args['from'] . ' as ' . $keystr . '$this->_vars[\'' . $_args['value'] . '\']){
						$fec_' . $hash . ' == 1 && $this->_vars[\'' . $_args['value'] . '_first\'] = true;
						$fec_' . $hash . ' == $_count_' . $hash . ' && $this->_vars[\'' . $_args['value'] . '_last\'] = true;
				';
				if (isset($_args['start'])) {
					$_result .= 'if($fec_' . $hash . '<=' . $_args['start'] . '){$fec_' . $hash . '++;continue;}';
				}
				if (isset($_args['end'])) {
					$_result .= 'if($fec_' . $hash . '>' . $_args['end'] . '){break;}';
				}
				$_result .= '$fec_' . $hash . '++;';
				$_result .= '?>';
				return $_result;
				break;
			case 'foreachelse':
				$this->_foreachelse_stack[count($this->_foreachelse_stack) - 1] = true;
				return "<?php }}else{ ?>";
				break;
			case '/foreach':
				if (array_pop($this->_foreachelse_stack)) {
					return "<?php } ?>";
				} else {
					return "<?php }} ?>";
				}
				break;
			case 'for':
				$this->_for_stack++;
				$_args = $this->_parse_arguments($arguments);
				isset($_args['stop']) && $_args['count'] = $_args['stop'];

				isset($_args['start']) or $this->throwError("missing 'start' attribute in 'for'", __FILE__, __LINE__);
				isset($_args['count'])  or $this->throwError("missing 'count' attribute in 'for'", __FILE__, __LINE__);
				isset($_args['step'])  or $_args['step'] = 1;
				$_result = '<?php for($for' . $this->_for_stack . ' = ' . $_args['start'] . '; ((' . $_args['start'] . ' < ' . $_args['count'] . ') ? ($for' . $this->_for_stack . ' < ' . $_args['count'] . ') : ($for' . $this->_for_stack . ' > ' . $_args['count'] . ')); $for' . $this->_for_stack . ' += ((' . $_args['start'] . ' < ' . $_args['count'] . ') ? ' . $_args['step'] . ' : -' . $_args['step'] . ')){ ?>';
				isset($_args['value']) && $_result .= '<?php $this->assign(\'' . $this->_dequote($_args['value']) . '\', $for' . $this->_for_stack . '); ?>';
				return $_result;
				break;
			case '/for':
				$this->_for_stack--;
				return "<?php } ?>";
				break;
			case 'section':
				array_push($this->_sectionelse_stack, false);
				return $this->internal('compile_section_start', array($arguments, &$this));
				break;
			case 'sectionelse':
				$this->_sectionelse_stack[count($this->_sectionelse_stack) - 1] = true;
				return "<?php }}else{ ?>";
				break;
			case '/section':
				if (array_pop($this->_sectionelse_stack)) {
					return "<?php } ?>";
				} else {
					return "<?php }} ?>";
				}
				break;
			case 'while':
				$_args = $this->_compile_if($arguments, false, true);
				return '<?php while(' . $_args . '){ ?>';
				break;
			case '/while':
				return "<?php } ?>";
				break;
			case 'if':
				return $this->_compile_if($arguments);
				break;
			case 'else':
				return "<?php }else{ ?>";
				break;
			case 'elseif':
				return $this->_compile_if($arguments, true);
				break;
			case '/if':
				$code = "<?php }; ?>";
				if ($this->_iPHP_compile) {
					$code .= $this->_iPHP_compile;
					unset($this->_iPHP_compile);
				}
				return $code;
				break;
			case 'assign':
				$_args = $this->_parse_arguments($arguments);
				if (!isset($_args['var']) && !isset($_args['value'])) {
					$code = null;
					if (isset($_args['array'])) {
						$_array   = array();
						$array_key = $this->_dequote($_args['array']);
						unset($_args['array']);
					}
					foreach ($_args as $key => $value) {
						if ($array_key) {
							$_array[$this->_dequote($key)] = $this->_dequote($value);
						} else {
							$code .= '<?php $this->assign(\'' . $this->_dequote($key) . '\', ' . $value . '); ?>';
						}
					}
					$array_key && $code .= '<?php $this->assign(\'' . $array_key . '\', ' . var_export($_array, true) . '); ?>';
				} else {
					if (!isset($_args['var'])) {
						$this->throwError("missing 'var' attribute in 'assign'", __FILE__, __LINE__);
					}
					if (!isset($_args['value'])) {
						$this->throwError("missing 'value' attribute in 'assign'", __FILE__, __LINE__);
					}
					$code = '<?php $this->assign(\'' . $this->_dequote($_args['var']) . '\', ' . $_args['value'] . '); ?>';
				}
				return $code;
				break;
			case 'switch':
				$_args = $this->_parse_arguments($arguments);
				isset($_args['from']) or $this->throwError("missing 'from' attribute in 'switch'", __FILE__, __LINE__);
				array_push($this->_switch_stack, array("matched" => false, "var" => $this->_dequote($_args['from'])));
				return;
				break;
			case '/switch':
				array_pop($this->_switch_stack);
				return '<?php break; }; ?>';
				break;
			case 'continue':
				return '<?php continue; ?>';
				break;
			case 'return':
				return '<?php return; ?>';
				break;
			case 'break':
				return '<?php break; ?>';
				break;
			case 'case':
				if (count($this->_switch_stack) > 0) {
					$_result = "<?php ";
					$_args = $this->_parse_arguments($arguments);
					$_index = count($this->_switch_stack) - 1;
					if (!$this->_switch_stack[$_index]["matched"]) {
						$_result .= 'switch(' . $this->_switch_stack[$_index]["var"] . '){';
						$this->_switch_stack[$_index]["matched"] = true;
					} else {
						$_result .= 'break; ';
					}
					if (!empty($_args['value'])) {
						$_result .= 'case ' . $_args['value'] . ': ';
					} else {
						$_result .= 'default: ';
					}
					return $_result . ' ?>';
				} else {
					$this->throwError("unexpected 'case', 'case' can only be in a 'switch'", __FILE__, __LINE__);
				}
				break;
			default:
				$_result = "";
				if ($this->_compile_compiler_function($function, $arguments, $_result)) {
					return $_result;
				} else if ($this->_compile_custom_block($function, $modifiers, $arguments, $_result)) {
					return $_result;
				} elseif ($this->_compile_custom_function($function, $modifiers, $arguments, $_result)) {
					return $_result;
				} elseif ($this->_compile_custom_output($function, $arguments, $_result)) {
					return $_result;
				} else {
					$this->throwError($function . " function does not exist", __FILE__, __LINE__);
				}
				break;
		}
	}

	public function _parse_is_expr($is_arg, $_arg)
	{
		return $this->internal('compile_parse_is_expr', array($is_arg, $_arg, &$this));
	}

	public function _dequote($string)
	{
		if ((substr($string, 0, 1) == "'" || substr($string, 0, 1) == '"') && (substr($string, -1) == "'" || substr($string, -1) == '"')) {
			return substr($string, 1, -1);
		} else {
			return $string;
		}
	}
	public function _dejson($string)
	{
		//简单判断 json 字符
		$s01 = substr($string, 0, 1);
		$s02 = substr($string, 0, 2);
		$s_1 = substr($string, -1);
		$s_2 = substr($string, -2);
		if (($s01 == '{' && $s_1 == '}') ||
			($s02 == '["' && $s_2 == '"]') ||
			($s02 == '[{' && $s_2 == '}]') ||
			($s02 == '[[' && $s_2 == ']]')
		) {
			if (preg_match('/\$.+/', $string)) {
				preg_match_all('/(?:(' . $this->_var_regexp . '|' . $this->_svar_regexp . ')(' . $this->_mod_regexp . '*))(?:\s+(.*?))?/xs', $string, $_variables);
				$_varname = $this->_parse_variables($_variables[1], $_variables[2]);
				$replace = array();
				foreach ((array)$_varname as $key => $var) {
					$vkey = $this->_dequote($_variables[1][$key]);
					if (substr($vkey, 0, 1) == '$') {
						$search[]  = "'$vkey'";
						$replace[] = $this->_dequote($var);
					}
				}
			}

			$array = json_decode($string, true);
			$error = json_last_error();
			if ($error === 0) {
				$ret = var_export($array, true);
				if ($search && $replace) {
					$ret = str_replace($search, $replace, $ret);
				}
				return $ret;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function _parse_arguments($arguments)
	{
		$_match		= array();
		$_result	= array();
		$_variables	= array();
		preg_match_all('/(?:' . $this->_qstr_regexp . ' | (?>[^"\'=\s]+))+|[=]/x', $arguments, $_match);
		/*
		   Parse state:
			 0 - expecting attribute name
			 1 - expecting '='
			 2 - expecting attribute value (not '=')
		*/
		$state = 0;
		$key   = null;
		$bracket  = array();

		foreach ($_match[0] as $value) {
			switch ($state) {
				case 0:
					$key === null ? $key = 0 : (is_numeric($key) ? $key++ : $key = 0);
					//解析 独立json
					$ret = $this->_dejson($value);
					if ($ret !== false) {
						$_result[$key] = $ret;
						$state = 0;
						break;
					}
					// valid attribute name
					if (is_string($value)) {
						$key = $this->_dequote($value);
						//二维数组 自增key
						if (strpos($key, '[]') !== false && strpos($key, '][') === false) {
							$bracket[$key]++;
							$kidx = (int)$bracket[$key] - 1;
							$key  = str_replace('[]', '[' . $kidx . ']', $key);
						}
						// if(strpos($key, '[]')!==false && strpos($key, '][')!==false){
						// 	$bkey  = str_replace('][', '],[', $key);
						// 	$bracketArray = explode(',', $bkey);
						// 	var_dump($bracketArray);
						// 	$bkk = '';
						// 	foreach ($bracketArray as $bk => &$value) {
						// 		$bkk.= $value;
						// 		$bracket[$bkk]++;
						// 		$bkidx = (int)$bracket[$bkk]-1;
						// 		$value = str_replace('[]', '['.$bkidx.']', $value);
						// 	}
						// 	$key = implode('', $bracketArray);
						// 	var_dump($bracketArray);
						// }
						$state  = 1;
					} else {
						$this->throwError("invalid attribute name: '$token'", __FILE__, __LINE__);
					}
					break;
				case 1:
					if ($value == '=') {
						$state = 2;
					} else {
						$this->throwError("expecting '=' after '$last_value'", __FILE__, __LINE__);
					}
					break;
				case 2:
					$quote = substr(trim($value), 0, 1);
					$value = $this->_dequote($value);
					if ($value != '=') {
						if ($value == 'yes' || $value == 'on' || $value == 'true') {
							$value = true;
						} elseif ($value == 'no' || $value == 'off' || $value == 'false') {
							$value = false;
						} elseif ($value == 'null') {
							$value = null;
						}

						if (strpos($value, '"') !== false || strpos($value, "'") !== false) {
							$value =  addslashes($value);
						}
						if ($quote != "'" && $quote != '"') {
							$quote = '';
						}

						if (preg_match_all('/(?:(' . $this->_var_regexp . '|' . $this->_svar_regexp . ')(' . $this->_mod_regexp . '*))(?:\s+(.*?))?/xs', $value, $_variables)) {
							// a="aa$cc" b="$aa$cc" c="$aa'cc'"
							$_varname = $this->_parse_variables($_variables[1], $_variables[2]);
							$value = stripslashes($value);
							$replace = array();
							foreach ((array)$_varname as $_vkey => $_vvvv) {
								if (strpos($_vvvv, 'runModifier') !== false) {
									$modifier[] = "{$quote}.{$_vvvv}.{$quote}";
								} else {
									$replace[] = "{$quote}.{$_vvvv}.{$quote}";
								}
							}
							if ($modifier) {
								$value = $quote . str_replace($_variables[0], $modifier, $value) . $quote;
							} else {
								$value = $quote . str_replace($_variables[1], $replace, $value) . $quote;
							}

							$_result[$key] = str_replace(array("{$quote}{$quote}.", ".{$quote}{$quote}",), '', $value);
						} else {
							if (strpos($value, '\"') !== false || strpos($value, "\'") !== false) {
								$value =  stripslashes($value);
							}
							$_result[$key] = "{$quote}{$value}{$quote}";
							if (is_bool($value)) {
								$_result[$key] = $value ? 'true' : 'false';
							}
							$ret = $this->_dejson($value);
							$ret === false or $_result[$key] = $ret;
						}
						$state = 0;
					} else {
						$this->throwError("'=' cannot be an attribute value", __FILE__, __LINE__);
					}
					break;
			}
			$last_value = $value;
		}
		if ($state != 0) {
			if ($state == 1) {
				$this->throwError("expecting '=' after attribute name '$last_value'", __FILE__, __LINE__);
			} else {
				$this->throwError("missing attribute value", __FILE__, __LINE__);
			}
		}
		return $_result;
	}

	public function _parse_variables($variables, $modifiers)
	{
		$_result = array();
		foreach ($variables as $key => $value) {
			$value = trim($value);
			if (!empty($this->default_modifiers) && !preg_match('!(^|\|)templatelite:nodefaults($|\|)!', $modifiers[$key])) {
				$_default_mod_string = implode('|', (array)$this->default_modifiers);
				$modifiers[$key] = empty($modifiers[$key]) ? $_default_mod_string : $_default_mod_string . '|' . $modifiers[$key];
			}

			if (empty($modifiers[$key])) {
				$_result[] = $this->_parse_variable($value);
			} else {
				$reference = null;
				if ($value[0] == '&') {
					$value = substr($value, 1);
					if ($value[0] == '$') {
						$reference = $this->_compile_variable($value) . ' = ';
					}
				}
				$_result[] = $reference . $this->_parse_modifier($this->_parse_variable($value), $modifiers[$key]);
			}
		}
		// var_dump($_result);
		//{$a?$b:$c} 支持模板中三元判断
		// if ($_result[2] === ':' && count($_result) == 4) {
		// 	return '(' . $_result[0] . '?' . $_result[1] . ':' . $_result[3] . ')';
		// }
		return $_result;
	}

	public function _parse_variable($variable)
	{
		// replace variable with value
		if ($variable[0] == '$') {
			// replace the variable
			return $this->_compile_variable($variable);
		} elseif ($variable[0] == '"') {
			// expand the quotes to pull any variables out of it
			// fortunately variables inside of a quote aren't fancy, no modifiers, no quotes
			// just get everything from the $ to the ending space and parse it
			// if the $ is escaped, then we won't expand it
			$_result = "";
			//			preg_match_all('/(?:[^\\\]' . $this->_dvar_regexp . ')/', substr($variable, 1, -1), $_expand);  // old match
			// 21:57 2008-4-27 math
			preg_match_all('/(?:[^\\\]' . $this->_dvar_regexp . ')/', $variable, $_expand);  // old match
			//			preg_match_all('/(?:[^\\\]' . $this->_dvar_regexp . '[^\\\])/', $variable, $_expand);
			$_expand = array_unique($_expand[0]);
			foreach ($_expand as $key => $value) {
				$_expand[$key] = trim($value);
				if (strpos($_expand[$key], '$') > 0) {
					$_expand[$key] = substr($_expand[$key], strpos($_expand[$key], '$'));
				}
			}
			$_result = $variable;
			foreach ($_expand as $value) {
				$value = trim($value);
				//解析{变量}
				if (strpos($_result, '{' . $value . '}') !== false) {
					$_result = str_replace('{' . $value . '}', '" . ' . $this->_parse_variable($value) . ' . "', $_result);
				} else {
					//mod 21:56 2008-4-27 math
					$_result = str_replace($value, $this->_parse_variable($value), $this->_dequote($_result));
				}
			}
			$_result = str_replace("`", "", $_result);
			return $_result;
		} elseif ($variable[0] == "'") {
			$_result = $variable;
			//解析{变量}
			if (strpos($variable, '{') !== false && strpos($variable, '}') !== false) {
				preg_match_all('/\{(' . $this->_dvar_regexp . ')\}/', $variable, $_expand);
				if ($_expand[1]) foreach ($_expand[1] as $k => $value) {
					$value = trim($value);
					$_result = str_replace($_expand[0][$k], "'." . $this->_parse_variable($value) . ".'", $_result);
				}
			}
			// return the value just as it is
			return $_result;
		} elseif ($variable[0] == "%") {
			return $this->_parse_section_prop($variable);
		} else {
			// return it as is; i believe that there was a reason before that i did not just return it as is,
			// but i forgot what that reason is ...
			// the reason i return the variable 'as is' right now is so that unquoted literals are allowed
			return $variable;
		}
	}

	public function _parse_section_prop($section_prop_expr)
	{
		$parts     = explode('|', $section_prop_expr, 2);
		$var_ref   = $parts[0];
		$modifiers = isset($parts[1]) ? $parts[1] : '';

		preg_match('!%(\w+)\.(\w+)%!', $var_ref, $match);
		$section_name = $match[1];
		$prop_name    = $match[2];
		$output       = "\$this->_sections['$section_name']['$prop_name']";

		$this->_parse_modifier($output, $modifiers);

		return $output;
	}

	public function _compile_variable($variable)
	{
		$_result  = "";
		// remove the $
		$variable = substr($variable, 1);

		// get [foo] and .foo and (...) pieces
		preg_match_all('!(?:^\w+)|(?:' . $this->_var_bracket_regexp . ')|\.\$?\w+|\S+!', $variable, $_match);
		$variable = $_match[0];
		$var_name = array_shift($variable);
		if ($var_name == $this->reserved_template_varname) {
			if ($variable[0][0] == '[' || $variable[0][0] == '.') {
				$find = array("[", "]", ".");
				switch (strtoupper(str_replace($find, "", $variable[0]))) {
					case 'SERVER':
						$_result = "\$_SERVER";
						break;
					case 'SELF':
						$_result = "\$_SERVER['PHP_SELF']";
						break;
					case 'REQUEST_URI':
						$_result = "\$_SERVER['REQUEST_URI']";
						break;
					case 'SERVER_NAME':
						$_result = "\$_SERVER['SERVER_NAME']";
						break;
					case 'SERVER_PORT':
						$_result = "\$_SERVER['SERVER_PORT']";
						break;
					case 'USER_AGENT':
						$_result = "\$_SERVER['HTTP_USER_AGENT']";
						break;
					case 'TIME':
						$_result = "time()";
						break;
					case 'NOW':
						$_result = "time()";
						break;
					case 'SECTION':
						$_result = "\$this->_sections";
						break;
					case 'LDELIM':
						$_result = "\$this->left_delimiter";
						break;
					case 'RDELIM':
						$_result = "\$this->right_delimiter";
						break;
					case 'TPLVERSION':
						$_result = "\$this->_version";
						break;
					case 'TEMPLATE':
						$_result = "\$this->_file";
						break;
					case 'CONST':
						$constant = str_replace($find, "", $_match[0][2]);
						$_result = "constant('$constant')";
						$variable = array();
						break;
					default:
						$_var_name = str_replace($find, "", $variable[0]);
						$_result = "\$this->_global['$_var_name']";
						break;
				}
				array_shift($variable);
			} else {
				if ($variable) {
					$this->throwError('$' . $var_name . implode('', $variable) . ' is an invalid $' . $this->reserved_template_varname . ' reference', __FILE__, __LINE__);
				} else {
					$_result = "\$this->_global";
				}
			}
		} else {
			$_result = "\$this->_vars['$var_name']";
		}

		if ($variable) foreach ($variable as $var) {
			if ($var[0] == '[') {
				$var = substr($var, 1, -1);
				if (is_numeric($var)) {
					$_result .= "[$var]";
				} elseif ($var[0] == '$') {
					$_result .= "[" . $this->_compile_variable($var) . "]";
				} else {
					// $_result .= "['$var']";
					$parts        = explode('.', $var);
					$section      = $parts[0];
					$section_prop = isset($parts[1]) ? $parts[1] : 'index';
					$_result      .= sprintf('[$this->_sections["%s"]["%s"]]', $section, $section_prop);
				}
			} else if ($var[0] == '.') {
				if ($var[0] == '$') {
					$_result .= sprintf('[$this->_vars["%s"]]', substr($var, 2));
				} else {
					$_result .= sprintf('["%s"]', substr($var, 1));
				}
			} else if (substr($var, 0, 2) == '->') {
				if (substr($var, 2, 2) == '__') {
					$this->throwError('call to internal object members is not allowed', __FILE__, __LINE__);
				} else if (substr($var, 2, 1) == '$') {
					$_result .= '->{(($var=$this->_vars [\'' . substr($var, 3) . '\']) && substr($var,0,2)!=\'__\') ? $_var : $this->throwError("cannot access property \\"$var\\"")}';
				}
			} else {
				$this->throwError('$' . $var_name . implode('', $variable) . ' is an invalid reference', __FILE__, __LINE__);
			}
		}
		return $_result;
	}

	public function _parse_modifier($variable, $modifiers)
	{
		$_match = array();
		$_mods  = array();		// stores all modifiers
		$_args  = array();		// modifier arguments

		preg_match_all('!\|(@?\w+)((?>:(?:' . $this->_qstr_regexp . '|[^|]+))*)!', '|' . $modifiers, $_match);
		list(, $_mods, $_args) = $_match;
		$count_mods = count($_mods);
		for ($i = 0, $for_max = $count_mods; $i < $for_max; $i++) {
			preg_match_all('!:(' . $this->_qstr_regexp . '|[^:]+)!', $_args[$i], $_match);
			$_arg       = $_match[1];
			$_map_array = 1;
			if ($_mods[$i][0] == '@') {
				$_mods[$i]  = substr($_mods[$i], 1);
				$_map_array = 0;
			}

			foreach ($_arg as $key => $value) {
				$_arg[$key] = $this->_parse_variable($value);
			}
			$_plugin_exists = $this->_plugin_exists($_mods[$i], "modifier");
			if ($_plugin_exists || function_exists($_mods[$i])) {
				$_arg         = (count($_arg) > 0) ? ', ' . implode(', ', $_arg) : '';
				$php_function = $_plugin_exists ? "plugin" : "PHP";
				$variable     = sprintf(
					'$this->runModifier((%s), "%s", "%s", %s)',
					$variable,
					$_mods[$i],
					$php_function,
					$_map_array . $_arg
				);
			} else {
				return sprintf(
					'$this->throwError("\'%s\' modifier does not exist", __FILE__, __LINE__);',
					$_mods[$i]
				);
			}
		}
		return $variable;
	}

	public function _plugin_exists($function, $type)
	{
		$_plugins_fun = $this->_plugins[$type][$function];
		if (empty($_plugins_fun)) {
			if ($register = $this->callback('register', array($function, $type, $this))) {
				return $register;
			}
		}
		if (
			isset($_plugins_fun) &&
			is_array($_plugins_fun) &&
			class_exists($_plugins_fun[0]) &&
			method_exists($_plugins_fun[0], $_plugins_fun[1])
		) {

			if (is_object($_plugins_fun[0])) {
				return $_plugins_fun[0] . '->' . $_plugins_fun[1];
			} else {
				return $_plugins_fun[0] . '::' . $_plugins_fun[1];
			}
		}
		// check for standard functions
		if (isset($_plugins_fun) && !is_array($_plugins_fun) && function_exists($_plugins_fun)) {
			return $_plugins_fun;
		}

		// check for a plugin in the plugin directory
		$_plugins_file_name = $type . '.' . $function . '.php';
		$pluginfile         = $this->_get_plugin_dir($_plugins_file_name);
		// var_dump($pluginfile);
		if (is_file($pluginfile)) {
			require_once $pluginfile;
			$_plugins_fun_name = 'tpl_' . $type . '_' . $function;
			if (function_exists($_plugins_fun_name)) {
				$this->register_modifier($function, $_plugins_fun_name);
				$this->_require_stack[$_plugins_file_name] = array($type, $function, $_plugins_fun_name);
				return $_plugins_fun_name;
			}
		}
		return false;
	}
}
