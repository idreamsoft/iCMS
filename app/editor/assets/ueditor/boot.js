define(['ui'], function (UI) {
    const editor = {
        // 默认编辑器ID
        editorId: "editor",
        // 编辑器实例
        instance: null,
        // 编辑器配置
        config: null,

        /**
         * 获取编辑器内容
         * @returns {string} 编辑器内容
         */
        getContent() {
            return this.instance ? this.instance.getContent() : '';
        },

        /**
         * 设置编辑器内容
         * @param {string} content - 要设置的内容
         * @param {boolean} [flag=false] - 是否追加内容（默认false，即写入内容,true为追加内容）
         */
        setContent(content, flag = false) {
            if (this.instance && typeof this.instance.setContent === 'function') {
                try {
                    this.instance.setContent(content, flag);
                } catch (e) {
                    console.error(`[editor] 内容设置失败:`, e);
                }
            }
        },

        /**
         * 检查编辑器是否有内容
         * @returns {boolean} 是否有内容
         */
        hasContents() {
            return this.instance ? this.instance.hasContents() : false;
        },

        /**
         * 获取编辑器实例
         * @returns {Object|null} 编辑器实例
         */
        get() {
            return this.instance;
        },

        /**
         * 创建编辑器实例
         * @param {Object} config - 编辑器配置
         * @returns {Object} 编辑器实例
         */
        create(eid, config = {}) {
            this.editorId = eid || 'editor';
            this.config = config;
            // 先销毁已存在的编辑器
            if (this.instance) {
                this.destroy();
            }

            try {
                UE.delEditor(this.editorId);
                this.instance = UE.getEditor(this.editorId, config);
                return this.instance;
            } catch (error) {
                console.error(`创建编辑器失败:`, error);
                return null;
            }
        },

        /**
         * 销毁编辑器实例
         */
        destroy() {
            if (this.instance) {
                setTimeout(() => {
                    try {
                        UE.delEditor(this.editorId);
                        this.instance = null;
                        // this.config = null;
                        // this.editorId = null;
                    } catch (error) {
                        console.error('销毁编辑器失败:', error);
                    }
                }, 200);
            }
        },

        /**
         * 执行编辑器命令
         * @param {string} cmd - 命令名称
         */
        execCommand(cmd) {
            if (this.instance) {
                this.instance.execCommand(cmd);
                this.instance.focus();
            } else {
                console.warn('编辑器实例不存在，无法执行命令');
            }
        },

        /**
         * 插入分页符
         */
        insPageBreak() {
            if (this.instance) {
                this.instance.execCommand('pagebreak');
                this.instance.focus();
            } else {
                console.warn('编辑器实例不存在，无法插入分页符');
            }
        },

        /**
         * 删除分页符
         */
        delPageBreak() {
            if (this.instance) {
                let html = this.instance.getContent();
                html = html.replace(/#--iCMS\.PageBreak--#/g, '');
                this.instance.setContent(html);
                this.instance.focus();
            } else {
                console.warn('编辑器实例不存在，无法删除分页符');
            }
        },

        /**
         * 清理编辑器内容格式
         */
        cleanup() {
            if (!this.instance) {
                iCMS.ui.alert("没找到可用编辑器");
                return;
            }

            try {
                const html = this.instance.getContent();
                const formattedHtml = typeof format === 'function' ? format(html) : html;
                this.instance.setContent(formattedHtml);
                this.instance.focus();
            } catch (error) {
                console.error('清理编辑器内容失败:', error);
                iCMS.ui.alert("清理编辑器内容失败");
            }
        },

        /**
         * 获取编辑器实例
         * @returns {Object|null} 编辑器实例
         */
        getInstance() {
            return this.instance;
        },

        /**
         * 获取编辑器配置
         * @returns {Object|null} 编辑器配置
         */
        getConfig() {
            return this.config;
        },

        /**
         * 销毁编辑器实例
         */
        destroyAll() {
            this.destroy();
        }
    };

    return function ($el, boot, callback) {
        require.config({
            "paths": {
                "ueditor.admincp": iCMS.url('editor/assets/ueditor/admincp/init', false),
                "ueditor.usercp": iCMS.url('editor/assets/ueditor/usercp/init', false),
                "ueditor.simple": iCMS.url('editor/assets/ueditor/simple/init', false),
                "ZeroClipboard": "/assets/libs/editor/ueditor/dist/third-party/zeroclipboard/ZeroClipboard",
                "ueditor": "/assets/libs/editor/ueditor/dist/ueditor.all.min"
            }
        });

        const configKey = "ueditor." + boot;

        require(['ZeroClipboard', configKey, 'ueditor'], function (ZeroClipboard, config) {
            window['ZeroClipboard'] = ZeroClipboard;
            let id = $el.attr("id");
            if (!id) {
                id = 'editor_' + Math.random().toString(36).slice(2, 11);
                $el.attr('id', id);
            }
            if (id) {
                const newEd = Object.assign({}, editor);
                newEd.create(id, config);
                $el.data("editor", newEd.instance);
                UI.on('click', '.js-editor-insPB', function () {
                    newEd.insPageBreak();
                });
                UI.on('click', '.js-editor-delPB', function () {
                    newEd.delPageBreak();
                });
                UI.on('click', '.js-editor-merge', function () {
                    // editor.cleanup();
                });
                UI.on('click', '.js-editor-cleanup', function () {
                    newEd.cleanup();
                });
                callback(id, newEd);
            }
        });
    };
});