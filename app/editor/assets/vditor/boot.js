define(["ui"], function (UI) {
    "use strict";

    // Vditor 编辑器包装类
    class VditorEditor {
        constructor(id) {
            this.id = id;
            this.instance = null;
            this.messageListener = null;
            this.frame = null;
            this._create();
        }

        _create() {
            const $el = $('#' + this.id);
            if (!$el.length) {
                console.error('[VditorEditor] 找不到元素:', this.id);
                return;
            }

            // 创建 iframe
            this.frame = $(`<iframe 
                src="/assets/libs/editor/vditor/editor.html" 
                frameborder="0" scrolling="no" 
                style="width: 100%; height: 100%; border: none;"
                name="${this.id}"></iframe>`);

            $el.attr('id', this.id + '_ta').after(this.frame).hide();
            this._setupMessageListener();
        }

        _setupMessageListener() {
            if (this.messageListener) {
                window.removeEventListener('message', this.messageListener);
            }

            this.messageListener = (event) => {
                if (event.data?.height && typeof event.data.height === 'number') {
                    this.frame.height(event.data.height);
                }
                this.instance = window.VditorEditor?.[this.id];
            };

            window.addEventListener('message', this.messageListener);
        }

        getContent() {
            const content = this.instance?.getValue?.() ?? '';
            return content && content !== '\n' ? content : '';
        }

        setContent(content) { this.instance?.setValue?.(content); }
        hasContents() { return !!this.getContent(); }
        execCommand() {}

        insPageBreak() {
            if (this.instance?.insertValue) {
                this.instance.insertValue('\n#--iCMS.PageBreak--#\n');
                this.instance.focus?.();
            }
        }

        delPageBreak() {
            if (this.instance?.getValue && this.instance?.setValue) {
                const text = this.instance.getValue().replace(/#--iCMS\.PageBreak--#/g, '');
                this.instance.setValue(text);
                this.instance.focus?.();
            }
        }

        cleanup() {
            iCMS?.notify?.info?.('Markdown编辑器不需要自动排版');
        }

        destroy() {
            if (this.messageListener) {
                window.removeEventListener('message', this.messageListener);
                this.messageListener = null;
            }

            try { this.instance?.destroy?.(); } catch (e) {}
            this.instance = null;

            // 清理 iframe
            this.frame?.remove();
            this.frame = null;
        }
    }

    // require 路径配置（只需配置一次）
    let configured = false;
    function ensureConfig() {
        if (configured) return;
        configured = true;
        require.config({
            paths: {
                "vditor.admincp": iCMS.url('editor/assets/vditor/admincp/init', false),
                "vditor.usercp": iCMS.url('editor/assets/vditor/usercp/init', false),
                "vditor": "/assets/libs/editor/vditor/dist/index.min"
            },
            shim: {
                "vditor": ["css!/assets/libs/editor/vditor/dist/index.css"]
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

        const id = $el.attr('id') || 'vditor_' + Math.random().toString(36).slice(2, 11);
        $el.attr('id', id);

        require(['vditor.' + boot, 'vditor'], () => {
            const editor = new VditorEditor(id);
            $el.data('editor', editor.instance);

            bindEditorEvents();
            callback(id, editor);
        });
    };
});