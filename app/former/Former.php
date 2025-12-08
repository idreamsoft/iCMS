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
    public static $FIELDS = [];
    public static $DATA = [];
    public static $APP = [];

    public static $html = [];
    public static $layout = [];
    public static $validate = [];
    public static $script = [];
    public static $error = null;

    public static $prefix = 'rs';
    public static $config = [];
    public static $GATEWAY = 1; // 1后台 0 前台
    public static $VALUES = [];

    public static $callback = [];
    public static $variable = [];

    public static $template = [
        'class' => [
            'group'     => 'form-group row',
            'label'     => 'col-sm-2 col-form-label text-sm-end',
            'container' => 'col-sm-8 ',
            'input'     => 'form-control',
            'end'       => 'input-group-text',
            'help'      => 'form-text text-muted',
            'radio'     => '',
            'checkbox'  => '',
        ],
        'group' => '
            <div id="{{id}}" class="{{class.group}} {{class}}">
                {{label}}
                <div class="{{class.container}}">
                {{widget}}
                {{end}}
                {{help}}
                </div>
            </div>
            {{script}}
        ',
        'widget'   => '{{content}}',
        'label'    => '<label class="{{class.label}}">{{content}}</label>',
        'end'      => '<span class="{{class.end}}">{{content}}</span>',
        'help'     => '<small class="{{class.help}}">{{content}}</small>',
        'radio'    => '<div class="{{class.radio}}">{{content}}</div>',
        'checkbox' => '<div class="{{class.checkbox}}">{{content}}</div>',
        'script'   => '<script type="text/javascript">{{content}}</script>',
    ];

    public static function init($vars = null)
    {
        self::$VALUES = $vars;
    }

    /**
     * 创建表单表单
     * @param  [type]  $app        [app数据]
     * @param  [type]  $rs         [数据]
     * @return void
     */
    public static function create($app, $data = null)
    {
        self::$APP = $app;
        self::$FIELDS = $app['fields'];
        self::$DATA = $data ?: [];
        //兼容旧v7
        AppsHelper::compatibleV7(self::$FIELDS);
        // self::render($app, $rs);
    }

    public static function multiValue($rs, $fieldArray)
    {
        foreach ($fieldArray as $key => $field) {
            if (in_array($field['type'], ['node', 'multi_node', 'prop', 'multi_prop'])) {
                $value = array_column($rs, $field['name']);
                // iSQL::values($rs, $field['name'], 'array', null);
                // $value = iSQL::explode_var($value);
                $call = self::$callback[$field['type']];
                if ($call && is_callable($call)) {
                    self::$variable[$field['name']] = call_user_func_array($call, [$value]);
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
    public static function getField($fields, $field)
    {
        $fieldAll = Former::fields($fields);
        return $fieldAll[$field] ?: [];
    }

    public static function fields($fieldArray, &$dataFields = null)
    {
        $fieldData = [];
        if (is_array($fieldArray)) {
            $result = array_column($fieldArray, 'fields');
            foreach ($result as $key => $value) {
                $fieldData = array_merge($fieldData, $value);
            }
        }
        if (is_array($dataFields)) {
            foreach ((array) $fieldData as $fkey => $fields) {
                $fields['field'] && strtoupper($fields['field']) == 'MEDIUMTEXT' && $dataFields[$fkey] = $fields;
            }
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
        $union_key = $data_table['union'];
        $fpk = $baseFields[$primary_key];
        $fpk && $app['fields'] += [$primary_key => $fpk];
        $fuk = $baseFields[$union_key];
        $fuk && $app['fields'] += [$union_key => $fuk];
    }

    public static function element($name, $attr = null)
    {
        $el = new iQuery($name);
        $attr && $el->attr($attr);
        return $el;
    }

    public static function optionItem($text, $value, &$where, $selected, $fields, $separator, $vars)
    {
        $hash = md5($text . $value);
        $active = $selected && in_array($value, $selected) ? true : false;
        $field = $fields['id'];
        if (in_array($fields['type'], ['checkbox', 'multiple', 'checkbox_prop'])) {
            $urlVals = $selected;
            $urlVals[] = $value;
            $urlVals = array_unique($urlVals);
            if ($active) {
                $where[$field][] = [$field, 'FIND_IN_SET', $value];
                $k = array_search($value, $urlVals);
                unset($urlVals[$k]);
            }
        } else {
            $active && $where[$field] = $value;
            $urlVals = [$value];
        }

        $url = Route::make([$field=> implode($separator, $urlVals)], $vars['url']);
        return [
            'id'      => $field . '-' . substr($hash, 8, 16),
            'field'   => $field,
            'value'   => $value,
            'name'    => $text,
            'text'    => $text,
            'url'     => $url,
            'active'  => $active,
            'checked' => $active,
        ];
    }

    /**
     * @param [type] $data
     * @return array
     */
    public static function optionData($data)
    {
        if (!is_array($data)) {
            $data = explode(';', $data);
        }
        ksort($data);
        $options = [];

        foreach ($data as $value => $text) {
            if ($text === '') {
                continue;
            }
            if (preg_match('/(.*)\((.*)\)/', $text, $match)) {
                $text = $match[1];
                self::textValue($text, $value);
                $subData = explode(',', $match[2]);
                $items = [];
                foreach ($subData as $k => $val) {
                    $items[] = ['value'=>$value . '-' . $k, 'text'=>$val];
                }
                $options[$value] = ['value'=>$value, 'text'=>$text, 'items'=>$items];
            } else {
                self::textValue($text, $value);
                $options[$value] = $text;
            }
        }
        return $options;
    }

    public static function textValue(&$text, &$value)
    {
        if (strpos($text, '=') !== false) {
            list($text, $value) = explode('=', $text);
        }
        is_null($value) && $value = $text;
        $text = trim($text, "\r\n");
    }

    public static function makeCheckbox($data, $attr, $selected = null)
    {
        $options = self::optionData($data);
        $html = '';
        $nav = '';
        $block = '';
        $active = null;
        foreach ($options as $value => $text) {
            if (isset($text['items'])) {
                $text['id'] = md5($text['text'] . $text['value']);
                $active = is_null($active) ? 'active' : '';
                $text['active'] = $active;
                $nav .= Utils::template('
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{active}}" id="{{id}}-tab" data-bs-toggle="tab" data-bs-target="#{{id}}" role="tab" aria-controls="{{id}}" aria-selected="true">{{text}}</button>
                </li>', $text);
                $checkItem = '';
                foreach ($text['items'] as $k => $v) {
                    $checkItem .= self::makeCheckItem($v['text'], $v['value'], $attr);
                }
                $block .= Utils::template(
                    '<div class="tab-pane {{active}}" id="{{id}}" role="tabpanel" aria-labelledby="{{id}}-tab" tabindex="0">{{checkItem}}</div>',
                    ['id'=> $text['id'], 'active'=>$text['active'], 'checkItem'=> $checkItem]
                );
            } else {
                $html .= self::makeCheckItem($text, $value, $attr);
                // $html.= self::element('div', ['class' => 'col-md-2'])->html($checkDiv);
            }
        }
        if ($nav) {
            $html = sprintf('<div class="block">
            <ul class="nav nav-tabs nav-tabs-block" role="tablist">%s</ul>
            <div class="block-content tab-content p-2">%s</div>
          </div>', $nav, $block);
        }
        // $html = self::element('div', ['class' => 'row'])->html($html);
        return $html;
    }

    public static function makeCheckItem($text, $value, $attr)
    {
        $attr2 = $attr;
        $attr2['value'] = $value;
        $attr2['class'] = 'form-check-input ' . $attr2['id'];
        $attr2['id'] .= '_' . $value;
        $label = self::element(
            'label',
            [
                'for' => $attr2['id'],
                // 'class' => $attr['type'] . '-inline'
                'class' => 'form-check-label'
            ]
        )->html($text);
        $input = self::element('input', $attr2);

        if (Former::$template['class']['input']) {
            $input->removeClass(Former::$template['class']['input']);
        }
        $element = $input . $label;

        return self::element('div', ['class' => 'form-check form-check-inline'])->html($element);
    }

    public static function makeOption($data, $name, $selecteds = null)
    {
        $options = self::optionData($data);
        $option = '';
        $selecteds = explode(',', $selecteds);
        foreach ($options as $value => $text) {
            $selected = in_array($value, $selecteds);
            $selected = $selected ? 'selected' : '';
            $type = Former::$config['option'] ? '[' . $name . '="' . $value . '"]' : '';
            $option .= sprintf(
                '<option value="%s" %s>%s %s</option>',
                $value,
                $selected,
                $text,
                $type
            );
        }
        return $option;
    }

    public static function admincp($data, $apps = null)
    {
        is_null($apps) && $apps = Admincp::$APP_DATA;
        if (!is_array($apps)) {
            $apps = Apps::get($apps);
        }
        if ($apps['fields']) {
            Content::model($apps);
            Content::cdata($data);
            FormerApp::add($apps, $data);
        }
        return new self();
    }

    public static function usercp($apps, $data = null, $option = true)
    {
        Former::$VALUES = [
            'userid'   => User::$id,
            'username' => User::$account,
            'nickname' => User::$nickname
        ];
        FormerApp::add($apps, $data, $option);
    }

    public static function render($flag = true, $glue = '')
    {
        if (empty(self::$FIELDS)) {
            return;
        }
        // $former_appid = self::$GATEWAY ? self::$APP['id'] : encrypt(self::$APP['id']);
        $html = [];
        // $html[] = '<input type="hidden" name="formCallUrl[]" value="{{former/save}}">';
        self::$GATEWAY && $html[] = '<input type="hidden" name="former_appid" value="' . self::$APP['id'] . '">';
        // $pk =  AppsTable::getPrimaryKey(self::$APP['id']);
        // $html[] = '<input type="hidden" name="former_primary" value="id">';

        foreach (self::$FIELDS as $key => $value) {
            $html[] = self::display($value['fields']);
        }
        return $flag
            ? print implode($glue, $html)
            : $html;
    }

    public static function html($field, $value = null, $fkey = null)
    {
        if ($field['type'] == 'br') {
            $id = $fkey;
            $div = self::element('div')->addClass('clearfloat mt10');
        } else {
            $id = $field['id'];
            $name = $field['name'];
            $class = $field['class'];
            $default = $field['default'];
            $default === 'NULL' && $default = null;
            $default === 'TIMESTAMP' && $default = time();
            list($type, $_type) = explode(':', $field['type']);
            $_value = $value;
            is_null($value) && $value = $default;
            if ($value === '' && in_array(strtoupper($field['field']), ['BIGINT', 'INT', 'MEDIUMINT', 'SMALLINT', 'TINYINT'])) {
                $value = '0';
            }
            self::fieldOut($value, $type);

            $field['help'] && $help = self::template($field['help'], 'help');

            $attr = compact(['id', 'name', 'type', 'class', 'value']);
            // $attr['id']   = self::$prefix.'_'.$id.'';
            // $attr['name'] = self::$prefix.'['.$name.']';
            // $orig_name = self::$prefix.'[_orig_'.$name.']';
            $attr['id'] = self::id($id);
            $attr['name'] = self::name($name);
            $orig_name = self::name($name, '_orig_');
            $field['holder'] && $attr['placeholder'] = $field['holder'];

            $classArray = [$attr['class'], self::$template['class']['input']];
            $classArray = array_filter($classArray);
            $classArray = array_unique($classArray);
            $attr['class'] = implode(' ', $classArray);
            // if ($type == 'multi_image' || $type == 'multi_file') {
            //     unset($attr['value']);
            //     $input = self::element('input', $attr);
            // } else {
            $input = self::element('input', $attr);
            $input->attr('type', 'text');
            $input->val($value);
            $field['ui'] && $input->attr('i', $field['ui']);
            // }
            switch ($type) {
                case 'multi_image':
                case 'multi_file':
                    $typeTitle = ($type == 'multi_file' ? '文件' : '图片');
                    $input->attr('placeholder', '批量上传' . $typeTitle);
                    $input->attr('data-upload', 'true');
                    $input->attr('data-browse', 'true');
                    $type == 'multi_image' && $input->attr('data-preview', 'true');
                    $input->attr('multiple', 'true');
                    $input->attr('i', 'cms:ui:file:btns');
                    break;
                case 'image':
                case 'file':
                    $typeTitle = ($type == 'file' ? '文件' : '图片');
                    $input->attr('data-upload', 'true');
                    $input->attr('data-browse', 'true');
                    $type == 'image' && $input->attr('data-preview', 'true');
                    $input->attr('i', 'cms:ui:file:btns');
                    break;
                case 'tpldir':
                case 'tplfile':
                    $input->attr('i', 'cms:ui:file:btns:browse');
                    $input->attr('data-dir', 'template');
                    $input->attr('data-mode', ($type == 'tpldir' ? 'dir' : 'file'));
                    break;
                case 'txt_prop':
                    // $form_group.=' input-append';
                    // if (self::$GATEWAY) {
                    //     $prop = PropWidget::btnGroup($name, $attr['id'], self::$APP['app']);
                    //     $prop .= PropWidget::btn('添加' . $field['label'], $name, self::$APP['app']);
                    // }
                    // $input .= $prop;
                    break;
                case 'prop':
                case 'multi_prop':
                    $input->attr('data-placeholder', '请选择' . $field['label'] . '...');
                    if (strpos($type, 'multi') !== false) {
                        // $input->attr('name', $attr['name'] . '[]');
                        $input->attr('multiple', 'true');
                        $input->attr('data-placeholder', '请选择' . $field['label'] . '(可多选)...');
                    }
                    $input->attr('i', 'cms:ui:prop');
                    $input->attr('value', $value);
                    $int = (strpos($field['field'], 'INT') !== false);
                    $input->attr('data-params-int', $int);
                    $input->attr('data-max', '5');
                    $input->attr('data-source', './prop/' . $name);
                    break;
                case 'date':
                case 'datetime':
                    $input->attr('i', 'cms:ui:datepicker');
                    $type == 'datetime' && $input->attr('data-enable-time', 'true');
                    break;
                case 'user_node':
                    if (self::$GATEWAY) {
                        $form_group = ' former_hide';
                        $input->attr('type', 'hidden');
                    }
                    break;
                case 'primary':
                case 'PRIMARY':
                case 'union':
                case 'hidden':
                    $form_group = ' former_hide';
                    $input->attr('type', 'hidden');
                    break;
                case 'relation':
                    $value or $value = Request::param($id);
                    break;
                case 'userid':
                    $form_group = ' former_hide';
                    $value or $value = self::$VALUES['userid'];
                    break;
                case 'nickname':
                case 'username':
                    $value or $value = self::$VALUES[$type];
                    $input->attr('data-source', '/user/selectpage');
                    $input->attr('data-key', $type == 'nickname' ? 'nickname' : 'account');
                    $input->attr('data-show', $type == 'nickname' ? 'nickname' : 'account');
                    // $input->attr('data-show', 'nickname');
                    // $input->attr('data-text-name', $attr['name']);
                    // $input->attr('sdf', self::name('userid'));
                    $input->attr('i', 'cms:ui:selectpage');
                    break;
                case 'area':
                    // $input->attr('i',$field['ui']);
                    // var_dump($field);
                    break;
                case 'kv':
                    if (self::$GATEWAY) {
                        AdmincpView::$ASSIGN = [
                            'kv_id'    => $id,
                            'kv_name'  => $attr['name'],
                            'kv_value' => $value,
                        ];
                        $input = AdmincpView::fetch('widget/kv', 'former');
                        // $input = str_replace(['{name}','{name_hash}','{value}'], [$attr['name'],$value], $input);
                    } else {
                    }
                    break;
                case 'tag':
                    $input->attr('onkeyup', "javascript:this.value=this.value.replace(/，/ig,',');");
                    break;
                case 'number':
                    break;
                case 'text':
                    break;
                case 'captcha':
                    if (self::$GATEWAY) {
                        return false;
                    } else {
                        $field['validate'] = ['required'];
                        $input->addClass('form-control captcha');
                        $input->attr('maxlength', '4');
                        $input->attr('type', 'text');
                        $help = PublicApp::captcha();
                    }
                    break;
                case 'editor':
                case 'markdown':
                    $attr['i'] = 'cms:ui:editor';
                    $attr['type'] = 'text/plain';
                    $attr['class'] = 'cms-editor';
                    $type == 'markdown' && $attr['data-markdown'] = true;
                    $input = self::element('textarea', $attr);
                    $input->html($value);
                    break;
                case 'multitext':
                    unset($attr['type']);
                    $input = self::element('textarea', $attr);
                    $input->css('height', '300px');
                    $input->text($value);
                    break;
                case 'textarea':
                    unset($attr['type']);
                    $input = self::element('textarea', $attr);
                    $input->css('height', '150px');
                    $input->text($value);
                    break;
                case 'switch':
                case 'radio':
                case 'checkbox':
                case 'radio_prop':
                case 'checkbox_prop':
                    if (in_array($type, ['checkbox', 'checkbox_prop'])) {
                        $attr['name'] = $attr['name'] . '[]';
                    }
                    $propClass = '';
                    if (in_array($type, ['radio_prop', 'checkbox_prop'])) {
                        $attr['type'] = str_replace('_prop', '', $type);
                        $int = (strpos($field['field'], 'INT') !== false);
                        $propArray = Prop::get($name, self::$APP['app'], $int);
                        $option = $propArray ? $propArray : [];
                        if (!isset($option[0])) {
                            // $option[0] = lang("Default");
                            $option[0] = Lang::get('Default') . $field['label'];
                        }
                        $field['option'] = $option;
                        $propClass = 'js-prop';
                    }

                    $input->attr('value', $value);
                    if ($type == 'switch') {
                        $attr['type'] = 'checkbox';
                        $attr['i'] = 'cms:ui:switch';
                        $input = self::element('input', $attr);
                    } else {
                        $field['option'] && $input = self::makeCheckbox($field['option'], $attr, $value);
                        $input = self::element('div')->html($input);
                        $input->addClass('form-control', true);
                        $input->attr('i', 'cms:ui:checked');
                        $input->attr('data-checked', $value);
                    }
                    break;
                case 'multi_node':
                case 'node':
                    unset($attr['type']);
                    $input->attr('data-placeholder', '请选择所属' . $field['label'] . '...');
                    if (strpos($type, 'multi') !== false) {
                        // $input->attr('name', $attr['name'] . '[]');
                        $input->attr('multiple', 'true');
                        $input->attr('data-placeholder', '请选择' . $field['label'] . '(可多选)...');
                        // $orig = self::element('input', array('type' => 'hidden', 'name' => $orig_name, 'value' => $value));
                    }
                    // $attr['class'].= " js-node";
                    // $attr['value'] = $value;
                    // $attr['data-node-appid'] = Node::$APPID;
                    $input->attr('i', 'cms:ui:node');
                    $input->attr('value', $value);
                    $input->attr('data-node-access', $value ? 'ce' : 'ca');
                    $input->attr('data-node-appid', Node::$APPID);
                    // $value === null or self::$vuedata[$attr['id']] = $value;
                    // $input = self::element('select', $attr);
                    // $input->val($value);
                    // $option = Node::set('APPID', Node::$APPID)->set('ACCESS', 'cm')->select();
                    // $input = $select->html($option); //$orig;
                    break;
                case 'multiple':
                case 'select':
                    // $attr['class'] = $field['class'];
                    $attr['data-placeholder'] = '请选择' . $field['label'] . '...';
                    $attr['i'] = 'cms:ui:bs-selectpicker';
                    if (strpos($type, 'multi') !== false) {
                        unset($attr['type']);
                        $attr['i'] = 'cms:ui:selectpicker';
                        $attr['multiple'] = 'true';
                        $attr['name'] = $attr['name'] . '[]';
                        $attr['data-placeholder'] = '请选择' . $field['label'] . '(可多选)...';
                        // $_input = self::element('input', array('type' => 'hidden', 'name' => $orig_name, 'value' => $value));
                    }
                    $attr['class'] = 'form-control';
                    $attr['value'] = $value;
                    $input = self::element('select', $attr);
                    $option = '';
                    if (self::$GATEWAY) {
                        $option .= '<option value=""></option>';
                    }
                    if ($field['option']) {
                        $option = self::makeOption($field['option'], $name, $value);
                        $input->html($option);
                    }
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
                    break;
            }
            if ($_type == 'hidden') {
                $form_group = ' former_hide';
                $input->attr('type', 'hidden');
            }
            if ($field['validate']) {
                list($rules, $msgs) = self::validate($field, 'rule', $value);
                // $input->attr('data-rule',  str_replace(['"','\\'], ['&quot;','\\\\'], json_encode($rules)));
                // $input->attr('data-msg', str_replace('"', '&quot;', json_encode($msgs)));

                foreach ($rules as $k => $r) {
                    $input->attr('data-rule-' . $k, $r);
                    $input->attr('data-msg-' . $k, $msgs[$k]);
                }
            }
            if ($field['labelafter']) {
                $input = '<div class="input-group">' . $input;
                $end = $field['labelafter'] . '</div>';
            }
            $script = $field['javascript'];
            $htmlArray = [
                'class'  => 'former_' . $type . ' ' . $form_group,
                'id'     => 'fg-' . random(6),
                'label'  => self::template($field['label'], 'label'),
                'widget' => self::template($input, 'widget'),
                'end'    => $end ? self::template($end, 'end') : '',
                'help'   => $help,
                'script' => $script ? self::template($script, 'script') : '',
            ];
            $div = self::template($htmlArray);
        }
        self::$html[$id] = $div;
        return true;
    }

    public static function template($html, $key = 'group')
    {
        $output = $html;
        $template = self::$template[$key];
        if ($template) {
            $output = self::templateClass($template);
            if (is_array($html)) {
                foreach ($html as $k => $value) {
                    is_null($value) && $value = '';
                    $output = str_replace('{{' . $k . '}}', $value, $output);
                }
            } else {
                is_null($html) && $html = '';
                $output = str_replace('{{content}}', $html, $output);
                is_null($html) && $output = '';
            }
        }
        return $output;
    }

    public static function setTemplateClass(array $class)
    {
        self::$template['class'] = array_merge(self::$template['class'], $class);
    }

    public static function templateClass($html)
    {
        if (self::$template['class']) {
            foreach (self::$template['class'] as $k => $value) {
                $search[] = '{{class.' . $k . '}}';
                $replace[] = $value;
            }
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

    public static function jsTest($id, $label, $msg, $pattern)
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
            return Request::post(self::$prefix);
        } else {
            return Request::post();
        }
    }

    public static function validate($field, $lang = 'js', $value = '')
    {
        if (empty($field['validate'])) {
            return;
        }

        $id = self::id($field['id']);
        $name = self::name($field['name']);
        $label = $field['label'];
        $type = $field['type'];
        $error = $field['error'];

        if ($lang == 'js') {
            $javascript = 'var ' . $id . ' = $("#' . $id . '"),' . $id . '_value = ' . $id . '.val(),' . $id . '_error="' . $error . '";';
            if ($type == 'editor') {
                $editor_id = Editor::getID($id, 'UE');
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
        $rule = [];
        $message = [];
        foreach ($field['validate'] as $key => $vd) {
            $code = null;
            switch ($vd) {
                case 'zipcode':
                    $msg = '邮政编码有误';
                    $pattern = "/^[1-9]{1}(\d+){5}$/";
                    break;
                case 'idcard':
                    $msg = '身份证有误';
                    $pattern = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
                    break;
                case 'telphone':
                    $msg = '固定电话号码有误';
                    $pattern = "/^(\(\d{3,4}\)|\d{3,4}-|\s)?\d{7,14}$/";
                    break;
                case 'mobphone':
                    $msg = '手机号码有误';
                    $pattern = "/^1[3-9]\d{9}$/";
                    break;
                case 'phone':
                    $msg = '电话或手机号码有误';
                    $pattern = "/(^(\(\d{3,4}\)|\d{3,4}-|\s)?\d{7,14}$)|(^1[3-9]\d{9}$)/";
                    break;
                case 'url':
                    $msg = '网址有误';
                    $pattern = "/^[a-zA-z]+:\/\/[^\s]*/";
                    break;
                case 'email':
                    $msg = '邮箱地址有误';
                    $pattern = "/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/";
                    break;
                case 'number':
                    $msg = '只能输入数字';
                    $pattern = "/^\d+(\.\d+)?$/";
                    break;
                case 'hanzi':
                    $msg = '只能输入汉字';
                    $pattern = "/^[\u4e00-\u9fa5]+$/";  // 使用 \u 表示 Unicode 字符
                    if ($lang == 'php') {
                        $pattern = "/^[\x{4e00}-\x{9fa5}]+$/u";  // 使用 \x{} 表示 Unicode 字符，并添加 /u 修饰符
                    }
                    break;
                case 'character':
                    $msg = '只能输入字母';
                    $pattern = '/^[A-Za-z]+$/';
                    break;
                case 'required':
                case 'empty':
                    $msg = $label . '不能为空!';
                    $vd = 'required';
                    $rule[$vd] = 'required';

                    if ($lang == 'php') {
                        empty($value) && iPHP::alert($msg);
                    } elseif ($lang == 'js') {
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
                    $min = $field['minmax'][0];
                    $max = $field['minmax'][1];
                    $msg_min = '您填写的' . $label . '小于' . $min . ',请重新填写!';
                    $msg_max = '您填写的' . $label . '大于' . $max . ',请重新填写!';
                    $rule[] = 'min:' . $min;
                    $rule[] = 'max:' . $max;
                    $message['min'] = $msg_min;
                    $message['max'] = $msg_max;
                    if ($lang == 'php') {
                        ($value < $min) && iPHP::alert($msg_min);
                        ($value > $max) && iPHP::alert($msg_max);
                    } elseif ($lang == 'js') {
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
                    $min = $field['count'][0];
                    $max = $field['count'][1];
                    $rule[] = 'minlength:' . $min;
                    $rule[] = 'maxlength:' . $max;
                    $msg_min = '您填写的' . $label . '小于' . $min . '字符,请重新填写!';
                    $msg_max = '您填写的' . $label . '大于' . $max . '字符,请重新填写!';
                    $message['minlength'] = $msg_min;
                    $message['maxlength'] = $msg_max;
                    if ($lang == 'php') {
                        (strlen($value) < $min) && iPHP::alert($msg_min);
                        (strlen($value) > $max) && iPHP::alert($msg_max);
                    } elseif ($lang == 'js') {
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
                    $field['defined'] && $code = stripcslashes($field['defined']);
                    break;
                default:
                    // code...
                    break;
            }
            if ($lang == 'php') {
                if ($pattern && $msg) {
                    if (is_array($value)) {
                        foreach ($value as $key => $subject) {
                            preg_match($pattern, $subject) or iPHP::alert($label . $msg);
                        }
                    } else {
                        preg_match($pattern, $value) or iPHP::alert($label . $msg);
                    }
                }
            } elseif ($lang == 'js') {
                if (empty($code)) {
                    $code = self::jsTest($id, $label, $msg, $pattern);
                }
                $javascript .= $code;
            } elseif ($lang == 'rule') {
                $pattern && $rule[$vd] = 'pattern:' . $pattern;
                $msg && $message[$vd] = $msg;
            }
        }
        if ($lang == 'js') {
            $javascript = preg_replace('/\s{3,}/is', '', $javascript);
            self::$validate[] = $javascript;
            $field['javascript'] && self::$script[] = self::script(stripcslashes($field['javascript']));
            return $javascript;
        }
        if ($lang == 'rule') {
            return [$rule, $message];
        }
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
                case 'area':
                    $value = str_replace(',', '/', $value);
                    break;
                case 'date':
                    $value = get_date($value, 'Y-m-d');
                    break;
                case 'datetime':
                    $value = get_date($value, 'Y-m-d H:i:s');
                    break;
                case 'json':
                    // $value = json_decode($value, true);
                    // $value = json_decode($value, true);
                    // is_array($value) && $value = implode(',',$value);
                    break;
                case 'multiple':
                case 'multi_node':
                case 'multi_prop':
                case 'checkbox':
                case 'checkbox_prop':
                    is_array($value) && $value = implode(',', $value);
                    // is_array($value) or $value = json_decode($value, true);
                    // empty($value) && $value = [];
                    break;
                default:
                    // code...
                    break;
            }
        }
        return $value;
    }

    //字段数据输出处理(后台 自定义表单)
    public static function fieldOutput($value, $fields, $vArray = null)
    {
        if (empty($value)) {
            return $value;
        }
        //字段数据类型
        $field = $fields['field'];

        //字段类型
        list($type, $_type) = explode(':', $fields['type']);

        self::fieldOut($value, $type);

        if (in_array($type, ['node', 'multi_node'])) {
            $variable = self::$variable[$fields['name']];
            $valArray = is_array($value) ? $value : explode(',', $value);
            $value = '';
            if ($valArray) {
                foreach ($valArray as $i => $val) {
                    $array = $variable[$val];
                    $value .= '<a href="' . APP_DOURL . '&cid=' . $val . '&' . $uri . '">' . $array->name . '</a>';
                }
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
        $field = $fields['field'] ? strtoupper($fields['field']) : '';
        //字段类型
        list($type, $_type) = explode(':', $fields['type']);

        //时间转换
        if (in_array($type, ['date', 'datetime'])) {
            $value = str2time($value);
            if ($_type == 'hidden') {
                $value = time();
            }
        }
        //数字转换
        if (in_array($field, ['BIGINT', 'INT', 'MEDIUMINT', 'SMALLINT', 'TINYINT'])) {
            $value = (int) $value;
            $field == 'TINYINT' && $max = 255;
            $field == 'SMALLINT' && $max = 65535;
            $field == 'MEDIUMINT' && $max = 8388607;
            $field == 'INT' && $max = 4294967296;
            $field == 'BIGINT' && $max = 9223372036854775807;
            if ($max && $value >= $max) {
                $alert = $fields['label'] . '超出最大允许值,(0-' . ($max - 1) . ')';
            }
        }
        if (in_array($field, ['DECIMAL'])) {
            list($length, $digit) = explode(',', $fields['len']);
            empty($digit) && $digit = 2;
            $value = sprintf("%01.{$digit}f", $value);
            $len = $length - $digit;
            $max = (float)(str_repeat('9', $len) . '.' . str_repeat('9', $digit));
            if ($value > $max) {
                $alert = $fields['label'] . '超出最大允许值,(0-' . $max . ')';
            }
        }
        //多选字段转换
        if (!empty($fields['multiple'])) {
            // is_array($value) && $value = implode(',', $value);
        }

        if (in_array($type, ['ip'])) {
            $value = Request::ip();
        } elseif (in_array($type, ['referer'])) {
            $value = $_SERVER['HTTP_REFERER'];
        } elseif ($type == 'area') {
            $value = str_replace('/', ',', $value);
        } elseif ($type == 'percentage') {
            $value = (int)$value;
            $max = 100;
            if ($value > $max) {
                $alert = $fields['label'] . '超出最大允许值,(0-' . $max . ')';
            }
        } elseif ($type == 'image') {
            $name = $fields['name'];
            if (Request::isUrl($value) && !isset($_POST[$name . '_http'])) {
                $value = FilesClient::remote($value);
            }
        } elseif ($type == 'captcha') {
            if (self::$GATEWAY) {
                return true;
            }

            if (empty($value)) {
                $alert = Lang::get('captcha:empty');
            }

            $captcha = Security::escapeStr($value);
            if (!Captcha::check($captcha)) {
                $alert = Lang::get('captcha:error');
            }
        } elseif ($type == 'editor') {
            $value = Security::htmlDecode($value);
            if (!self::$GATEWAY) {
                $value = preg_replace("/<a[^>].*?>(.*?)<\/a>/si", '\\1', $value);
                $value = Vendor::run('CleanHtml', [$value]);
            }
            if (self::$GATEWAY) {
                Request::post('dellink') && $value = preg_replace("/<a[^>].*?>(.*?)<\/a>/si", '\\1', $value);
                Request::post('noWatermark') && FilesMark::$enable = false;
                if (Request::post('remote')) {
                    $value = FilesPic::remote($value, true);
                    $value = FilesPic::remote($value, true);
                    $value = FilesPic::remote($value, true);
                }
            }
        } elseif (in_array($type, ['checkbox', 'multiple', 'checkbox_prop'])) {
            $value = is_array($value) ? implode(',', $value) : '';
        } elseif ($type == 'json') {
            $value = json_decode($value);
            $value = json_encode($value);
        } else {
            $value = Security::escapeStr($value);
        }

        if ($alert) {
            iPHP::alert($alert);
        }
        if ($error) {
            throw new sException($error);
        }
        return $value;
    }

    /**
     * 处理表单数据
     * @param  [type] $app [app数据]
     * @return Array     [description]
     */
    public static function postData($app, $post = null)
    {
        is_null($post) && $post = self::post();
        empty($post) && iPHP::alert('no post data');

        $content = [];
        $contentData = [];
        $fileData = [];
        $tagData = [];
        $nodeData = [];

        $fields = self::fields($app['fields']);
        foreach ($fields as $key => $field) {
            $value = isset($post[$key]) ? $post[$key] : '';
            //字段绑定的函数处理
            $field['func'] && $value = self::func($field['func'], $value);
            //字段数据处理
            $value = self::fieldIn($value, $field);
            //数据验证
            self::validate($field, 'php', $value);

            //找查MEDIUMTEXT字段
            if ($field['field'] && strtoupper($field['field']) == 'MEDIUMTEXT') {
                $contentData[$key] = $value;
                unset($content[$key]);
            } else {
                $content[$key] = $value;
            }
        }
        return compact('content', 'contentData');
    }

    /**
     * 表单数据处理
     * @param  [type] $func  [description]
     * @param  [type] $value [description]
     * @return void
     */
    public static function func($func, $value, $type = 'input')
    {
        return $value;
    }

    public static function display($fields = null)
    {
        self::$html = [];
        self::$validate = [];
        self::$script = [];
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
        $pieces = [];
        if (empty($GLOBALS['former.css'])) {
            $GLOBALS['former.css'] = true;
            $pieces[] = '<link rel="stylesheet" href="/assets/libs/former/former.css" type="text/css" />';
        }
        self::$html && $pieces[] = implode('', self::$html);
        // $pieces[] = '<script type="text/javascript">';
        // $pieces[] = '$(function(){';
        // if (self::$validate) {
        //     if ($id === null && defined('APP_FORMID')) {
        //         $id = '#' . APP_FORMID;
        //     }
        //     $js = file_get_contents(__DIR__ . '/assets/former.js');
        //     $validate = implode(';', self::$validate);
        //     $pieces[] = str_replace(
        //         array('$APP_FORMID', 'function validate() {};', ').submit('),
        //         array("'{$id}'", $validate, ").{$func}("),
        //         $js
        //     );
        // }
        self::$script && $pieces[] = implode(';', self::$script);
        // $pieces[] = '});';
        // $pieces[] = '</script>';
        // if (isset(self::$APP['apptype'])) {
        //     $pieces[] = '<hr />';
        //     $pieces[] = '<a class="btn btn-dark" href="' . ADMINCP_URL . '=apps&do=edit&id=' . self::$APP['id'] . '" target="_blank">增加自定义字段</a><hr />';
        //     $pieces[] = '<a href="https://www.icmsdev.com/docs/add-custom-fields.html" target="_blank">查看增加自定义字段教程</a>';
        // }
        return implode(PHP_EOL, $pieces);
    }
}
