define(['jquery'], function ($) {
    "use strict";

    const doc = $(document);

    // 简单模块代理映射：方法名 -> 模块名
    const MOD_MAP = {
        rating: 'rating',
        autocomplete: 'autocomplete',
        switch: 'switch',
        prop: 'selectpage',
        node: 'selectpage',
        selectpage: 'selectpage',
        'bs-selectpicker': 'bs-selectpicker',
        selectpicker: 'selectpicker',
        daterangepicker: 'daterangepicker',
        datepicker: 'datepicker',
        citypicker: 'citypicker',
        editor: 'editor',
        form: 'form',
        ajax: 'ajax',
        metadata: 'metadata',
        batch: 'batch'
    };

    // 文件相关方法统一处理
    const FILE_METHODS = ['file_btns_upload', 'file_btns_browse', 'file_btns', 'file_browse', 'file_upload'];

    const factory = {
        enabled: '',
        evented: '',

        run($el, method, eventType) {
            const params = { $el, el: $el[0], event: {}, eventType };
            if (this[method]) {
                this[method](params);
            } else {
                console.warn(`[UI] Method "${method}" not found`);
            }
        },

        on(evt, selector, func, enabledClass, nodes) {
            if (evt === 'bind') {
                $(selector).each(function () {
                    func.call(this);
                    if (enabledClass) $(this).addClass(enabledClass);
                });
                return;
            }

            const $doc = nodes || doc;
            evt = evt || 'click';
            $(selector).removeClass(this.enabled).addClass(this.evented);
            $doc.off(evt, selector).on(evt, selector, function (event) {
                event.preventDefault();
                func.call(this, event);
            });
        },

        bind($el, func, evt = 'click') {
            $el.off(evt).on(evt, function (event) {
                event.preventDefault();
                func.call(this);
            });
        },

        mod(param, module) {
            require([module], (mod) => {
                if (typeof mod === 'function') {
                    mod(param);
                } else if (mod?.init) {
                    mod.init(param);
                }
            });
        },

        // Bootstrap 组件
        popover({ $el, el }) {
            return new bootstrap.Popover(el, $.extend({ animation: true, trigger: 'hover focus' }, $el.data()));
        },

        tip(params) { this.tooltip(params); },

        tooltip({ $el, el, eventType }) {
            if ($el.hasClass('cms-ui-enabled') && eventType === 'click') return;
            return new bootstrap.Tooltip(el, $.extend({ animation: true }, $el.data()));
        },

        // jQuery 插件
        maxlength({ $el }) {
            require(['jq-maxlength'], () => {
                $el.maxlength({
                    alwaysShow: !!$el.data('always-show'),
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

        rangeslider({ $el }) {
            require(['jq-rangeslider'], () => {
                $el.ionRangeSlider({ input_values_separator: ';', skin: $el.data('skin') || 'round' });
            });
        },

        slider({ $el }) {
            const opts = {
                arrows: $el.data('arrows') || false,
                dots: $el.data('dots') || false,
                slidesToShow: $el.data('slides-to-show') || 1,
                centerMode: $el.data('center-mode') || false,
                autoplay: $el.data('autoplay') || false,
                autoplaySpeed: $el.data('autoplay-speed') || 3000,
                infinite: $el.data('infinite') ?? true
            };
            if ($el.data('responsive')) {
                opts.responsive = [
                    { breakpoint: 1024, settings: { slidesToShow: 3, slidesToScroll: 3 } },
                    { breakpoint: 600, settings: { slidesToShow: 2, slidesToScroll: 2 } },
                    { breakpoint: 480, settings: { slidesToShow: 1, slidesToScroll: 1 } }
                ];
            }
            require(['jq-slick'], () => $el.slick(opts).show());
        },

        gallery({ $el }) {
            require(['jq-magnific-popup'], () => {
                $el.magnificPopup({
                    delegate: $el.data('delegate'),
                    items: $el.data('items'),
                    gallery: { enabled: true },
                    type: 'image'
                });
            });
        },

        imgload({ $el }) {
            require(['jq-lazyload'], () => $el.lazyload());
        },

        // 媒体查询
        getmedia(func) {
            const queries = {
                xs: '(max-width: 575.98px)',
                sm: '(min-width: 576px) and (max-width: 767.98px)',
                md: '(min-width: 768px) and (max-width: 991.98px)',
                lg: '(min-width: 992px) and (max-width: 1199.98px)',
                xl: '(min-width: 1200px) and (max-width: 1399.98px)',
                xxl: '(min-width: 1400px)'
            };
            const getCurrent = () => Object.entries(queries).find(([, q]) => window.matchMedia(q).matches)?.[0];
            if (func) window.addEventListener('resize', () => func(getCurrent()));
            return getCurrent();
        },

        // DOM 操作
        'class-toggle'({ el }) {
            el.addEventListener('click', () => {
                el.classList.add('js-class-toggle-enabled');
                const classes = el.dataset.class?.split(' ') || [];
                document.querySelectorAll(el.dataset.target).forEach(t => classes.forEach(c => t.classList.toggle(c)));
            });
        },

        'form-switch'({ $el }) {
            $el.off('change', 'input[type="checkbox"]').on('change', 'input[type="checkbox"]', function (e) {
                e.preventDefault();
                const url = $el.data('url') || $(this).data('url');
                const params = { ...$el.data('params'), ...$(this).data('params'), value: $(this).prop('checked') ? 1 : 0 };
                if (url) {
                    iCMS.api.post(url, params, () => {
                        iCMS.notify.success(['msg:update', params.id, Lang('Field'), Lang(params.field), params.value]);
                    });
                }
            });
        },

        markdown(params) {
            params.$el.data('markdown', true);
            this.mod(params, 'editor');
        },

        insert({ $el, el }) {
            const doInsert = (ele, tgt) => {
                const $ele = $(ele);
                let data = $ele.data('insert') || $ele.data('data') || $ele.attr('href');
                let target = tgt || $ele.data('target') || $ele.siblings('input');
                if (data === '<%var%>') data = '<%var_' + iCMS.utils.random(2) + '%>';
                if (!target.length) target = $ele.parent().siblings('input');
                $ele.data('mode') ? $(target).val(data) : $(target).insertData(data);
            };

            if (el.tagName === 'A' || el.tagName === 'BUTTON') {
                doInsert(el);
            } else {
                $el.off('click', $el.data('item')).on('click', $el.data('item'), function () {
                    doInsert(this, $el.data('target'));
                });
            }
        },

        random({ $el }) {
            const target = $el.data('target') || $el.siblings('input');
            $(target).val(iCMS.utils.random($el.data('len') || 8));
        },

        remove({ $el }) {
            const removeSelector = $el.data('remove');
            const url = $el.data('url');
            removeSelector ? $el.parents(removeSelector).remove() : $el.remove();
            if (url) {
                iCMS.api.post(url, { data: $($el.data('inputs')).serializeJSON(), remove: 1 });
            }
        },

        dblclick({ $el }) {
            this.bind($el, function () { window.location.href = $(this).attr('href'); }, 'dblclick');
        },

        checkAll({ $el }) {
            this.bind($el, function () {
                $('input:checkbox', $(this).data('target')).prop('checked', $(this).prop('checked'));
            }, 'change');
        },

        captcha({ $el }) {
            this.bind($el, function () { iCMS.ui.reCaptcha(this); });
        },

        modal({ $el, el }) {
            iCMS.ui.modal(el, { url: $el.attr('href') });
        },

        boxform({ $el, el }) {
            const $data = $el.data();
            const $form = $($data.form || '#i-form');
            const url = $form.attr('action');
            const form = require('form');

            iCMS.ui.dialog({
                title: $data.title || 'Add',
                content: $data.box || '#i-box',
                height: 'auto',
                yes() {
                    if (!form.check($form)) return false;
                    iCMS.api.post(url, { data: $form.serializeJSON() }, (json) => {
                        if ($data.success && typeof window[$data.success] === 'function') {
                            window[$data.success].call(el, json, $data);
                        }
                    });
                },
                end() { $form[0].reset(); }
            });
        },

        dialog({ $el, el }) {
            const opts = $el.data();
            const url = $el.attr('href');
            let row = {}, callback = null;

            if (isDef(opts.tableId)) {
                const btn = this.getTableButton(opts);
                if (typeof btn?.callback === 'function') callback = btn.callback;
                row = $('#' + opts.tableId).bootstrapTable('getRowByUniqueId', opts.rowId) || {};
            }

            iCMS.ui.dialog(el, { url, callback, row, ...(isDef(opts.dialog) && { content: opts.dialog }) });
        },

        checked({ $el, el }) {
            const check = (element, val) => {
                const $e = $(element);
                const v = $e.data('checked') ?? val;
                if (v == null || !['radio', 'checkbox'].includes($e.attr('type'))) return;
                if (v.toString().split(',').includes($e.attr('value'))) {
                    $e.prop('checked', true).trigger('change');
                }
            };
            setTimeout(() => {
                check(el);
                $el.find('input:radio,input:checkbox').each((_, e) => check(e, $el.data('checked')));
            }, 100);
        },

        masked({ $el }) {
            require(['jq-masked'], () => $el.mask('9999.99').addClass('js-masked-enabled'));
        },

        checkbox(params) { this.checkinput(params); },
        radio(params) { this.checkinput(params); },

        checkinput({ $el }) {
            const opts = $el.data();
            const name = $el.attr('name');
            const type = $el.attr('type') || 'radio';
            const value = $el.val();
            const field = opts.field || name;
            const { data } = iCMS.utils.source(opts.source, field, opts.placeholder || $el.attr('placeholder') || Lang('msg:select'));

            if (!data) return;
            $el.attr('disabled', 'disabled').hide().parent().addClass('d-flex align-items-center');

            data.forEach((v, idx) => {
                if (v.id === '') return;
                const id = `${field}_${idx}`;
                const $inline = $('<div>').addClass('form-check form-check-inline');
                const $input = $('<input>', { id, checked: v.id == value, class: 'form-check-input', name, value: v.id, type });
                const $label = $('<label>', { class: 'form-check-label', for: id }).text(v.name);
                $el.after($inline.append($input, $label));
            });
        },

        select({ $el, el }) {
            const setSelected = (element) => {
                if (element.tagName !== 'SELECT') return;
                const v = $(element).attr('value');
                if ($(element).attr('multiple')) {
                    v.split(',').forEach(val => $(`option[value="${val}"]`, element).attr('selected', true));
                } else {
                    $(element).val(v);
                    $(`option[value="${v}"]`, element).attr('selected', true);
                }
                $(element).trigger('change');
            };
            setTimeout(() => {
                setSelected(el);
                $el.find('select[value]').each((_, e) => setSelected(e));
            }, 100);
        },

        tabs() {
            const tabShow = (el) => new bootstrap.Tab(el).show();
            if (!$('.nav-tabs:first a.nav-link.active').length) {
                const $first = $('.nav-tabs:not(.without-auto-active) a.nav-link:first');
                if ($first.length) {
                    $first.hasClass('js-tabs-link') ? $first.addClass('active') : tabShow($first[0]);
                }
            }
            doc.on('click', '.nav-link:not(.js-tabs-link)', function (e) {
                e.preventDefault();
                tabShow(this);
            });
        },

        getTableButton(opts) {
            if (isUndef(opts.tableId) || isUndef(opts.fieldIndex) || isUndef(opts.buttonIndex)) return null;
            const tableOpts = $('#' + opts.tableId).bootstrapTable('getOptions');
            if (!tableOpts) return null;

            for (const cols of tableOpts.columns) {
                const col = cols.find(c => c.fieldIndex === opts.fieldIndex);
                if (col) return col.buttons?.[opts.buttonIndex] || null;
            }
            return null;
        }
    };

    // 动态生成模块代理方法
    Object.entries(MOD_MAP).forEach(([method, module]) => {
        if (!factory[method]) {
            factory[method] = function (params) { this.mod(params, module); };
        }
    });

    // 文件相关方法统一代理
    FILE_METHODS.forEach(method => {
        factory[method] = function (params) {
            require(['files'], (mod) => mod.init(params));
        };
    });

    return factory;
});