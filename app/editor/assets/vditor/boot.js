define(["ui"], function (UI) {
    // 定义核心编辑器管理对象
    const editor = {
        // 当前编辑器实例
        instance: null,
        // 当前编辑器ID
        editorId: null,
        // 消息监听器引用
        messageListener: null,

        /**
         * 获取编辑器内容
         * @returns {string} 编辑器内容，失败时返回空字符串
         */
        getContent() {
            if (!this.instance || typeof this.instance.getValue !== 'function') {
                console.warn('[editor] 当前编辑器实例不存在或不支持 getValue 方法');
                return "";
            }
            try {
                const content = this.instance.getValue();
                // 返回非空且非仅换行符的内容
                return content && content !== "\n" ? content : "";
            } catch (error) {
                console.error('[editor] 获取编辑器内容失败:', error);
                return "";
            }
        },

        /**
         * 检查编辑器是否有内容
         * @returns {boolean} 是否有内容
         */
        hasContents() {
            return Boolean(this.getContent());
        },

        /**
         * 获取当前编辑器实例
         * @returns {Object|null} 当前编辑器实例，如果不存在则返回null
         */
        get() {
            return this.instance;
        },

        /**
         * 创建编辑器实例
         * @param {string} eid - 编辑器将要绑定的DOM元素ID
         * @returns {Object|null} 创建的编辑器实例，失败时返回null
         */
        create(eid) {
            this.editorId = eid || 'editor';
            // 销毁已存在的实例（如果有的话）
            if (this.instance) {
                this.destroy();
            }

            const element = $("#" + eid);

            if (!element.length) {
                console.error(`[editor] 创建编辑器失败: 找不到ID为 ${eid} 的DOM元素`);
                return null;
            }

            // 创建 iframe
            const frame = $(`<iframe 
                src="/assets/libs/editor/vditor/editor.html" 
                frameborder="0" 
                scrolling="no" 
                style="width: 100%; height: 100%; border: none;"
                name="${eid}"
            ></iframe>`);

            // 将原元素ID修改，然后在其后插入iframe
            element.attr('id', eid + '_ta').after(frame).hide();

            // 从全局对象获取编辑器实例 (假设 VditorEditor 是一个全局对象)
            const ed = window.VditorEditor?.[eid];

            if (ed) {
                // 存储实例和ID
                this.instance = ed;
            } else {
                // console.error(`[editor] 创建编辑器失败: 未能从 VditorEditor 获取到 ${eid} 的实例`);
                // 清理刚创建的 iframe
                // frame.remove();
                // throw new Error(`[editor] 创建编辑器失败: 未能从 VditorEditor 获取到 ${eid} 的实例`);
                // return null;
            }

            // 为当前编辑器设置消息监听器
            this._setupMessageListener(frame);

            return ed;
        },

        /**
         * 设置当前编辑器的iframe高度消息监听器
         * @param {jQuery} frame - iframe jQuery对象
         * @private
         */
        _setupMessageListener(frame) {
            // 移除旧的监听器（如果存在）
            if (this.messageListener) {
                window.removeEventListener('message', this.messageListener);
            }

            // 创建新的监听器
            this.messageListener = (event) => {
                if (event.data?.height && typeof event.data.height === 'number') {
                    frame.height(event.data.height);
                }
                this.instance = window.VditorEditor?.[this.editorId];
            };

            window.addEventListener('message', this.messageListener);
        },

        /**
         * 销毁当前编辑器实例
         */
        destroy() {
            if (this.instance && typeof this.instance.destroy === 'function') {
                try {
                    this.instance.destroy();
                } catch (error) {
                    console.error('[editor] 销毁编辑器实例时发生错误:', error);
                }
            }

            // 移除消息监听器
            if (this.messageListener) {
                window.removeEventListener('message', this.messageListener);
                this.messageListener = null;
            }

            // 清理实例和ID引用
            this.instance = null;
            if (this.editorId) {
                // 尝试找到并移除对应的iframe容器
                const iframeId = this.editorId + '_iframe';
                const iframeElement = $("#" + iframeId);
                if (iframeElement.length) {
                    iframeElement.remove();
                }

                // 尝试找到并恢复原容器元素ID (如果之前修改过)
                const containerId = this.editorId + '_container';
                const containerElement = $("#" + containerId);
                if (containerElement.length) {
                    containerElement.attr('id', this.editorId); // 恢复原始ID
                }
                this.editorId = null;
            }
        },

        /**
         * 插入分页符
         */
        insPageBreak() {
            if (!this.instance) {
                console.warn('[editor] 当前编辑器实例不存在，无法插入分页符');
                return;
            }

            if (typeof this.instance.insertValue !== 'function') {
                console.error('[editor] 当前编辑器实例不支持 insertValue 方法，无法插入分页符');
                return;
            }

            try {
                // 插入分页符标记，前后加换行符以确保格式正确
                this.instance.insertValue("\n#--iCMS.PageBreak--#\n");
                // 如果有focus方法，调用它以保持焦点
                if (typeof this.instance.focus === 'function') {
                    this.instance.focus();
                }
            } catch (error) {
                console.error('[editor] 向编辑器插入分页符失败:', error);
            }
        },

        /**
         * 删除分页符
         */
        delPageBreak() {
            if (!this.instance) {
                console.warn('[editor] 当前编辑器实例不存在，无法删除分页符');
                return;
            }

            if (typeof this.instance.getValue !== 'function' || typeof this.instance.setValue !== 'function') {
                console.error('[editor] 当前编辑器实例不支持 getValue 或 setValue 方法，无法删除分页符');
                return;
            }

            try {
                // 获取当前内容
                let text = this.instance.getValue();
                // 使用正则表达式全局替换所有分页符标记
                text = text.replace(/#--iCMS\.PageBreak--#/g, "");
                // 设置更新后的内容
                this.instance.setValue(text);
                // 如果有focus方法，调用它以保持焦点
                if (typeof this.instance.focus === 'function') {
                    this.instance.focus();
                }
            } catch (error) {
                console.error('[editor] 从编辑器删除分页符失败:', error);
            }
        },

        /**
         * 清理格式（Markdown编辑器不需要此操作）
         */
        cleanup() {
            // Markdown编辑器通常不处理HTML格式，因此清理格式意义不大
            // 使用 iCMS 的通知系统（如果可用）提示用户
            if (iCMS?.notify?.info) {
                iCMS.notify.info("Markdown编辑器不需要自动排版");
            } else {
                console.info("Markdown编辑器不需要自动排版");
            }
        },

        /**
         * 获取当前编辑器的配置（如果Vditor支持）
         * @returns {Object|null} 配置对象或null
         * 注意：此方法依赖于Vditor是否提供获取配置的API
         */
        getConfig() {
            if (this.instance && typeof this.instance.getConfig === 'function') {
                return this.instance.getConfig();
            }
            console.warn('[editor] 当前编辑器实例可能不支持获取配置');
            return null;
        }
    };

    /**
     * 编辑器初始化函数
     * @param {jQuery} $el - jQuery元素 (容器元素)
     * @param {string} boot - 启动类型 (e.g., "admincp", "usercp")
     * @param {Function} callback - 初始化完成后的回调函数，接收 (eid, editorWrapper) 参数
     * @returns {void}
     */
    return function ($el, boot, callback) {
        // 参数验证
        if (!$el || !$el.length) {
            console.error('[editor] 初始化失败: 编辑器容器元素不存在或为空');
            return;
        }

        if (!boot) {
            console.error('[editor] 初始化失败: 启动类型 (boot) 不能为空');
            return;
        }

        if (typeof callback !== 'function') {
            console.error('[editor] 初始化失败: 回调函数 (callback) 必须是一个函数');
            return;
        }

        // 配置 RequireJS 路径和shim
        require.config({
            paths: {
                // 直接定义为admincp会出现冲突
                "vditor.admincp": iCMS.url('editor/assets/vditor/admincp/init', false),
                "vditor.usercp": iCMS.url('editor/assets/vditor/usercp/init', false),
                "vditor": "/assets/libs/editor/vditor/dist/index.min"
            },
            shim: {
                "vditor": [
                    "css!/assets/libs/editor/vditor/dist/index.css"
                ]
            }
        });

        const bootModule = `vditor.${boot}`;

        // 加载 Vditor 配置和主库
        require([bootModule, 'vditor'], function (/* config, Vditor */) { // config 未在此处使用，但可能在特定配置中需要
            try {
                const id = $el.attr("id");

                if (!id) {
                    id = 'vditor_' + Math.random().toString(36).slice(2, 11);
                    $el.attr('id', id);
                }

                // 为当前编辑器创建一个独立的编辑器对象副本
                // 这样每个编辑器实例都可以独立管理其状态
                const newEd = Object.assign({}, editor);

                // 调用创建方法
                newEd.instance = newEd.create(id);
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
            } catch (error) {
                console.error('[editor] 初始化过程中发生未预期的错误:', error);
            }
        }, function (error) {
            console.error('[editor] 模块加载失败:', error);
        });
    };
});