define(['ui'], function (UI) {
    "use strict";

    // 编辑器包装类
    class UEditor {
        constructor(id, config) {
            this.id = id;
            this.config = config;
            this.instance = null;
            this._create();
        }

        _create() {
            try {
                UE.delEditor(this.id);
                this.instance = UE.getEditor(this.id, this.config);
            } catch (e) {
                console.error('[UEditor] 创建失败:', e);
            }
        }

        _exec(fn) {
            if (!this.instance) return null;
            const result = fn(this.instance);
            this.instance.focus();
            return result;
        }

        getContent() { return this.instance?.getContent() ?? ''; }
        setContent(content, flag = false) { this.instance?.setContent(content, flag); }
        hasContents() { return this.instance?.hasContents() ?? false; }
        execCommand(cmd) { this._exec(inst => inst.execCommand(cmd)); }

        insPageBreak() { this._exec(inst => inst.execCommand('pagebreak')); }

        delPageBreak() {
            this._exec(inst => {
                const html = inst.getContent().replace(/#--iCMS\.PageBreak--#/g, '');
                inst.setContent(html);
            });
        }

        cleanup() {
            this._exec(inst => {
                const html = inst.getContent();
                inst.setContent(typeof format === 'function' ? format(html) : html);
            });
        }

        destroy() {
            if (!this.instance) return;
            setTimeout(() => {
                try { UE.delEditor(this.id); } catch (e) {}
                this.instance = null;
            }, 200);
        }
    }

    // require 路径配置（只需配置一次）
    let configured = false;
    function ensureConfig() {
        if (configured) return;
        configured = true;
        require.config({
            paths: {
                "ueditor.admincp": iCMS.url('editor/assets/ueditor/admincp/init', false),
                "ueditor.usercp": iCMS.url('editor/assets/ueditor/usercp/init', false),
                "ueditor.simple": iCMS.url('editor/assets/ueditor/simple/init', false),
                "ZeroClipboard": "/assets/libs/editor/ueditor/dist/third-party/zeroclipboard/ZeroClipboard",
                "ueditor": "/assets/libs/editor/ueditor/dist/ueditor.all.min"
            }
        });
    }

    // 事件绑定（只需绑定一次）
    let bindEvents = false;
    function bindEditorEvents() {
        if (bindEvents) return;
        bindEvents = true;
        UI.on('click', '.js-editor-insPB', () => window.iEditor?.get(getActiveEditorId())?.insPageBreak());
        UI.on('click', '.js-editor-delPB', () => window.iEditor?.get(getActiveEditorId())?.delPageBreak());
        UI.on('click', '.js-editor-cleanup', () => window.iEditor?.get(getActiveEditorId())?.cleanup());
    }

    function getActiveEditorId() {
        return window.iEditor?.ids?.[0];
    }

    // 引导函数
    return function ($el, boot, callback) {
        ensureConfig();

        const id = $el.attr('id') || 'editor_' + Math.random().toString(36).slice(2, 11);
        $el.attr('id', id);

        require(['ZeroClipboard', 'ueditor.' + boot, 'ueditor'], (ZeroClipboard, config) => {
            window.ZeroClipboard = ZeroClipboard;

            const editor = new UEditor(id, config);
            $el.data('editor', editor.instance);

            bindEditorEvents();
            callback(id, editor);
        });
    };
});