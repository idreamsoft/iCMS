<?php

/**
 * iPHP - i PHP Framework
 * Copyright (c) 2012 iiiphp.com. All rights reserved.
 *
 * @author icmsdev <iiiphp@qq.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 * Former 表单生成器
 */

class Former
{
    public static $FIELDS   = array();
    public static $DATA   = array();
    public static $APP   = array();

    public static $html     = array();
    public static $layout   = array();
    public static $validate = array();
    public static $vuedata  = array();
    public static $script   = array();
    public static $error    = null;

    public static $prefix   = null;
    public static $config   = array();
    public static $GATEWAY  = 1; // 1后台 0 前台
    public static $VALUES   = array();

    public static $callback   = array();
    public static $variable   = array();

    public static $template   = array(
        'class' => array(
            'group'    => 'form-group row',
            'label'    => 'col-sm-1 col-form-label',
            'container' => 'col-sm-10 input-group',
            'input'    => 'form-control',
            'end'      => 'input-group-append',
            'help'     => 'form-text text-muted',
            'radio'    => '',
            'checkbox' => '',
        ),
        'group' => '
            <div id="{{id}}" class="{{class.group}} {{class}}">
                {{label}}
                <div class="{{class.container}}">
                {{widget}}
                {{end}}
                </div>
            </div>
            {{help}}
            {{script}}
        ',
        'widget'   => '{{content}}',
        'label'    => '<label class="{{class.label}}">{{content}}</label>',
        'end'      => '<span class="{{class.end}}">{{content}}</span>',
        'help'     => '<small class="{{class.help}}">{{content}}</small>',
        'radio'    => '<div class="{{class.radio}}">{{content}}</div>',
        'checkbox' => '<div class="{{class.checkbox}}">{{content}}</div>',
    );
    public static function init($vars = null)
    {
        self::$VALUES = $vars;
    }
    /**
     * [创建表单表单]
     * @param  [type]  $app        [app数据]
     * @param  [type]  $rs         [数据]
     * @return [type]              [description]
     */
    public static function create($app, $data = null)
    {
        self::$APP    = $app;
        self::$FIELDS = $app['fields'];
        self::$DATA   = $data;
        //兼容旧v7
        AppsHelper::compatibleV7(self::$FIELDS);
        // self::render($app, $rs);
    }
    public static function multi_value($rs, $fieldArray)
    {
        foreach ($fieldArray as $key => $field) {
            if (in_array($field['type'], array('node', 'multi_node', 'prop', 'multi_prop'))) {
                $value = array_column($rs, $field['name']);
                // iSQL::values($rs, $field['name'], 'array', null);
                // $value = iSQL::explode_var($value);
                $call  = self::$callback[$field['type']];
                if ($call && is_callable($call)) {
                    self::$variable[$field['name']] = call_user_func_array($call, array($value));
                }
            }
        }
        return self::$variable;
    }
    /**
     * 获取字段数据
     * @param  [type]  $array [字段配置]
     * @param  boolean $ui   [是否把UI标识返回数组]
     * @return Array
     */
    public static function fields($array, &$dataFields = null)
    {
        $result = array_column($array, 'fields');
        $fieldData = array();
        foreach ($result as $key => $value) {
            $fieldData = array_merge($fieldData, $value);
        }
        if (is_array($dataFields)) foreach ((array) $fieldData as $fkey => $fields) {
            $fields['field'] == 'MEDIUMTEXT' && $dataFields[$fkey] = $fields;
        }
        return $fieldData;
    }
    public static function fullFields(&$app, array $data_table)
    {
        // if($data_table===null){
        //     $dtn = apps_mod::data_table_name($app['app']);
        //     $data_table = $app['table'][$dtn];
        // }
        $baseFields = AppsTable::getDataBaseFields($app['app']);
        $primary_key = $data_table['primary'];
        $union_key   = $data_table['union'];
        $fpk = $baseFields[$primary_key];
        $fpk && $app['fields'] += array($primary_key => $fpk);
        $fuk = $baseFields[$union_key];
        $fuk && $app['fields'] += array($union_key => $fuk);
    }

