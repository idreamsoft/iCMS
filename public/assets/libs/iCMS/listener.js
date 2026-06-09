define(["ui"], function (UI) {
    "use strict";

    const ENABLED = 'cms-ui-enabled';
    const EVENTED = 'cms-ui-evented';
    const DISABLED_SELECTOR = '.cms-ui-disabled,[i$=":disabled"]';

    // 选择器生成器
    const selector = {
        // 生成 [i^="prefix:"] 选择器
        attr: (tag, prefix = 'cms') => iCMS.$s(`${prefix}:`, '^=', tag),

        // 生成带排除条件的选择器
        withExcludes: (tag, prefix) => 
            `${selector.attr(tag, prefix)}:not(.${ENABLED}):not(.${EVENTED}):not(${DISABLED_SELECTOR})`,

        // 生成 cms 前缀选择器（排除 enabled）
        cms: (tag) => `${selector.attr(tag, 'cms')}:not(.${ENABLED})`,

        // 生成 hover 事件选择器
        hover: (tag) => `${selector.attr(tag, 'cms@h')}:not(.${ENABLED})`
    };

    const factory = {
        // DOM 变化监听器
        observe(find, callback, exclude) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (!mutation.addedNodes.length) return;
                    let nodes = $(find, mutation.addedNodes);
                    if (exclude) nodes = nodes.not(exclude);
                    if (nodes.length) callback(nodes, find);
                });
            });
            observer.observe(document, { childList: true, subtree: true, attributes: true });
        },

        // 模板渲染
        template(event) {
            event.preventDefault();
            const $el = $(this);
            const data = $el.data();
            const drive = $el.attr('template-drive') || 'template';

            if (isEmpty(data)) return;

            if (drive === 'template') {
                const opts = $el.getDatas('template');
                const tpl = iCMS.utils.template({
                    openTag: opts.opentag || '{{',
                    closeTag: opts.closetag || '}}'
                });
                $el.html(tpl.render($el.html(), data));
            } else if (drive === 'vue') {
                require(['vue'], (Vue) => new Vue({ el: this, data }));
            }
        },

        // 调用模块方法
        callMod(el, event) {
            const $el = el instanceof jQuery ? el : $(el);
            const iv = $el.iv();
            const methods = iv[2].split(',');

            return methods.map((method) => {
                const mod = iCMS.getModPath(iv, method);
                if (iv.length >= 4) method += '_' + iv.slice(3).join('_');

                const params = {
                    $el,
                    el: $el[0],
                    iv,
                    event,
                    eventType: event?.type || ''
                };
                return iCMS.run(mod, method, params);
            });
        },

        // 主运行函数
        run() {
            console.log('listener start');

            UI.enabled = ENABLED;
            UI.evented = EVENTED;

            // 事件处理器
            const call = function (event) {
                event?.preventDefault();
                factory.callMod(this, event);
            };

            // 禁用文本输入自动完成
            $('input[type="text"]').attr('autocomplete', 'off');

            // Bootstrap 组件绑定
            const bootstrapBinds = ['tooltip', 'popover'];
            bootstrapBinds.forEach((name) => {
                const sel = iCMS.$s(`cms:ui:${name}`);
                UI.on('bind', sel, call, ENABLED);
                factory.observe(sel, (nodes) => UI.on('bind', sel, call, ENABLED, nodes), '.cms-ui-mutation');
            });

            // 自动绑定选择器
            const cmsui = selector.withExcludes('*', 'cms:ui') + ':not(a,button,[type="button"])';
            const apps = selector.withExcludes('*', 'app');
            const binds = [
                selector.withExcludes('div'),
                selector.withExcludes('span'),
                selector.withExcludes('form'),
                cmsui,
                apps
            ].join(',');

            // 绑定执行
            UI.on('bind', binds, call, ENABLED);

            // 事件绑定
            UI.on('input', selector.cms('input'), call, EVENTED);
            UI.on('click', selector.cms('a'), call, EVENTED);
            UI.on('click', selector.cms('button'), call, EVENTED);
            UI.on('click', selector.cms('img'), call, EVENTED);
            UI.on('mouseenter touchstart', selector.hover('*'), call, EVENTED);

            // 模板事件
            UI.on('cms:event:template', '.cms-ui-template', factory.template);

            // 图片懒加载
            factory.observe('img[data-src]', (nodes) => {
                nodes.each(function () { this.src = $(this).data('src'); });
            });

            // 动态元素绑定
            factory.observe(binds, (nodes) => {
                UI.on('bind', binds, call, ENABLED, nodes);
            }, '.cms-ui-mutation');
        }
    };

    return factory;
});
