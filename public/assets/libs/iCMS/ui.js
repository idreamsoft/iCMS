define(['jquery'], function ($) {
    "use strict";
    var doc = $(document);
    const factory = {
        enabled: '', //绑定执行，排除事件类
        evented: '', //事件执行，排除绑定类
        run: function ($el, method, eventType) {
            let params = { $el, el: $el[0], event: {}, eventType };
            if (factory.hasOwnProperty(method)) {
                factory[method](params);
            } else {
                console.warn(`Method ${method} not found in ui.js`);
            }
        },
        on: function (evt, s, func, enabled, nodes) {
            // console.log({s,evt});
            if (evt === 'bind') {
                $(s).each(function () {
                    func.call(this);
                    enabled && $(this).addClass(enabled);
                });
                return;
            }
            let doc = nodes || $(document);
            evt = evt || 'click';
            $(s).removeClass(factory.enabled);
            $(s).addClass(factory.evented);
            doc.off(evt, s).on(evt, s, function (event) {
                event.preventDefault();
                func.call(this, event);
            });
        },
        bind: function (s, func, evt) {
            evt = evt || 'click';
            s.off(evt).on(evt, function (event) {
                event.preventDefault();
                func.call(this);
            });
        },
        /**
         * 
         * @param {object} param { $el, el } 
         * @param {string} module /assets/libs/iCMS/module.js 
         */
        mod: function (param, module) {
            require([module], function (factory) {
                if (typeof factory === "function") {
                    result = factory(param);
                } else if (typeof factory === "object") {
                    factory.init(param);
                    // if (factory.hasOwnProperty("init")) {
                    // }
                }
                // mod.init(params);
            });
        },
        rating: function (params) {
            this.mod(params, "rating");
        },
        popover: function ({ $el, el }) {
            var options = $.extend({
                animation: true,
                trigger: 'hover focus',
            }, $el.data());
            return new bootstrap.Popover(el, options)
        },
        tip: function (params) {
            this.tooltip(params);
        },
        tooltip: function ({ $el, el, eventType }) {
            if ($el.hasClass('cms-ui-enabled') && eventType == 'click') {
                return;
            }
            var options = $.extend({
                animation: true,
            }, $el.data());
            return new bootstrap.Tooltip(el, options);
        },
        maxlength: function ({ $el, el }) {
            require(["jq-maxlength"], function () {
                // Add .js-maxlength-enabled class to tag it as activated and init it
                $el.maxlength({
                    alwaysShow: $el.data('always-show') ? true : false,
                    threshold: $el.data('threshold') || 10,
                    warningClass: $el.data('warning-class') || 'badge bg-warning',
                    limitReachedClass: $el.data('limit-reached-class') || 'badge bg-danger',
                    placement: $el.data('placement') || 'bottom',
                    preText: $el.data('pre-text') || '',
                    separator: $el.data('separator') || '/',
                    postText: $el.data('post-text') || ''
                });
            });

        },
        rangeslider: function ({ $el, el }) {
            require(["jq-rangeslider"], function () {
                $el.ionRangeSlider({
                    input_values_separator: ';',
                    skin: $el.data('skin') || 'round'
                });
            });
        },
        autocomplete: function (params) {
            this.mod(params, "autocomplete");
        },
        getmedia: function (func) {
            // 定义媒体查询
            const mediaQueries = {
                xs: window.matchMedia("(max-width: 575.98px)"),
                sm: window.matchMedia("(min-width: 576px) and (max-width: 767.98px)"),
                md: window.matchMedia("(min-width: 768px) and (max-width: 991.98px)"),
                lg: window.matchMedia("(min-width: 992px) and (max-width: 1199.98px)"),
                xl: window.matchMedia("(min-width: 1200px) and (max-width: 1399.98px)"),
                xxl: window.matchMedia("(min-width: 1400px)")
            };

            // 获取当前断点
            function getCurrentBreakpoint() {
                for (const [breakpoint, query] of Object.entries(mediaQueries)) {
                    if (query.matches) return breakpoint;
                }
            }

            // 监听窗口大小变化
            window.addEventListener("resize", () => {
                const currentBreakpoint = getCurrentBreakpoint();
                if (typeof func === 'function') {
                    func(currentBreakpoint);
                }
                console.log("当前断点:", currentBreakpoint);
            });

            // 初始化时获取一次断点
            console.log("初始断点:", getCurrentBreakpoint());
            return getCurrentBreakpoint();

        },
        slider: function ({ $el, el }) {
            let $options = {
                arrows: $el.data('arrows') || false,
                dots: $el.data('dots') || false,
                slidesToShow: $el.data('slides-to-show') || 1,
                centerMode: $el.data('center-mode') || false,
                autoplay: $el.data('autoplay') || false,
                autoplaySpeed: $el.data('autoplay-speed') || 3000,
                infinite: typeof $el.data('infinite') === 'undefined' ? true : el.data('infinite'),
            };
            if ($el.data('responsive')) {
                $options.responsive = [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 3,
                        }
                    },
                    {
                        breakpoint: 600,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 2,

                        }
                    },
                    {
                        breakpoint: 480,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1,
                        }
                    }
                ];
            }
            require(["jq-slick"], function () {
                $el.slick($options).show();
            });
        },
        gallery: function ({ $el, el }) {
            var delegate = $el.data('delegate'); //'a.img-lightbox'
            var items = $el.data('items');
            require(["jq-magnific-popup"], function () {
                $el.magnificPopup({
                    delegate,
                    items,
                    gallery: {
                        enabled: true
                    },
                    type: 'image' // this is a default type
                });
            });
        },
        imgload: function ({ $el, el }) {
            require(["jq-lazyload"], function () {
                $el.lazyload();
            });
        },
        "class-toggle": function ({ $el, el }) {
            el.addEventListener('click', () => {
                // Add .js-class-toggle-enabled class to tag it as activated
                el.classList.add('js-class-toggle-enabled');

                // Get all classes
                let cssClasses = el.dataset.class ? el.dataset.class.split(' ') : false;

                // Toggle class on target elements
                document.querySelectorAll(el.dataset.target).forEach(targetEl => {
                    if (cssClasses) {
                        cssClasses.forEach(cls => {
                            targetEl.classList.toggle(cls);
                        });
                    }
                });
            });
        },
        "form-switch": function ({ $el, el }) {
            $el.off('change', 'input[type="checkbox"]')
                .on('change', 'input[type="checkbox"]', function (event) {
                    event.preventDefault();
                    var url = $el.data("url") || $(this).data("url") || '';
                    var checked = $(this).prop("checked");
                    var params = $el.data("params") || $(this).data("params") || {};
                    params['value'] = (checked ? 1 : 0);
                    // console.log(params, this);

                    if (url) {
                        iCMS.api.post(url, params, function (json) {
                            iCMS.notify.success([
                                "msg:update",
                                params['id'],
                                Lang("Field"),
                                Lang(params['field']),
                                params['value']
                            ]);
                        })
                    }
                });
        },
        switch: function (params) {
            this.mod(params, "switch");
        },
        file_btns_upload: function (params) {
            this.__files(params);
        },
        file_btns_browse: function (params) {
            this.__files(params);
        },
        file_btns: function (params) {
            this.__files(params);
        },
        file_browse: function (params) {
            this.__files(params);
        },
        file_upload: function (params) {
            this.__files(params);
        },
        __files: function (params) {
            require(["files"], function (factory) {
                factory.init(params);
            })
        },
        prop: function (params) {
            this.mod(params, "selectpage");
        },
        node: function (params) {
            this.mod(params, "selectpage");
        },
        selectpage: function (params) {
            this.mod(params, "selectpage");
        },
        "bs-selectpicker": function (params) {
            this.mod(params, "bs-selectpicker");
        },
        selectpicker: function (params) {
            this.mod(params, "selectpicker");
        },
        daterangepicker: function (params) {
            this.mod(params, "daterangepicker");
        },
        datepicker: function (params) {
            this.mod(params, "datepicker");
        },
        citypicker: function (params) {
            this.mod(params, "citypicker");
        },
        editor: function (params) {
            this.mod(params, "editor");
        },
        markdown: function (params) {
            params['$el'].data('markdown', true);
            this.mod(params, "editor");
        },
        form: function (params) {
            this.mod(params, "form");
        },
        ajax: function (params) {
            this.mod(params, "ajax");
        },
        insert: function ({ $el, el }) {
            function insert(ele, target) {
                let $ele = $(ele);
                var data = $ele.data("insert") || $ele.data("data") || $ele.attr("href"),
                    target = target || $ele.data("target") || $ele.siblings('input'),
                    mode = $ele.data("mode");
                if (data == "<%var%>") {
                    data = "<%var_" + iCMS.utils.random(2) + "%>";
                }
                if (target.length < 1) {
                    target = $ele.parent().siblings('input');
                }
                if (mode) {
                    $(target).val(data);
                } else {
                    $(target).insertData(data);
                }
            }
            if (el.tagName == 'A' || el.tagName == 'BUTTON') {
                insert(el);
                return;
            } else {
                let item = $el.data("item");
                let target = $el.data("target")
                $el.off('click', item)
                    .on('click', item, function (event) {
                        insert(this, target);
                    });
            }
            // this.on($el, function () {

            // });
        },
        random: function ({ $el, el }) {
            // this.on($el, function () {
            var target = $el.data("target") || $el.siblings('input'),
                len = $el.data("len") || 8;
            $(target).val(iCMS.utils.random(len));
            // });
        },
        remove: function ({ $el, el }) {
            let remove = $el.data("remove");
            let url = $el.data("url");
            if (remove) {
                $el.parents(remove).remove();
            } else {
                $el.remove();
            }
            if (url) {
                let inputs = $el.data("inputs");
                let data = $(inputs).serializeJSON();
                iCMS.api.post(url, { data, remove: 1 });
            }
        },
        dblclick: function ({ $el, el }) {
            this.bind($el, function () {
                var href = $(this).attr("href");
                console.log(href);
                window.location.href = href;
            }, 'dblclick');
        },
        checkAll: function ({ $el, el }) {
            this.bind($el, function () {
                var target = $(this).data("target"),
                    checked = $(this).prop("checked");
                $("input:checkbox", target).prop('checked', checked);
            }, 'change');
        },
        captcha: function ({ $el, el }) {
            this.bind($el, function () {
                iCMS.ui.reCaptcha(this);
            });
        },
        modal: function ({ $el, el }) {
            var url = $el.attr('href');
            iCMS.ui.modal(el, { url });
        },
        boxform: function ({ $el, el }) {
            // iCMS.callDataFunc.call(this, $el, 'success');
            let $data = $el.data();
            let $fel = $($data['form'] || "#i-form");
            let $url = $fel.attr('action');
            let form = require("form");
            iCMS.ui.dialog({
                title: $data['title'] || 'Add',
                content: $data['box'] || '#i-box',
                height: 'auto',
                yes: function () {
                    if (!form.check($fel)) {
                        return false;
                    }
                    let data = $fel.serializeJSON();
                    iCMS.api.post($url, { data }, function (json) {
                        var func = $data['success'] || false;
                        if (func && typeof window[func] == 'function') {
                            window[func].call(el, json, $data);
                        }
                    });
                },
                end: function () {
                    $fel[0].reset();
                }
            });
        },
        dialog: function ({ $el, el }) {
            var that = this;
            var $options = $el.data();
            var url = $el.attr('href');
            // url = iCMS.url(url);
            var row = {}
            var callback = null
            if (!isUndef($options.tableId)) {
                var button = that.getTableButton($options);
                // console.log($options);
                if (typeof (button['callback']) === "function") {
                    callback = button['callback'];
                }
                // if (typeof(button['cancel']) === "function") {
                //     cancel = button['cancel'];
                // }
                row = $("#" + $options.tableId).bootstrapTable('getRowByUniqueId', $options.rowId) || {};
            }
            var params = { url, callback, row };
            if (!isUndef($options.dialog)) {
                params.content = $options.dialog;
            }
            iCMS.ui.dialog(el, params);
        },
        checked: function ({ $el, el }) {
            function checked(element, checked) {
                let $ele = $(element);
                var _checked = $ele.data('checked');
                if (isDef(_checked)) {
                    checked = _checked.toString()
                }
                if (isUndef(checked)) {
                    return;
                }

                let typeMap = ['radio', 'checkbox'];
                let type = $ele.attr('type');
                if (!typeMap.includes(type)) {
                    return;
                }

                let value = $ele.attr('value');
                let array = checked.toString().split(',');
                if (array.includes(value)) {
                    $ele.prop("checked", "checked");
                    $ele.trigger("change");
                }
            }
            window.setTimeout(function () {
                checked(el);
                $el.find("input:radio,input:checkbox").each(function (index, element) {
                    let value = $el.data('checked');
                    checked(element, value);
                });
            }, 100);
        },
        masked: function ({ $el, el }) {
            require(['jq-masked'], function (jm) {
                // $.mask.definitions['~']='[+-]';
                let format = $el.data('format') || '';
                $el.mask('9999.99').add('js-masked-enabled');
                // jQuery('.js-masked-date:not(.js-masked-enabled)').mask('99/99/9999');
                // this.mod(params, "masked");
            });
        },
        checkbox: function (params) {
            factory.checkinput(params);
        },
        radio: function (params) {
            factory.checkinput(params);
        },
        checkinput: function ({ $el, el }) {
            var $opt = $el.data();
            var name = $el.attr("name");
            var type = $el.attr("type") || 'radio';
            var value = $el.val();
            var field = $opt['field'] || name;
            var placeholder = $opt["placeholder"] || $el.attr("placeholder") || Lang('msg:select');
            var { url, data } = iCMS.utils.source($opt["source"], field, placeholder);
            console.log(url, data);
            if (data) {
                $el.attr('disabled', 'disabled').hide();
                $el.parent().addClass("d-flex align-items-center");
                var $div = [];
                $.each(data, function (idx, v) {
                    if (v.id !== '') {
                        var checked = (v.id == value);

                        var id = field + '_' + idx;
                        var $inline = $('<div>').addClass('form-check form-check-inline');
                        var $input = $('<input>').attr({
                            id,
                            checked,
                            'class': 'form-check-input',
                            'name': name,
                            'value': v.id,
                            'type': type
                        });
                        var $label = $('<label>').attr({
                            'class': 'form-check-label',
                            'for': id
                        }).text(v.name);

                        $inline.append($input, $label);
                        $div.push($inline);
                        // console.log($div);
                    }
                    $el.after($div);
                });
            }
        },
        select: function ({ $el, el }) {
            function selected(element) {
                if (element.tagName != 'SELECT') {
                    return;
                }
                var v = $(element).attr('value');
                if ($(element).attr("multiple")) {
                    var vs = v.split(',');
                    $.each(vs, function (i, v) {
                        $('option[value="' + v + '"]', element).attr("selected", true);
                    });
                } else {
                    $(element).val(v);
                    $('option[value="' + v + '"]', element).attr("selected", true);
                }
                $(element).trigger("change");
            }
            window.setTimeout(function () {
                selected(el)
                $el.find("select[value]").each(function (index, element) {
                    selected(element)
                });
            }, 100);
        },
        metadata: function (params) {
            this.mod(params, "metadata");
        },
        batch: function (params) {
            this.mod(params, "batch");
        },

        tabs: function () {
            var tabShow = function (tel) {
                var tabTrigger = new bootstrap.Tab(tel);
                tabTrigger.show();
            }
            if ($('.nav-tabs:first a.nav-link.active').length < 1) {
                // var firstTabEl = document.querySelector('.nav-tabs a.nav-link:first-child')
                var firstTabEl = $(".nav-tabs:not(.without-auto-active) a.nav-link:first")

                if (firstTabEl.size() > 0) {
                    if (firstTabEl.hasClass('js-tabs-link')) {
                        firstTabEl.addClass('active');
                    } else {
                        tabShow(firstTabEl[0]);
                    }
                }
            }
            doc.on('click', ".nav-link:not(.js-tabs-link)", function (e) {
                e.preventDefault();
                tabShow(this);
            });
            // var s = '[data-toggle="tabs"]:not(.js-tabs-enabled)'
            // $(s).each((index, element) => {
            //     // let el = $(element);

            //     // el.find(".nav-link:not(.js-tabs-link)").each((idx, elem) => {
            //     //     elem.addEventListener("click", function (event) {
            //     //         event.preventDefault();
            //     //         tabShow(elem);
            //     //     });
            //     // });
            // }).addClass("js-tabs-enabled");
        },

        getTableButton: function (options) {
            if (!isUndef(options.tableId) && !isUndef(options.fieldIndex) && !isUndef(options.buttonIndex)) {
                var tableOptions = $("#" + options.tableId).bootstrapTable('getOptions');
                if (tableOptions) {
                    var columnObj = null;
                    $.each(tableOptions.columns, function (i, columns) {
                        $.each(columns, function (j, column) {
                            if (column.fieldIndex === options.fieldIndex) {
                                columnObj = column;
                                return false;
                            }
                        });
                        if (columnObj) {
                            return false;
                        }
                    });
                    if (columnObj) {
                        return columnObj['buttons'][options.buttonIndex];
                    }
                }
            }
            return null;
        },
    }
    return factory;
});