    public static function element($name, $attr = null)
    {
        return FormerHelper::element($name, $attr);
    }
    public static function render($flag = true, $glue = '')
    {
        $html = array();
        foreach (self::$FIELDS as $key => $value) {
            $html[$key] = self::display($value['fields']);
        }
        return $flag
            ? print implode($glue, $html)
            : $html;
    }
    public static function html($field, $value = null, $fkey = null)
    {
        if ($field['type'] == 'br') {
            $id  = $fkey;
            $div = self::element("div")->addClass("clearfloat mt10");
        } else {
            $id      = $field['id'];
            $name    = $field['name'];
            $class   = $field['class'];
            $default = $field['default'];

            list($type, $_type) = explode(':', $field['type']);
            $_value = $value;

            is_null($value) && $value = $default;
            if (empty($value)) {
                if (in_array($field['field'], array('BIGINT', 'INT', 'MEDIUMINT', 'SMALLINT', 'TINYINT'))) {
                    $value = '0';
                } else {
                    $value = '';
                }
            }

            self::fieldOut($value, $type);

            $field['help'] && $help = self::fetch($field['help'], 'help');

            $attr = compact(array('id', 'name', 'type', 'class', 'value'));
            // $attr['id']   = self::$prefix.'_'.$id.'';
            // $attr['name'] = self::$prefix.'['.$name.']';
            // $orig_name = self::$prefix.'[_orig_'.$name.']';
            $attr['id']   = self::id($id);
            $attr['name'] = self::name($name);
            $orig_name    = self::name($name, '_orig_');
            $field['holder'] && $attr['placeholder'] = $field['holder'];

            $classArray = array($attr['class'], self::$template['class']['input']);
            $classArray = array_filter($classArray);
            $classArray = array_unique($classArray);
            $attr['class'] = implode(' ', $classArray);
            if ($type == 'multi_image' || $type == 'multi_file') {
                unset($attr['value']);
                $input = self::element('input', $attr);
            } else {
                $input = self::element('input', $attr);
                $input->val($value);
            }
            if (self::$GATEWAY) {
                if ($type == 'captcha') {
                    return false;
                }
            }
            switch ($type) {
                case 'multi_image':
                case 'multi_file':
                    // $form_group.=' input-append';
                    $typeTitle = ($type == 'multi_file' ? '文件' : '图片');
                    $input->attr('type', 'text');
                    $input->attr('placeholder', '批量上传' . $typeTitle);
                    $input->attr('readonly', 'readonly');
                    if (self::$GATEWAY) {
                        $picbtn = FilesWidget::setData($value,[
                            'return' => true,
                            'multi' => true,
                            'noHttp' => false,
                            'title' => $typeTitle,
                            'type' => $type,
                        ])->picBtn($attr['id']);
                    }
                    $input .= $picbtn;
                    break;
                case 'image':
                case 'file':
                    // $form_group.=' input-append';
                    $typeTitle = ($type == 'file' ? '文件' : '图片');
                    $input->attr('type', 'text');
                    if (self::$GATEWAY) {
                        $picbtn = FilesWidget::setData($value, [
                            'return' => true,
                            'title' => $typeTitle,
                            'type' => $type,
                        ])->picBtn($attr['id']);
                    }
                    $input .= $picbtn;
                    break;
                case 'tpldir':
                case 'tplfile':
                    // $form_group.=' input-append';
                    $input->attr('type', 'text');
                    if (self::$GATEWAY) {
                        $click = 'file';
                        $type == 'tpldir' && $click = 'dir';
                        $modal = FilesWidget::modalBtn($name, $attr['id'], $click);
                    }
                    $input .= $modal;
                    break;
                case 'txt_prop':
                    // $form_group.=' input-append';
                    $input->attr('type', 'text');
                    if (self::$GATEWAY) {
                        $prop = PropWidget::btnGroup($name, $attr['id'], self::$APP['app']);
                        $prop .= PropWidget::btn('添加' . $field['label'], $name, self::$APP['app']);
                    }
                    $input .= $prop;
                    break;
                case 'prop':
                case 'multi_prop':
                    unset($attr['type']);
                    $attr['data-placeholder'] = '请选择' . $field['label'] . '...';
                    if (strpos($type, 'multi') !== false) {
                        $attr['name']     = $attr['name'] . '[]';
                        $attr['multiple'] = 'true';
                        $attr['data-placeholder'] = '请选择' . $field['label'] . '(可多选)...';
                        // $orig = self::element('input', array('type' => 'hidden', 'name' => $orig_name, 'value' => $value));
                    }
                    $end = PropWidget::btn('添加' . $field['label'], $name, self::$APP['app']);
                    $attr['data-toggle'] = "chosen";
                    $attr['v-model'] = $attr['id'];
                    $value === null or self::$vuedata[$attr['id']] = $value;
                    $select = self::element('select', $attr);
                    $option_text = '';
                    if (self::$GATEWAY) {
                        $option_text = '[' . $name . '=\'0\']';
                    }
                    // $selected = empty($value) ? 'selected' : '';
                    $selected = '';
                    $option = sprintf(
                        '<option value="0" %s>默认%s</option>',
                        $selected,
                        $field['label'] . $option_text
                    );
                    if (PropAdmincp::$default['option'][$name]) {
                        $option .= PropAdmincp::$default['option'][$name];
                    }
                    $option .= PropWidget::app(self::$APP['app'])
                        ->getOption($name, $value, self::$config['option']);
                    // $option .= PropWidget::get($name, null, 'option', null, self::$APP['app'], self::$config['option']);
                    // $value === null or $script = self::script('iCMS.FORMER.select("' . $attr['id'] . '","' . $value . '");', true);
                    // $input = $select->html($option) . $orig . $btn;
                    $input = $select->html($option);
                    break;
                case 'date':
                case 'datetime':
                    $attr['class'] .= ' ui-datepicker';
                    $attr['type'] = 'text';
                    $input = self::element('input', $attr);
                    $input->val($value);
                    break;
                case 'user_node':
                    if (self::$GATEWAY) {
                        $form_group = ' former_hide';
                        $input->attr('type', 'hidden');
                    }
                    break;
                case 'PRIMARY':
                case 'union':
                case 'hidden':
                    $form_group = ' former_hide';
                    $input->attr('type', 'hidden');
                    break;
                case 'relation':
                    $value or $value = Request::param($id);
                    $input->attr('type', 'text');
                    $input->val($value);
                    break;
                case 'userid':
                    $form_group = ' former_hide';
                    $value or $value = self::$VALUES['userid'];
                    $input->attr('type', 'text');
                    $input->val($value);
                    break;
                case 'nickname':
                case 'username':
                    $value or $value = self::$VALUES[$type];
                    $input->attr('type', 'text');
                    $input->val($value);
                    break;
                case 'tag':
                    $input = $input->attr('type', 'text')->attr('onkeyup', "javascript:this.value=this.value.replace(/，/ig,',');");
                    // $orig = self::element('input', array('type' => 'hidden', 'name' => $orig_name, 'value' => $value));
                    // $input .= $orig;
                    break;
                case 'number':
                    $input->attr('type', 'text');
                    break;
                case 'text':
                    break;
                case 'captcha':
                    $input->addClass('captcha')->attr('maxlength', "4")->attr('type', 'text');
                    $help = PublicApp::captcha();
                    break;
                case 'editor':
                    $attr['class'] = 'editor-body';
                    $attr['type']  = 'text/plain';
                    $input = self::element('textarea', $attr);
                    if (self::$GATEWAY) {
                        $editorId = 'editor-body-' . $attr['id'];
                        $script = Editor::ueditor($attr['id'], self::$config, self::$GATEWAY);
                        $input = self::element("div", [
                            'id' => $editorId,
                            'class' => 'editor-container',
                            'style' => 'width: 100%;'
                        ])->html($input);
                    }
                    break;
                case 'markdown':
                    $attr['class'] = 'editor-body';
                    $attr['type']  = 'text/plain';
                    $attr['style']  = 'display:none;';
                    $input = self::element('textarea', $attr);
                    $input->val('');
                    if (self::$GATEWAY) {
                        $editorId = 'editor-body-' . $attr['id'];
                        $script = Editor::markdown($editorId, self::$config, self::$GATEWAY);
                        $input2 = self::element("script", [
                            'id' => $editorId,
                            'class' => 'editor-container',
                            'type' => 'text/plain',
                            'style' => 'width: 100%;'
                        ])->html($value);
                        $input = $input2.$input;
                    }
                    break;
                case 'multitext':
                    unset($attr['type']);
                    $input = self::element('textarea', $attr);
                    $input->css('height', '300px');
                    break;
                case 'textarea':
                    unset($attr['type']);
                    $input = self::element('textarea', $attr);
                    $input->css('height', '150px');
                    break;
                case 'switch':
                case 'radio':
                case 'radio_prop':
                case 'checkbox':
                case 'checkbox_prop':
                    if ($type == 'checkbox' || $type == 'checkbox_prop') {
                        $attr['name'] = $attr['name'] . '[]';
                    }
                    $btn = '';
                    if ($type == 'radio_prop' || $type == 'checkbox_prop') {
                        $attr['type']    = str_replace('_prop', '', $type);
                        $propArray       = PropWidget::get($name, null, 'array', null, self::$APP['app'], self::$config['option']);
                        if ($propArray) {
                            //交换键值
                            $field['option'] = array_flip($propArray);
                        } else {
                            $field['option'] = '默认' . $field['label'] . '=0;';
                        }
                        $btn = PropWidget::btn('添加' . $field['label'], $name, self::$APP['app']);
                    }
                    $attr['v-model'] = $attr['id'];
                    $input->attr('v-model', $attr['id']);
                    $value === null or self::$vuedata[$attr['id']] = $value;
                    $field['option'] && $input  = FormerHelper::checkbox($field['option'], $attr);
                    $input = self::fetch($input, $attr['type']);
                    if ($type == 'switch') {
                        $attr['type'] = 'checkbox';
                        $input = self::element('input', $attr);
                        $input = '<div class="switch">' . $input . '</div>';
                    }
                    $input .= $btn;
                    break;
                case 'multi_node':
                case 'node':
                    unset($attr['type']);
                    $attr['data-placeholder'] = '请选择所属' . $field['label'] . '...';
                    if (strpos($type, 'multi') !== false) {
                        $attr['name']     = $attr['name'] . '[]';
                        $attr['multiple'] = 'true';
                        $attr['data-placeholder'] = '请选择' . $field['label'] . '(可多选)...';
                        // $orig = self::element('input', array('type' => 'hidden', 'name' => $orig_name, 'value' => $value));
                    }
                    $attr['data-toggle'] = "chosen";
                    $attr['v-model'] = $attr['id'];
                    $value === null or self::$vuedata[$attr['id']] = $value;
                    $select = self::element('select', $attr);
                    $option = Node::set('APPID', Node::$APPID)->set('ACCESS', 'cm')->select();
                    $input = $select->html($option); //$orig;
                    break;
                case 'multiple':
                case 'select':
                    // $attr['class'] = $field['class'];
                    $attr['data-placeholder'] = '请选择' . $field['label'] . '...';
                    if (strpos($type, 'multi') !== false) {
                        unset($attr['type']);
                        $attr['multiple'] = 'true';
                        $attr['name']     = $attr['name'] . '[]';
                        $attr['data-placeholder'] = '请选择' . $field['label'] . '(可多选)...';
                        // $_input = self::element('input', array('type' => 'hidden', 'name' => $orig_name, 'value' => $value));
                    }
                    $attr['data-toggle'] = "chosen";
                    $attr['v-model'] = $attr['id'];
                    $value === null or self::$vuedata[$attr['id']] = $value;
                    $input  = self::element('select', $attr);
                    $option = '';
                    if (self::$GATEWAY) {
                        $option .= '<option value=""></option>';
                    }
                    if ($field['option']) {
                        $option = FormerHelper::option($field['option'], $name, $value);
                        $input->html($option);
                    }
                    // $input .= $_input;
                    break;
                case 'device':
                    is_null($_value) && $value = iPHP_MOBILE ? '1' : '0';
                    $input->val($value);
                    break;
                case 'postype':
                    is_null($_value) && $value = self::$GATEWAY;
                    $input->val($value);
                    break;
                default:
                    $input->attr('type', 'text');
                    break;
            }
            if ($_type == 'hidden') {
                $form_group = ' former_hide';
                $input->attr('type', 'hidden');
            }
            if ($field['label-after']) {
                $end = '<div class="input-group-text">' . $field['label-after'] . '</div>';
            }
            $htmlArray = array(
                'class'  => 'former_' . $type . ' ' . $form_group,
                'id'     => $form_group_id ?: 'fg-' . random(6),
                'label'  => self::fetch($field['label'], 'label'),
                'widget' => self::fetch($input, 'widget'),
                'end'    => self::fetch($end, 'end'),
                'help'   => self::fetch($help, 'help'),
                'script' => $script,
            );
            $div = self::fetch($htmlArray);
        }
        self::$html[$id] = $div;
        return true;
    }
    public static function fetch($html, $key = "group")
    {
        $output = $html;
        $template = self::$template[$key];
        if ($template) {
            $output = self::template_class($template);
            if (is_array($html)) {
                foreach ($html as $k => $value) {
                    $output = str_replace('{{' . $k . '}}', $value, $output);
                }
            } else {
                $output = str_replace('{{content}}', $html, $output);
                is_null($html) && $output = '';
            }
        }
        return $output;
    }
    public static function set_template_class(array $class)
    {
        self::$template['class'] = array_merge(self::$template['class'], $class);
    }
    public static function template_class($html)
    {
        if (self::$template['class']) foreach (self::$template['class'] as $k => $value) {
            $search[]  = '{{class.' . $k . '}}';
            $replace[] = $value;
        }
        $html = str_replace($search, $replace, $html);
        return $html;
    }

