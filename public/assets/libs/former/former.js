
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as anonymous module.
        define(['jquery', 'jquery-ui'], factory);
    } else if (typeof exports === 'object') {
        // Node / CommonJS
        factory(require('jquery'), require('jquery-ui'));
    } else {
        // Browser globals.
        factory(jQuery);
    }
})(function ($) {

    var iFormer = {
        init: function () {
            var self = this;
            self.layout();
            self.design();
            self.run();
        },
        layout: function (el) {
            var el = el || ".former-layout";
            $(el).sortable({
                placeholder: "ui-state-highlight",
                cancel: ".clearfloat",
                delay: 300,
                sort: function (event, ui) {
                    var target = $(event.target);
                    $(".clearfloat", target).remove();
                    target.append('<div class="clearfloat mt-4"></div>');
                },
                receive: function (event, ui) {
                    var helper = ui.helper,
                        tag = helper.attr("tag"),
                        field = helper.attr("field"),
                        type = helper.attr("type"),
                        label = helper.attr("label"),
                        len = helper.attr("len"),
                        obj = helper.getDatas('set'),
                        id = iCMS.utils.random(6, true);
                    var target = $(event.target);
                    // console.log(helper);
                    // console.log(target);
                    var tabs = target.data("tabs");
                    // console.log(tabs);
                    label = (label || "表单") + id;
                    if (type == "relation:id") {
                        var spData = $("#rootid").selectPageSelectedData();
                        var root = spData[0];
                        // console.log(spData,root);
                        if (root["app"]) {
                            id = root["app"] + "_id";
                            label = root["title"] + "ID";
                        } else {
                            iCMS.notify.warning('该父级应用无法关联');
                        }
                    }
                    var params = $.extend({
                        id, label, field, type, len, tabs,
                        name: id,
                        default: ""
                    }, obj);
                    // console.log(params);
                    var html = iFormer.render(helper, params);
                    helper.replaceWith(html);
                },
            }).disableSelection();
        },

        design: function () {
            $("[i='layout'],[i='field']", ".former-design")
                .draggable({
                    placeholder: "ui-state-highlight",
                    connectToSortable: ".former-layout",
                    helper: "clone",
                    revert: "invalid",
                    start: function (event, ui) {
                        if ($('.nav-item', "#former-tabs").length < 2) {
                            iCMS.ui.alert("请先添加一个表单卡!");
                            return false;
                        }
                    }
                })
                .disableSelection();
            var doc = $(document);
            doc.on("click", "button.close", function (e) {
                e.preventDefault();
                var item = $(this).parent().parent();
                var paneId = $("a", item).attr("href");
                item.remove();
                $(paneId).remove();
            });
            doc.on("click", "button.add_tabs", function (event) {
                event.preventDefault();
                iCMS.ui.dialog({
                    // follow: this,
                    // width: "150",
                    height: "auto",
                    content: document.getElementById("mktabs-box"),
                    title: "创建新表单卡",
                    yes: function (index, layero) {
                        var $name = $("#newtabname").val(),
                            $id = $("#newtabid").val(),
                            $icon = $("#newtabicon").val() || "",
                            d = this;
                        if ($name == "") {
                            iCMS.ui.alert("请输入表单卡名称!");
                            $("#newtabname").focus();
                            return false;
                        }
                        if ($id == "") {
                            $id = iCMS.utils.random(6, true);
                        }
                        var formerId = "former-layout-" + $id;
                        console.log($("#" + formerId), $("#" + formerId).length);
                        if ($("#" + formerId).length > 0) {
                            iCMS.ui.alert("表单卡ID已经存在!请重新输入！");
                            return false;
                        }
                        var wrapId = "former-wrap-" + $id;
                        var block = $("#former-block");
                        var tabs = $("#former-tabs").removeClass("js-tabs-enabled");
                        var nav_last = $(".block-options", tabs).parent();
                        var tab_content = $(".tab-content", block);
                        var tabsText = 'data-tabs=\'["' + $id + '","' + $name + '","' + $icon + '"]\'';
                        var item = '<li class="nav-item ' + formerId + '"' + tabsText + '><a class="nav-link active" href="#' + formerId + '"><i class="' + $icon + '"></i> ' + $name + ' <button type="button" class="btn close"><span>×</span></button></a></li>';
                        var pane = '<div id="' + formerId + '" class="tab-pane active"><ul ' + tabsText + ' id="' + wrapId + '" class="former-layout"><div class="clearfloat m-4"></div></ul></div>';
                        $(".nav-link", tabs).removeClass("active");
                        $(".tab-pane", tab_content).removeClass("active");
                        nav_last.before(item);
                        tab_content.append(pane);
                        iFormer.layout("#" + wrapId);
                        layer.close(index);
                    },
                });
            });
        },
        run: function () {
            var self = this;
            var json = $("#formerData").text().trim();
            if (json) {
                var collection = JSON.parse(json);
                self.edit_event();

                $.each(collection, function (key, value) {
                    var layoutId = '#former-wrap-' + value[0];
                    self.render($('div'), value[1], value[1]['id'], value[2]).appendTo(layoutId)
                });
            }
        },

        TypeMap: {
            'number': ['BIGINT', 'INT', 'MEDIUMINT', 'SMALLINT', 'TINYINT'],
            'option': ['radio', 'checkbox', 'select', 'multiple'],
        },
        widget: function (t) {
            var element = {
                input: '<input/>',
                textarea: '<textarea/>',
                btn: function ($text, $type) {
                    if ($type) {
                        var btn = $('<button type="button" class="btn btn-secondary"><i class="fa fa-' + $type + '"></i> ' + $text + '</button>');
                    } else {
                        var btn = $('<button type="button" class="btn btn-secondary dropdown-toggle"><span class="caret"></span> ' + $text + '</button>');
                    }
                    return btn;
                }
            };
            if (typeof (element[t]) === "function") {
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
         * @param  {[type]} origin [原字段名]
         * @param  {[type]} readonly [只读]
         * @return {[type]}        [description]
         */
        render: function (helper, obj, origin, readonly) {
            var me = this;
            var $container = this.widget('div').addClass("form-group row");

            if (obj['type'] == 'br') {
                var data = JSON.stringify(obj);
                $container.addClass('pagebreak mb-3');
                var doc = $(document);
                doc.on("dblclick", ".pagebreak", function (event) {
                    $(this).remove()
                });
            } else {
                var $div = this.widget('div').addClass('col-sm-2 col-form-label text-sm-end');
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
                        var inputAfter = function () {
                            var $btnFun = iFormer.widget('btn');
                            return iFormer.widget('div').addClass('input-group-text').append($btnFun('选择', 'search'));
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
                        var inputAfter = function () {
                            var $btnFun = iFormer.widget('btn');
                            return iFormer.widget('div').addClass('input-group-text').append($btnFun(eText[obj_type]));
                        }

                        break;
                    case 'seccode':
                        $input.addClass('captcha').attr('maxlength', "4");
                        obj['validate'] = ["empty"];
                        var inputAfter = function () {
                            var span = iFormer.widget('span').addClass('input-group-text').append('<img src="' + iCMS.$CONFIG.API + '?do=captcha" alt="验证码" class="captcha-img r3">' + '<a href="javascript:;" class="captcha-text">换一张</a>');
                            return iFormer.widget('div').addClass('input-group-text').append(span);
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
                        var inputAfter = function () {
                            var $img = iFormer.widget('img').prop('src', '/assets/libs/former/img/' + obj_type + '.png');
                            return $img;
                        }
                        $input_type = null;
                        break;

                    case 'PRIMARY':
                    case 'user_node':
                    case 'userid':
                    case 'union':
                    case 'hidden':
                        var inputAfter = function () {
                            var $span;
                            if (obj_type == "PRIMARY") {
                                $span = iFormer.widget('span').addClass('badge bg-danger').text('主键 自增ID');
                            }
                            return me.hidden($input, $span);
                        }
                    case 'url':
                    case 'mail':
                    case 'text':
                    case 'text_row':
                    case 'text_rows':
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
                        var inputAfter = function () {
                            var $span = iFormer.widget('span');
                            var field_option = function () {
                                var optionText = obj['option'].replace(/(\n)+|(\r\n)+/g, "");
                                optionArray = optionText.split(";");
                                $.each(optionArray, function (index, val) {
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
                        var inputAfter = function () {
                            var eText = {
                                prop: '属性',
                                multi_prop: '属性',
                                node: '栏目',
                                multi_node: '栏目',
                            }
                            if (eText[obj_type]) {
                                var $span = iFormer.widget('span').addClass('badge bg-info bg-tip').text(eText[obj_type]);
                                $('.input-group-text:first', $div).append($span);
                            }
                        }
                        if (obj_type.indexOf("multi") != '-1') {
                            obj['multiple'] = true;
                            $input.attr('multiple', true);
                        }
                        // obj['class'] = obj['class']||'';
                        var field_option = function () {
                            // console.log(obj['option']);
                            var optionText = obj['option'].replace(/(\n)+|(\r\n)+/g, "");
                            optionArray = optionText.split(";");
                            $.each(optionArray, function (index, val) {
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
                        var inputAfter = function () {
                            return iFormer.widget('div').addClass('input-group-text').append(obj['labelafter']);
                        }
                        break;
                    case 'decimal':
                        break;
                }
                if (type_addons == 'hidden') {
                    var inputAfter = function () {
                        return me.hidden($input, $div);
                    }
                }
                obj['class'] = obj['class'] || 'form-control';

                //整数类型 默认无符号
                if ($.inArray(obj['field'], iFormer.TypeMap['number']) > -1) {
                    if (typeof (obj['unsigned']) == "undefined") {
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

                if (typeof (_div) === "function") {
                    _div();
                } else {
                    $div.append(obj['label']);
                    // $div.after($input);
                }
                var $el2, $actbtn;

                if (readonly) {
                    $container.addClass('former-base-field');
                } else {
                    var $actbtn = iFormer.action_btn($container, $input);
                }
                var $div2 = iFormer.widget('div').addClass('col-sm-8');
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

            var data = JSON.stringify(obj);

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
        fields: function (data, $container) {
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
        action_btn: function ($container) {
            var $action = this.widget('div').addClass("col-sm-1 action_btn btn-group btn-group-sm"),
                $edit = this.widget('button'),
                $del = this.widget('button');

            $edit.attr('type', 'button')
                .addClass('btn btn-alt-primary act-edit')
                .html('<i class="fa fa-fw fa-pencil-alt"></i>')
                .on("click", function (e) {
                    e.preventDefault();
                    iFormer.edit($container);
                });
            $del.attr('type', 'button')
                .addClass('btn btn-alt-primary act-del')
                .html('<i class="fa fa-trash-alt"></i>')
                .on("click", function (e) {
                    e.preventDefault();
                    $container.remove();
                });

            $action.append($edit, $del);

            return $action;
        },
        hidden: function ($input, $span) {
            var $div = iFormer.widget('div');
            var info = iFormer.widget('span').addClass('badge bg-info').text('隐藏字段');
            if ($span) {
                $div.append($span);
            }
            $div.append(info);
            $input.hide();
            return $div;
        },
        callback: function (func, ret, param) {
            if (typeof (func) === "function") {
                func(ret, param);
            } else {
                var msg = ret;
                if (typeof (ret) === "object") {
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
        freset: function () {
            var $form = $("#former-field-form");
            $form[0].reset();
            $form.find('select').val(null).trigger("change");
            $form.find('input:not(:radio)').val(null).trigger("change");
        },
        /**
         * 编辑字段
         * @param  {[type]} $container [description]
         * @return {[type]}            [description]
         */
        edit: function ($container) {
            // $container.dblclick(function(event) {
            // window.event.preventDefault();
            var me = $(this),
                data = $("[name='fields[]']", $container).val(),
                origin = $("[name^='origin']", $container).val(),
                obj = JSON.parse(data);
            iFormer.edit_dialog(obj, function (param) {
                var render = iFormer.render($container, param, origin);
                if (obj['tabs'][0] == param['tabs'][0]) {
                    $container.replaceWith(render);
                } else {
                    var layoutId = '#former-wrap-' + param['tabs'][0];
                    render.appendTo(layoutId);
                    $container.remove();
                }
            });
            // });
        },
        setValue: function (ipt, val) {
            if (ipt.length > 0) {
                ipt.data('value', val);
                if (ipt.attr("type") == 'radio') {
                    ipt.filter('[value="' + val + '"]').prop("checked", true);
                } else if (ipt.attr("type") == 'checkbox') {
                    ipt.prop('checked', val == 1 ? 1 : 0);
                } else {
                    if (isArray(val)) {
                        $.each(val, function (i, v) {
                            $('option[value="' + v + '"]', ipt).attr("selected", true);
                        });
                    }
                    ipt.val(val);
                    if (ipt.hasClass('selectpicker')) {
                        ipt.selectpicker('val', val);
                    }
                }
                // ipt.trigger("change");
            }
        },
        edit_event: function () {
            var $form = $("#former-field-form");
            var ipt = $("[name='validate[]']", $form);
            ipt.on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
                var target = $(e.target);
                // console.log({clickedIndex, isSelected});
                if (clickedIndex !== null) {
                    var option = target.find("option").eq(clickedIndex);
                    if (isSelected) {
                        option.attr("selected", "selected");
                    } else {
                        option.removeAttr("selected");
                    }
                    target.append(option);
                }
            }).on('change', function (e) {
                e.preventDefault();
                var values = $(this).selectpicker('val');
                var texts = [];
                var target = $(e.target);
                target.find("option:selected").each(function (index, element) {
                    texts.push($(this).text());
                });
                if (texts.length > 0) {
                    texts = $.unique(texts);
                    var pp = target.parent();
                    $(".filter-option-inner-inner", pp).text(texts.join(','));
                }

                $("[validate]", $form).addClass("hide");
                $("[validate]", $form).find("[name]").attr("disabled", true);;
                $.each(values, function (i, v) {
                    var vipt = $("[validate='" + v + "']");
                    if (vipt.length > 0) {
                        vipt.removeClass("hide");
                        vipt.find("[name]").removeAttr('disabled');
                        var vdata = vipt.data('value');
                        if (isObject(vdata)) {
                            $.each(vdata, function (index, val) {
                                console.log($('[name="' + v + '[' + index + ']"]'));
                                $('[name="' + v + '[' + index + ']"]').val(val);
                            });
                        } else {
                            if (v == 'defined') {
                                vdata = vdata.replace(/\\(['|"])/g, "$1");
                            }
                            $('[name="' + v + '"]').val(vdata);
                        }
                    }
                });
            });
            $("[name='field']", $form).on('change', function (e) {
                e.preventDefault();
                var val = $(this).selectpicker('val');
                iFormer.unsigned_wrap(val, $form);
            });
            $("[name='type']", $form).on('change', function (e) {
                e.preventDefault();
                var val = $(this).selectpicker('val');
                iFormer.option_wrap(val, $form);
            });
        },

        unsigned_wrap: function (val, $form) {
            if ($.inArray(val, iFormer.TypeMap['number']) > -1) {
                $('#unsigned-wrap', $form).removeClass("hide");
                $('[name="unsigned"]').removeAttr('disabled');
            } else {
                $('#unsigned-wrap', $form).addClass("hide");
                $('[name="unsigned"]').attr("disabled", true);
            }
        },

        option_wrap: function (val, $form,flag) {
            if ($.inArray(val, iFormer.TypeMap['option']) > -1) {
                $("#option-wrap", $form).removeClass("hide");
                $("[name='option']").removeAttr('disabled');
            } else {
                $("#option-wrap", $form).addClass("hide");
                $("[name='option']").attr("disabled", true);
            }
            $("[name='option']", $form).on('input',function (e) {
                e.preventDefault();
                var field = $("select[name='field']").val();
                var vals = $(this).val();
                if (vals) {
                    var result = iFormer.parse_option(vals);
                    // console.log(result);
                    for (let [key, value] of Object.entries(result)) {
                        if ($.inArray(field, iFormer.TypeMap['number']) > -1 && !iFormer.isNumeric(value)) {
                            iCMS.notify.info(`选项列表中 ${key} 选项值只能为数字类型，请修改！或者修改字段类型为文本类型`);
                        }
                    }  
                }

            });
        },
        isNumeric:function(value) {
            return !isNaN(Number(value)) && !isNaN(parseFloat(value));
        },
        parse_option:function (configStr) {
            const result = {};
            const lines = configStr.split(';').filter(line => line.trim());
            
            lines.forEach((line, index) => {
                const trimmedLine = line.trim();
                if (trimmedLine.includes('=')) {
                    // 有值项
                    const [key, value] = trimmedLine.split('=');
                    result[key.trim()] = value.trim();
                } else {
                    // 无值项，值为索引值
                    result[trimmedLine] = index;
                }
            });
            
            return result;
        },
        edit_form_init: function (obj, dom) {
            var form = $("#former-field-form", dom);
            // 清空 form 内的所有表单元素
            form.find('input, select, textarea').each(function () {
                var $this = $(this);
                if ($this.hasClass('selectpicker')) {
                    $this.selectpicker('val', '')
                }
                // 根据元素类型清空值
                else if ($this.is('input[type="text"], input[type="password"], input[type="email"], input[type="number"], textarea')) {
                    $this.val(''); // 清空文本输入框或多行文本框
                } else if ($this.is('input[type="checkbox"], input[type="radio"]')) {
                    $this.prop('checked', false); // 取消复选框或单选框的选中状态
                } else if ($this.is('select')) {
                    $this.prop('selectedIndex', 0); // 将下拉框重置为第一个选项
                }

            });

            var ipt = $("[name='tabs']", dom);
            ipt.find('option').remove();
            $("li.nav-item", "#former-tabs").each(function (index, el) {
                var td = $(el).data("tabs");
                if (td) {
                    var selected = obj['tabs'][0] == td[0];
                    ipt.append($("<option>", {
                        value: td[0],
                        text: td[1], selected
                    }));
                }
            });
            ipt.selectpicker('destroy').selectpicker({
                "styleBase": "form-control",
            });
            obj['access'] = obj['access'] || {};
            if (isUndef(obj['admincp_index'])) {
                obj['admincp_index'] = 1
            }

            ['view', 'edit', 'delete'].forEach(name => {
                var ipt = $("[name='access[" + name + "][]']", dom);
                iFormer.setValue(ipt, obj['access'][name]);
            });

            var ipt = $("[name='validate[]']", dom);
            if (obj['validate']) {
                $.each(obj['validate'], function (i, v) {
                    var vipt = $("[validate='" + v + "']");
                    if (vipt.length > 0) {
                        vipt.data('value', obj[v]);
                    }
                });
            }
            iFormer.option_wrap(obj['type'], form);
            iFormer.unsigned_wrap(obj['field'], form);
            // iFormer.setValue(ipt, obj['validate'] || []);
            if (obj['validate']) {
                ipt.selectpicker('val', obj['validate'])
                const allOptions = ipt.find('option').get();
                ipt.empty();
                const vdtexts = [];
                // 更新显示

                obj['validate'].forEach(function (v) {
                    let option = allOptions.find(function (o) {
                        return o.value == v;
                    });
                    vdtexts.push($(option).text());
                    ipt.append(option);
                })
                const $inner = ipt.next('button').find('.filter-option-inner-inner');
                $inner.html(vdtexts.join(','));
                ipt.append(allOptions.filter(o => !obj['validate'].includes($(o).val())))
            }

            for (var name in obj) {
                var _value = obj[name];
                if (name == 'javascript') {
                    _value = _value.replace(/\\(['|"])/g, "$1");
                }
                if (name != 'validate' && name != 'tabs' && name != 'access') {
                    var ipt = $("[name='" + name + "']", dom);
                    iFormer.setValue(ipt, _value);
                }
                if (name == 'admincp_index') {
                    ipt.prop('checked', _value == 1 ? 1 : 0).trigger('change');
                }
            }

            $("#labelafter-wrap", dom).addClass("hide");
            if (obj['labelafter']) {
                $("#labelafter-wrap", dom).removeClass("hide");
            }
        },
        /**
         * 字段编辑框
         * @param  {[type]}   obj      [description]
         * @param  {Function} callback [deiFormer-scription]
         * @return {[type]}            [description]
         */

        edit_dialog: function (obj, callback) {
            var me = this, _id = obj['id'];
            var $form = $("#former-field-form");

            return iCMS.ui.dialog({
                skin: 'former-dialog',
                title: '表单字段设置',
                width: 'auto',
                content: $("#former-field-editor"),
                success: function (dom, index, that) {
                    me.edit_form_init(obj, dom);
                },
                yes: function (index, layero) {
                    var form = $("#former-field-form", layero);
                    var $data = form.serializeJSON();

                    if (isUndef($data['validate'])) {
                        $data['validate'] = '';
                    }
                    if (isUndef($data['access'])) {
                        $data['access'] = {};
                    }
                    ['view', 'edit', 'delete'].forEach(name => {
                        if (isUndef($data['access'][name])) {
                            $data['access'][name] = [];
                        }
                    });

                    //更新字段展现
                    var data = $.extend({}, obj, $data, {
                        'tabs': [
                            $('[name="tabs"]', $form).val(),
                            $('[name="tabs"]', $form).find("option:selected").text()
                        ],
                    });

                    if (!data.label) {
                        iCMS.ui.alert("请填写字段名称!");
                        return false;
                    }
                    if (!data.name) {
                        iCMS.ui.alert("请填写字段名!");
                        return false;
                    }
                    if (data.name == 'url') {
                        iCMS.ui.alert("【url】为系统保留字段，禁止使用");
                        return false;
                    }
                    if (data['id'] != data['name']) {
                        data['id'] = data['name'];
                        $('[name="id"]', $form).val(data['id']);
                    }
                    var $apptype = $('[name="rs[apptype]"]').val();
                    if ($apptype == "2") {
                        var dname = $('[name="_field_' + data.name + '"]', '.former-layout').not('[name="_field_' + _id + '"]');
                    } else {
                        var dname = $('td[field="' + data.name + '"]', '.app-table-list').not('td[field="' + _id + '"]');
                    }

                    if (dname.length) {
                        iCMS.ui.alert("该字段名已经存在,请重新填写");
                        return false;
                    }

                    $('td[field="' + _id + '"]').attr('field', data.name).text(data.name);

                    //更新 fields[]
                    if (data['func']) {
                        //fields['func'] = $.unique(fields['func']);
                    }
                    callback(data);
                    me.freset($form);
                    layer.close(index);
                },
                cancel: function () {
                    me.freset($form);
                    return true;
                }
            });
        }
    };
    return iFormer;
});