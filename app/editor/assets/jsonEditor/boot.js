define(['ui'], function (UI) {
    "use strict";

    // JSON编辑器包装类
    class JsonEditor {
        constructor(id, config, JE) {
            this.id = id;
            this.config = config;
            this.instance = null;
            this._create(JE);
        }

        _create(JE) {
            try {
                this.instance = JE.create({
                    container: '#' + this.id,
                    templateApiUrl: iCMS.url('editorJson/templates', false),
                    onChange: (value) => console.log('Changed:', value)
                });
                // this.instance.setTemplateApiUrl('/api/editorJson/ccc');
            } catch (e) {
                console.error('[JsonEditor] 创建失败:', e);
            }
        }

        getContent() { return this.instance?.getValue() ?? ''; }
        setContent(content) { this.instance?.setValue(content); }
        hasContents() { return !!this.instance?.getJsonString(2); }

        // JSON编辑器不支持这些操作，保留空实现以兼容接口
        execCommand() {}
        insPageBreak() {}
        delPageBreak() {}
        cleanup() {}

        destroy() {
            if (!this.instance) return;
            setTimeout(() => {
                try { this.instance.destroy(); } catch (e) {}
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
                "jsoneditor.admincp": iCMS.url('editor/assets/jsoneditor/admincp/init', false),
                "jsoneditor.usercp": iCMS.url('editor/assets/jsoneditor/usercp/init', false),
                "jsoneditor.simple": iCMS.url('editor/assets/jsoneditor/simple/init', false),
                "jsoneditor": "/assets/libs/editor/jsoneditor/dist/json-editor.umd"
            },
            shim: {
                "jsoneditor": {
                    deps: ["css!/assets/libs/editor/jsoneditor/dist/json-editor.css"],
                    exports: "jsoneditor"
                }
            }
        });
    }

    // 引导函数
    return function ($el, boot, callback) {
        ensureConfig();

        const id = $el.attr('id') || 'editor_' + Math.random().toString(36).slice(2, 11);
        $el.attr('id', id);

        require(['jsoneditor.' + boot, 'jsoneditor'], (config, JE) => {
            const editor = new JsonEditor(id, config, JE);
            $el.data('editor', editor.instance);
            callback(id, editor);
        });
    };
});