    public static function script($code = null, $script = false, $ready = true)
    {
        if ($code) {
            $code = '+function(){' . $code . '}();';
            $ready && $code = '$(function(){' . $code . '});';
            return $script ? '<script>' . $code . '</script>' : $code;
        }
    }
    public static function js_test($id, $label, $msg, $pattern)
    {
        $script = '
            var ' . $id . '_msg = "' . $msg . '",pattern = ' . $pattern . ';
            if(!pattern.test(' . $id . '_value)){
                iCMS.ui.alert(' . $id . '_error||"[' . $label . ']"+' . $id . '_msg+",请重新填写!");
                ' . $id . '.focus();
                return false;
            }';
        return $script;
    }
    public static function id($name, $pre = null)
    {
        if (self::$prefix) {
            return self::$prefix . '_' . $pre . $name;
        } else {
            return $pre . $name;
        }
    }
    public static function name($name, $pre = null)
    {
        if (self::$prefix) {
            return self::$prefix . '[' . $pre . $name . ']';
        } else {
            return $pre . $name;
        }
    }
    public static function post()
    {
        if (self::$prefix) {
            return $_POST[self::$prefix];
        } else {
            return $_POST;
        }
    }
    public static function validate($field_array, $lang = 'js', $value = '')
    {
        if (empty($field_array['validate'])) return;

        $id    = self::id($field_array['id']);
        $name  = self::name($field_array['name']);
        $label = $field_array['label'];
        $type  = $field_array['type'];
        $error = $field_array['error'];

        if ($lang == 'js') {
            $javascript = 'var ' . $id . ' = $("#' . $id . '"),' . $id . '_value = ' . $id . '.val(),' . $id . '_error="' . $error . '";';
            if ($type == 'editor') {
                $editor_id = Editor::getID($id,'UE');
                // $script = 'var '.$id.' = iCMS.editor.get("editor-body-'.$id.'"),'.$id.'_value = '.$id.'.hasContents()';
                $javascript = 'var ' . $id . '_value = ' . $editor_id . '.getContent();';
            }
            if ($type == 'markdown') {
                $javascript = 'var ' . $id . ' = $("#' . $id . '"),' . $id . '_value = ' . $id . '.text(),' . $id . '_error="' . $error . '";';
                $javascript .= 'if(' . $id . '_value==\'\\n\'){' . $id . '_value = "";}';
                // $editor_id = Editor::getID($id,'MD');
                // $javascript = 'var ' . $id . '_value = ' . $editor_id . '.getValue();';
                // $javascript = 'var ' . $id . ' = iCMS.editor.get("editor-body-' . $id . '"),' . $id . '_value = ' . $id . '.getValue()';
            }
        }

        foreach ($field_array['validate'] as $key => $vd) {
            $code = null;
            switch ($vd) {
                case 'zipcode':
                    $msg = "邮政编码有误";
                    $pattern = "/^[1-9]{1}(\d+){5}$/";
                    break;
                case 'idcard':
                    $msg = "身份证有误";
                    $pattern = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
                    break;
                case 'telphone':
                    $msg = "固定电话号码有误";
                    $pattern = "/^(\(\d{3,4}\)|\d{3,4}-|\s)?\d{7,14}$/";
                    break;
                case 'mobphone':
                    $msg = "手机号码有误";
                    $pattern = "/^1[34578]\d{9}$/";
                    break;
                case 'phone':
                    $msg = "电话或手机号码有误";
                    $pattern = "/(^(\(\d{3,4}\)|\d{3,4}-|\s)?\d{7,14}$)|(^1[34578]\d{9}$)/";
                    break;
                case 'url':
                    $msg = "网址有误";
                    $pattern = "/^[a-zA-z]+:\/\/[^\s]*/";
                    break;
                case 'email':
                    $msg = "邮箱地址有误";
                    $pattern = "/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/";
                    break;
                case 'number':
                    $msg = "只能输入数字";
                    $pattern = "/^\d+(\.\d+)?$/";
                    break;
                case 'hanzi':
                    $msg = "只能输入汉字";
                    $pattern = "/^[\u4e00-\u9fa5]*$/";
                    if ($lang == 'php') {
                        $pattern = "/^[\x{4e00}-\x{9fa5}]*$/u";
                    }
                    break;
                case 'character':
                    $msg = "只能输入字母";
                    $pattern = "/^[A-Za-z]+$/";
                    break;
                case 'empty':
                    $msg = $label . '不能为空!';
                    if ($lang == 'php') {
                        empty($value) && Script::alert($msg);
                    } else {
                        $code = '
                            if(!' . $id . '_value){
                                iCMS.ui.alert(' . $id . '_error||"' . $msg . '");
                                ' . $id . '.focus();
                                return false;
                            }
                        ';
                    }
                    break;
                case 'minmax':
                    $min  = $field_array['minmax'][0];
                    $max  = $field_array['minmax'][1];
                    $msg_min = '您填写的' . $label . '小于' . $min . ',请重新填写!';
                    $msg_max = '您填写的' . $label . '大于' . $max . ',请重新填写!';
                    if ($lang == 'php') {
                        ($value < $min) && Script::alert($msg_min);
                        ($value > $max) && Script::alert($msg_max);
                    } else {
                        $code = '
                            var value = parseInt(' . $id . '_value)||0;
                            // console.log(value);
                            if (value < ' . $min . ') {
                                iCMS.ui.alert(' . $id . '_error||"' . $msg_min . '");
                                ' . $id . '.focus();
                                return false;
                            }
                            if (value > ' . $max . ') {
                                iCMS.ui.alert(' . $id . '_error||"' . $msg_max . '");
                                ' . $id . '.focus();
                                return false;
                            }
                        ';
                    }

                    break;
                case 'count':
                    $min  = $field_array['count'][0];
                    $max  = $field_array['count'][1];
                    $msg_min = '您填写的' . $label . '小于' . $min . '字符,请重新填写!';
                    $msg_max = '您填写的' . $label . '大于' . $max . '字符,请重新填写!';
                    if ($lang == 'php') {
                        (strlen($value) < $min) && Script::alert($msg_min);
                        (strlen($value) > $max) && Script::alert($msg_max);
                    } else {
                        $code = '
                            var value = ' . $id . '_value.replace(/[^\x00-\xff]/g, \'xx\').length;
                            // console.log(value);
                            if (value < ' . $min . ') {
                                iCMS.ui.alert(' . $id . '_error||"' . $msg_min . '");
                                ' . $id . '.focus();
                                return false;
                            }
                            if (value > ' . $max . ') {
                                iCMS.ui.alert(' . $id . '_error||"' . $msg_max . '");
                                ' . $id . '.focus();
                                return false;
                            }
                        ';
                    }

                    break;
                case 'defined':
                    $field_array['defined'] && $code = stripcslashes($field_array['defined']);
                    break;
                default:
                    # code...
                    break;
            }
            if ($lang == 'php') {
                if ($pattern && $msg) {
                    preg_match($pattern, $value) or Script::alert($msg);
                }
            } else {
                if (empty($code)) {
                    $code = self::js_test($id, $label, $msg, $pattern);
                }
                $javascript .= $code;
            }
        }
        $javascript = preg_replace('/\s{3,}/is', '', $javascript);
        self::$validate[] = $javascript;
        self::$script[] = self::script(stripcslashes($field_array['javascript']));

        return $javascript;
    }
    /**
     * 在表单上显示的值
     *
     * @param [type] $value
     * @param [type] $type
     * @return void
     */
    public static function fieldOut(&$value, $type)
    {
        if (!is_null($value)) {
            switch ($type) {
                case 'date':
                    $value = get_date($value, 'Y-m-d');
                    break;
                case 'datetime':
                    $value = get_date($value, 'Y-m-d H:i:s');
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
                case 'multiple':
                case 'multi_node':
                case 'multi_prop':
                case 'checkbox':
                case 'checkbox_prop':
                    is_array($value) or $value = json_decode($value, true);
                    empty($value) && $value = [];
                    break;
                default:
                    # code...
                    break;
            }
        }
        return $value;
    }
    //字段数据输出处理(后台 自定义表单)
    public static function field_output($value, $fields, $vArray = null)
    {
        if (empty($value)) {
            return $value;
        }
        //字段数据类型
        $field = $fields['field'];

        //字段类型
        list($type, $_type) = explode(':', $fields['type']);

        self::fieldOut($value, $type);

        if (in_array($type, array('node', 'multi_node'))) {
            $variable = self::$variable[$fields['name']];
            $valArray = is_array($value) ? $value : explode(",", $value);
            $value = '';
            if ($valArray) foreach ($valArray as $i => $val) {
                $array = $variable[$val];
                $value .= '<a href="' . APP_DOURL . '&cid=' . $val . '&' . $uri . '">' . $array->name . '</a>';
            }
        }
        //多选字段转换
        if (!empty($fields['multiple'])) {
            is_array($value) or $value = explode(',', $value);
        }

        return $value;
    }
    //字段数据输入处理
    public static function fieldIn($value, $fields)
    {
        $error = null;
        $alert = null;
        $max = null;
        //字段数据类型
        $field = $fields['field'];
        //字段类型
        list($type, $_type) = explode(':', $fields['type']);

        //数字转换
        if (in_array($field, array('BIGINT', 'INT', 'MEDIUMINT', 'SMALLINT', 'TINYINT'))) {
            $value = (int) $value;
            $field == 'TINYINT' && $max = 255;
            $field == 'SMALLINT' && $max = 65535;
            $field == 'MEDIUMINT' && $max = 8388607;
            $field == 'INT' && $max = 4294967296;
            if ($max && $value > $max) {
                $alert = $fields['label'] . '超出最大允许值,(0-' . $max . ')';
            }
        }
        if (in_array($field, array('DECIMAL'))) {
            list($lenght, $digit) = explode(',', $fields['len']);
            empty($digit) && $digit = 2;
            $value = sprintf("%01.{$digit}f", $value);
            $len = $lenght - $digit;
            $max = (float)(str_repeat('9', $len) . '.' . str_repeat('9', $digit));
            if ($value > $max) {
                $alert = $fields['label'] . '超出最大允许值,(0-' . $max . ')';
            }
        }
        //多选字段转换
        if (!empty($fields['multiple'])) {
            // is_array($value) && $value = implode(',', $value);
        }

        //时间转换
        if (in_array($type, array('date', 'datetime'))) {
            $value = str2time($value);
            if ($_type == 'hidden') {
                $value = time();
            }
        } elseif (in_array($type, array('ip'))) {
            $value = Request::ip();
        } elseif (in_array($type, array('referer'))) {
            $value = $_SERVER['HTTP_REFERER'];
        } elseif ($type == 'percentage') {
            $value = (int)$value;
            $max = 100;
            if ($value > $max) {
                $alert = $fields['label'] . '超出最大允许值,(0-' . $max . ')';
            }
        } elseif ($type == 'image') {
            $name = $fields['name'];
            if (Request::isUrl($value) && !isset($_POST[$name . '_http'])) {
                $value  = FilesClient::remote($value);
            }
        } elseif ($type == 'captcha') {
            if (self::$GATEWAY) {
                return true;
            }
            $captcha = Security::escapeStr($value);
            if (!Captcha::check($captcha)) {
                $alert = Lang::get('iCMS:captcha:error');
            }
        } elseif ($type == 'editor') {
            if (!self::$GATEWAY) {
                $value = Vendor::run('CleanHtml', array($value));
            }
            Request::post('dellink') && $value = preg_replace("/<a[^>].*?>(.*?)<\/a>/si", "\\1", $value);
            Request::post('noWatermark') && FilesMark::$enable = false;
            if (Request::post('remote')) {
                $value = FilesPic::remote($value, true);
                $value = FilesPic::remote($value, true);
                $value = FilesPic::remote($value, true);
            }
        } elseif ($type == 'json') {
            $value = json_decode($value);
            $value = json_encode($value);
        } else {
            $value = Security::escapeStr($value);
        }
        // var_dump($fields, $value);

        $alert && iPHP::alert($alert);
        $error && iPHP::throwError($error);
        return $value;
    }
    /**
     * 处理表单数据
     * @param  [type] $app [app数据]
     * @return Array     [description]
     */
    public static function postData($app, $content = null)
    {
        is_null($content) && $content = self::post();

        if (empty($content)) {
            iPHP::alert('no post data');
        }

        $contentData = array();
        $fileData = array();
        $tagData = array();
        $nodeData = array();

        $fieldsArray = self::fields($app['fields']);

        foreach ($content as $key => &$value) {
            $fields = $fieldsArray[$key];
            if (empty($fields)) {
                unset($content[$key]);
                continue;
            }
            //字段绑定的函数处理
            $fields['func'] && $value = self::func($fields['func'], $value);
            //字段数据处理
            $value = self::fieldIn($value, $fields);

            //数据验证
            self::validate($fields, 'php', $value);

            //找查MEDIUMTEXT字段
            if ($fields['field'] == 'MEDIUMTEXT') {
                $contentData[$key] = $value;
                unset($content[$key]);
            }
        }
        return compact('content', 'contentData');
    }
    /**
     * 表单数据处理
     * @param  [type] $func  [description]
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    public static function func($func, $value, $type = 'input')
    {
        return $value;
    }
    public static function display($fields = null)
    {
        self::$html = array();
        self::$vuedata = array();
        self::$validate = array();
        self::$script = array();
        foreach ($fields as $id => $field) {
            $value = self::$DATA[$field['name']];
            self::$DATA === null && $value = null;
            $flag = self::html($field, $value, $id);
            $flag && self::validate($field);
        }
        return self::layout();
    }
    public static function layout($id = null, $func = 'submit')
    {
        $pieces = array();
        if (empty($GLOBALS['former.css'])) {
            $GLOBALS['former.css'] = true;
            $pieces[] = '<link rel="stylesheet" href="./app/former/assets/former.css" type="text/css" />';
        }
        self::$html && $pieces[] = implode('', self::$html);
        $pieces[] = '<script type="text/javascript">';
        self::$vuedata && $pieces[] = 'iCMS.set(\'Vue.data\',' . json_encode(self::$vuedata) . ');';
        $pieces[] = '$(function(){';
        if (self::$validate) {
            if ($id === null && defined('APP_FORMID')) {
                $id = '#' . APP_FORMID;
            }
            $js = file_get_contents(__DIR__ . '/assets/former.js');
            $validate = implode(';', self::$validate);
            $pieces[] = str_replace(
                array('$APP_FORMID', 'function validate() {};', ').submit('),
                array("'{$id}'", $validate, ").{$func}("),
                $js
            );
        }
        self::$script && $pieces[] = implode(';', self::$script);;
        $pieces[] = '});';
        $pieces[] = '</script>';
        // if (isset(self::$APP['apptype'])) {
        //     $pieces[] = '<hr />';
        //     $pieces[] = '<a class="btn btn-dark" href="' . ADMINCP_URL . '=apps&do=edit&id=' . self::$APP['id'] . '" target="_blank">增加自定义字段</a><hr />';
        //     $pieces[] = '<a href="https://www.icmsdev.com/docs/add-custom-fields.html" target="_blank">查看增加自定义字段教程</a>';
        // }
        return implode(PHP_EOL, $pieces);
    }
}
