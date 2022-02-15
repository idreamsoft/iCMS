var iFormer = {
    ui: {
        fbox: null
    },
    FieldType: {
        'number': ['BIGINT', 'INT', 'MEDIUMINT', 'SMALLINT', 'TINYINT']
    },
    editor_callback: {
        validate: function(e, p) {
            $(".v_selected").removeClass('v_selected');
            $("option:selected", "#iFormer-validate").each(function() {
                var value_el = $("#iFormer-validate-" + this.value);
                if (value_el.length > 0) {
                    value_el.removeClass('hide').addClass('v_selected');
                }
            });
            $("[id^='iFormer-validate-']").each(function(index, el) {
                if (!$(this).hasClass('v_selected')) {
                    $(this).addClass('hide');
                    $("input", this).val('');
                }
            });
        }
    },
    sort_option: function(e, v) {
        var option = e.find('option[value="' + v + '"]').clone();
        option.attr('selected', 'selected');
        return option;
    },
    sort_value: function(a, e, p) {
        var name = a.id.replace('iFormer-', ''),
            select = $("#sort-" + name);
        if (p['selected']) {
            select.append(iFormer.sort_option($(a), p['selected']));
        }
        if (p['deselected']) {
            select.find('option[value="' + p['deselected'] + '"]').remove();
        }
        if (typeof(iFormer.editor_callback[name]) === 'function') {
            iFormer.editor_callback[name](e, p);
        }
    },
    sort_select: function($fbox) {
        $('select[multiple="multiple"]', $fbox).each(function(index, select) {
            var name = this.id.replace('iFormer-', '');
            $("#sort-" + name, $fbox).html('');
            $(this).on('change', function(e, p) {
                iFormer.sort_value(this, e, p);
            });
        });
    },
    widget: function(t) {
        var element = {
            input: '<input/>',
            textarea: '<textarea/>',
            btn: function($text, $type) {
                if ($type) {
                    var btn = $('<button type="button" class="btn btn-secondary"><i class="fa fa-' + $type + '"></i> ' + $text + '</button>');
                } else {
                    var btn = $('<button type="button" class="btn btn-secondary dropdown-toggle"><span class="caret"></span> ' + $text + '</button>');
                }
                return btn;
            }
        };
        if (typeof(element[t]) === "function") {
            return element[t];
        }
        if (element[t]) {
            return $(element[t]);
        } else {
            return $('<' + t + '/>');
        }
    },
    /**
     * 生成HTML
     * @param  {[type]} helper [表单]
     * @param  {[type]} obj    [生成表单数组]
     * @param  {[type]} data   [fields字符串]
     * @param  {[type]} origin [原字段名]
     * @param  {[type]} readonly [只读]
     * @return {[type]}        [description]
     */
    render: function(helper, obj, data, origin, readonly) {
        var me = this;
        var $container = this.widget('div').addClass("form-group row");

        if (obj['type'] == 'br') {
            data = JSON.stringify(obj);
            $container.addClass('pagebreak mb-3');
            var doc = $(document);
            doc.on("dblclick", ".pagebreak", function(event) {
                $(this).remove()
            });
        } else {
            var $div = this.widget('div').addClass('col-sm-2 col-xl-1 col-form-label');
            var $label = this.widget('label');
            var $help = this.widget('small').addClass('form-text text-muted');
            // $comment = this.widget('span').addClass('help-inline');
            var $input = this.widget('input');
            var $input_type = 'text';
            // elem_class = obj['class'];
            var obj_type = obj['type'],
                type_addons;

            if (obj_type && obj_type.indexOf(':') != "-1") {
                var typeArray = obj_type.split(':');
                obj_type = typeArray[0];
                type_addons = typeArray[1];
            }
            switch (obj_type) {
                case 'tpldir':
                case 'tplfile':
                    var inputAfter = function() {
                        var $btnFun = iFormer.widget('btn');
                        return iFormer.widget('div').addClass('input-group-append').append($btnFun('选择', 'search'));
                    }
                    break;
                case 'multi_image':
                case 'multi_file':
                    // $elem = this.widget('textarea');
                    // $input_type = null;
                case 'text_prop':
                case 'file':
                case 'image':
                    var eText = {
                        prop: '选择属性',
                        image: '图片上传',
                        multi_image: '多图上传',
                        file: '文件上传',
                        multi_file: '批量上传',
                    }
                    if (obj_type != 'prop') {
                        obj['class'] = obj['class'] || "";
                    }
                    var inputAfter = function() {
                        var $btnFun = iFormer.widget('btn');
                        return iFormer.widget('div').addClass('input-group-append').append($btnFun(eText[obj_type]));
                    }

                    break;
                case 'seccode':
                    $input.addClass('captcha').attr('maxlength', "4");
                    obj['validate'] = ["empty"];
                    var inputAfter = function() {
                        var span = iFormer.widget('span').addClass('input-group-text').append('<img src="' + iCMS.$CONFIG.API + '?do=captcha" alt="验证码" class="captcha-img r3">' + '<a href="javascript:;" class="captcha-text">换一张</a>');
                        return iFormer.widget('div').addClass('input-group-append').append(span);
                    }
                    break;
                case 'multitext':
                    // obj['class'] = obj['class']||"";
                case 'textarea':
                    $input = this.widget('textarea');
                    // obj['class'] = obj['class']||"";
                    $input.css('height', '120px');
                    $input_type = null;
                    break;
                case 'markdown':
                case 'editor':
                    $input = this.widget('textarea').hide();
                    var inputAfter = function() {
                        var $img = iFormer.widget('img').prop('src', './app/former/assets/img/' + obj_type + '.png');
                        return $img;
                    }
                    $input_type = null;
                    break;

                case 'PRIMARY':
                case 'user_node':
                case 'userid':
                case 'union':
                case 'hidden':
                    var inputAfter = function() {
                        var $span;
                        if (obj_type == "PRIMARY") {
                            $span = iFormer.widget('span').addClass('badge badge-danger').text('主键 自增ID');
                        }
                        return me.hidden($input, $span);
                    }
                case 'text':
                    if (obj['len'] == "5120") { // obj['class'] = obj['class']||'';
                    }
                    break;
                case 'switch':
                case 'radio':
                case 'checkbox':
                case 'radio_prop':
                case 'checkbox_prop':
                    obj['class'] = obj['class'] || obj_type;
                    $input_type = obj_type;
                    if (obj_type == 'switch' || obj_type == 'radio_prop') {
                        obj['class'] = 'radio';
                        $input_type = 'radio';
                    }
                    if (obj_type == 'checkbox' || obj_type == 'checkbox_prop') {
                        obj['multiple'] = true;
                        obj['class'] = 'checkbox';
                        $input_type = 'checkbox';
                    }
                    if (obj_type == 'radio_prop' || obj_type == 'checkbox_prop') {
                        obj['option'] = '默认属性=0;'
                    }
                    $input.hide();
                    //改变$div内容
                    var inputAfter = function() {
                        var $span = iFormer.widget('span');
                        var field_option = function() {
                            var optionText = obj['option'].replace(/(\n)+|(\r\n)+/g, "");
                            optionArray = optionText.split(";");
                            $.each(optionArray, function(index, val) {
                                if (val) {
                                    var ov = val.split("=");
                                    var aa = me.widget('input').attr('type', $input_type);
                                    $span.append(ov[0], aa, ' ');
                                }
                            });
                        };
                        if (obj['option']) {
                            field_option();
                        }
                        $span.append($input);
                        return iFormer.widget('div').addClass('form-control').append($span);
                    }
                    break;
                case 'multi_prop':
                case 'multi_node':
                case 'multiple':
                case 'prop':
                case 'node':
                case 'select':
                    $input = this.widget('select');
                    var inputAfter = function() {
                        var eText = {
                            prop: '属性',
                            multi_prop: '属性',
                            node: '栏目',
                            multi_node: '栏目',
                        }
                        if (eText[obj_type]) {
                            var $span = iFormer.widget('span').addClass('badge badge-info badge-tip').text(eText[obj_type]);
                            $('.input-group-text:first', $div).append($span);
                        }
                    }
                    if (obj_type.indexOf("multi") != '-1') {
                        obj['multiple'] = true;
                        $input.attr('multiple', true);
                    }
                    // obj['class'] = obj['class']||'';
                    var field_option = function() {
                        // console.log(obj['option']);
                        var optionText = obj['option'].replace(/(\n)+|(\r\n)+/g, "");
                        optionArray = optionText.split(";");
                        $.each(optionArray, function(index, val) {
                            if (val) {
                                var ov = val.split("=");
                                $input.append('<option value="' + ov[1] + '">' + ov[0] + '</option>');
                            }
                        });
                    };
                    if (obj['option']) {
                        field_option();
                    }
                    // console.log(obj);
                    $input_type = null;
                    break;
                case 'number':
                    if (obj['len'] == "1") { // obj['class'] = obj['class']||'';
                    }
                    if (obj['len'] == "10") { // obj['class'] = obj['class']||'';
                    }
                    break;
                case 'currency':
                case 'percentage':
                    //追加$div内容
                    var inputAfter = function() {
                        var $span = iFormer.widget('span').addClass('input-group-text').append(obj['label-after']);
                        return iFormer.widget('div').addClass('input-group-append').append($span);
                    }
                    break;
                case 'decimal':
                    break;
            }
            if (type_addons == 'hidden') {
                var inputAfter = function() {
                    return me.hidden($input, $div);
                }
            }
            obj['class'] = obj['class'] || 'form-control';

            //整数类型 默认无符号
            if ($.inArray(obj['field'], iFormer.FieldType['number']) > 0) {
                if (typeof(obj['unsigned']) == "undefined") {
                    obj['unsigned'] = '1'
                }
            }
            /**
             * 生成器字段样式展现
             */
            $input.attr({
                'id': '_field_' + obj['id'],
                'name': '_field_' + obj['name'],
                // 'class': obj['class']+' form-control',
                'class': obj['class'],
                'value': obj['default'] || '',
            });

            if ($input_type) {
                $input.attr({
                    'type': $input_type
                });
            }

            // $label.text(obj['label']);
            $help.text(obj['help']);
            // $comment.text(obj['comment']);

            if (typeof(_div) === "function") {
                _div();
            } else {
                $div.append(obj['label']);
                // $div.after($input);
            }
            var $el2, $actbtn;

            if (readonly) {
                $container.addClass('iFormer-base-field');
            } else {
                var $actbtn = iFormer.action_btn($container, $input);
            }
            var $div2 = iFormer.widget('div').addClass('col-sm-10 col-xl-8');
            if (typeof inputAfter === "function") {
                $el2 = inputAfter($input);
                var $group = iFormer.widget('div').addClass('input-group');
                $group.append($input, $el2);
                $div2.append($group);
            } else {
                $div2.append($input, $el2);
            }
            $div2.append($help);

            $container.append($div, $div2, $actbtn);
        }

        if (origin) {
            var $origin = this.widget('input').prop({
                'type': 'hidden',
                'name': 'origin[' + origin + ']',
                'value': obj['id']
            });
            $container.append($origin);
        }
        // var $tab = this.widget('input').prop({
        //     'type': 'hidden',
        //     'name': 'tabs[' + tab + '][]',
        //     'value': obj['id']
        // });
        // $container.append($tab);

        data = data || JSON.stringify(obj);

        iFormer.fields(data, $container);

        // $(':checkbox,:radio',$container).uniform();

        // $container.dblclick(function(event) {
        //     iFormer.edit($container);
        // });

        return $container;
    },
    /**
     * 字段数据
     * @param  {[type]} obj  [数组]
     * @param  {[type]} data [字符串]
     * @param  {[type]} $container
     * @return {[type]}      [description]
     */
    fields: function(data, $container) {
        var $fields = this.widget('input').prop({
            'type': 'hidden',
            'name': 'fields[]'
        });
        // if(data){
        $fields.val(data);
        // }else{
        //     $fields.val(this.url_encode(obj));
        // }
        $container.append($fields);
    },
    /**
     * 字段 编辑/删除 按钮
     * @param  {[type]} $container [description]
     * @param  {[type]} $div [description]
     * @return {[type]}            [description]
     */
    action_btn: function($container) {
        var $action = this.widget('div').addClass("action_btn btn-group btn-group=sm"),
            $edit = this.widget('button'),
            $del = this.widget('button');

        $edit.attr('type', 'button').addClass('btn btn-alt-primary act-edit').html('<i class="fa fa-fw fa-pencil-alt"></i>').click(function(e) {
            e.preventDefault();
            iFormer.edit($container);
        });
        $del.attr('type', 'button').addClass('btn btn-alt-primary act-del').html('<i class="fa fa-trash-alt"></i>').click(function(e) {
            e.preventDefault();
            $container.remove();
        });

        $action.append($edit, $del);

        return $action;
    },
    hidden: function($input, $span) {
        var $div = iFormer.widget('div');
        var info = iFormer.widget('span').addClass('badge badge-info').text('隐藏字段');
        if ($span) {
            $div.append($span);
        }
        $div.append(info);
        $input.hide();
        return $div;
    },
    callback: function(func, ret, param) {
        if (typeof(func) === "function") {
            func(ret, param);
        } else {
            var msg = ret;
            if (typeof(ret) === "object") {
                msg = ret.message || 'error';
            }
            var UI = require("ui");
            UI.alert(msg);
        }
    },
    /**
     * 重置表单
     * @param  {[type]} a [description]
     * @return {[type]}   [description]
     */
    freset: function(a) {
        //form嵌套下出错
        document.getElementById("iFormer-field-form").reset();
        $('[data-toggle="chosen2"]', $(a)).chosen("destroy");
        $("select", $(a)).find('option').removeAttr('selected');
        // $.uniform.restore('.uniform');
    },
    /**
     * 编辑字段
     * @param  {[type]} $container [description]
     * @return {[type]}            [description]
     */
    edit: function($container) {
        // $container.dblclick(function(event) {
        // window.event.preventDefault();
        var me = $(this),
            data = $("[name='fields[]']", $container).val(),
            origin = $("[name^='origin']", $container).val(),
            obj = JSON.parse(data);
        // console.log(obj);
        iFormer.edit_dialog(obj, function(param, qt) {
            var render = iFormer.render($container, param, qt, origin);
            $container.replaceWith(render);
        });
        // });
    },

    /**
     * 字段编辑框
     * @param  {[type]}   obj      [description]
     * @param  {Function} callback [description]
     * @return {[type]}            [description]
     */
    edit_dialog: function(obj, callback) {
        var me = this,
            _id = obj['id'];
        var fbox = document.getElementById("iFormer-field-editor");
        var $fbox = $(fbox);
        $('[data-toggle="tabs"]', $fbox).removeClass('js-tabs-enabled');
        One.helpers(['core-bootstrap-tabs']);
        $('[data-toggle="chosen2"]', $fbox).chosen($chosen);

        iFormer.sort_select($fbox);

        for (var name in obj) {

            // if(i=='func'){
            //     continue;
            // }
            if (name == 'javascript') {
                obj[name] = obj[name].replace(/\\(['|"])/g, "$1");
            }
            var ifn = $("#iFormer-" + name, $fbox);

            // console.log(ifn.is('select'));
            // console.log(i,obj[name],typeof(obj[name]));
            // if(typeof(obj[name])==='object'){
            if (ifn.data('toggle') == 'chosen2') {
                // ifn.trigger("chosen:updated");
                // 多选排序
                // console.log(ifn);
                if (ifn.attr('multiple')) {
                    ifn.setSelectionOrder($.unique(obj[name]), true);
                } else {
                    if (name == 'tabs') {
                        console.log(obj[name][0]);
                        ifn.val(obj[name][0]);
                    } else {
                        ifn.val(obj[name]);
                    }
                    ifn.trigger("chosen:updated");
                }
                if ($("#sort-" + name, $fbox).length > 0) {
                    $.each(obj[name], function(ii, v) {
                        $("#sort-" + name, $fbox).append(iFormer.sort_option(ifn, v));
                    });
                }
            } else {
                if (ifn.length > 0) {
                    ifn.val(obj[name]);
                }
            }
        }

        //整数类型 显示unsigned
        if ($.inArray(obj['field'], iFormer.FieldType['number']) > 0) {
            $('.unsigned-wrap', $fbox).show();
            $('[name="unsigned"][value="' + obj['unsigned'] + '"]', $fbox).prop("checked", true);
            // $.uniform.update('[name="unsigned"]');
        }

        if (obj['validate']) {
            $.each(obj['validate'], function(i, v) {
                if ($("#iFormer-validate-" + v).length > 0) {
                    $("#iFormer-validate-" + v).removeClass("hide");
                    if ($.isArray(obj[v])) {
                        $.each(obj[v], function(index, val) {
                            $('[name="' + v + '[' + index + ']"]').val(val);
                        });
                    } else {
                        if (v == 'defined') {
                            obj[v] = obj[v].replace(/\\(['|"])/g, "$1");
                        }
                        $('[name="' + v + '"]').val(obj[v]);
                    }

                }
            });
        }

        $("#iFormer-label-after-wrap", $fbox).hide();

        if (obj['label-after']) {
            $("#iFormer-label-after-wrap", $fbox).show();
        }
        if (obj['type'] == 'radio' || obj['type'] == 'checkbox' || obj['type'] == 'select' || obj['type'] == 'multiple') {
            $("#iFormer-option-wrap", $fbox).show();
            $("[name='option']").removeAttr('disabled');
        } else {
            $("#iFormer-option-wrap", $fbox).hide();
            $("[name='option']").attr("disabled", true);
        }

        return iCMS.ui.dialog({
            id: 'apps-field-dialog',
            className: 'iCMS-UI-dialog apps-field-dialog',
            title: 'iCMS - 表单字段设置',
            content: fbox,
            okValue: '确定',
            ok: function() {
                //更新字段展现
                var data = $.extend(obj, {
                    'label': $("#iFormer-label", $fbox).val(),
                    'tabs': [$("#iFormer-tabs", $fbox).val(), $("#iFormer-tabs", $fbox).find("option:selected").text()],
                    'type': $("#iFormer-type", $fbox).val(),
                    'name': $("#iFormer-name", $fbox).val(),
                    'class': $("#iFormer-class", $fbox).val(),
                    'comment': $("#iFormer-comment", $fbox).val(),
                    'option': $("#iFormer-option", $fbox).val(),
                    'help': $("#iFormer-help", $fbox).val(),
                    'label-after': $("#iFormer-label-after", $fbox).val(),
                    'default': $("#iFormer-default", $fbox).val()
                });

                if (!data.label) {
                    iCMS.ui.alert("请填写字段名称!");
                    return false;
                }
                if (!data.name) {
                    iCMS.ui.alert("请填写字段名!");
                    return false;
                }
                if (data['id'] != data['name']) {
                    data['id'] = data['name'];
                    $("#iFormer-id", $fbox).val(data['id']);
                }
                var $apptype = $('[name="apptype"]').val();
                if ($apptype == "2") {
                    var dname = $('[name="_field_' + data.name + '"]', '.iFormer-layout').not('[name="_field_' + _id + '"]');
                } else {
                    var dname = $('td[field="' + data.name + '"]', '.app-table-list').not('td[field="' + _id + '"]');
                }

                if (dname.length) {
                    iCMS.ui.alert("该字段名已经存在,请重新填写");
                    return false;
                }

                $('td[field="' + _id + '"]').attr('field', data.name).text(data.name);

                //更新 fields[]
                var param = $("form", $fbox).serializeArray();
                var fields = data;

                $.each(param, function(index, val) {
                    // console.log(index, val);
                    var name = val['name'];
                    if (name.indexOf('[') != -1) {
                        name = name.replace(/\[\d+\]/g, "[]");
                        name = name.replace('[]', '');
                        if (!fields[name]) {
                            fields[name] = [];
                        }
                        if (val['value'] != '') {
                            fields[name].push(val['value']);
                        }
                    } else {
                        fields[name] = val['value'];
                    }
                });
                if (fields['validate']) {
                    fields['validate'] = $.unique(fields['validate']);
                }
                if (fields['func']) {
                    //fields['func'] = $.unique(fields['func']);
                }

                // fields['tabs'] = data['tabs'];

                callback(data, JSON.stringify(fields));

                me.freset(fbox);
                return true;
            },
            cancelValue: '取消',
            cancel: function() {
                me.freset(fbox);
                return true;
            }
        });
    }
};