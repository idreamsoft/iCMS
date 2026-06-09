define(['vue', 'editor'], function (Vue, editor) {

    /**
     * 编辑器组件工厂函数
     * @param {Object} options - 配置选项
     * @param {string|Element} options.el - 挂载元素
     * @returns {Vue} Vue实例
     */
    return function ({ el }) {
        // 默认编辑器项配置
        const DEFAULT_ITEM = {
            body: "",
            chapterId: "",
            chapterTitle: "",
            display: 1,
            show: false
        };

        /**
         * 检查值是否未定义
         * @param {*} value - 要检查的值
         * @returns {boolean} 是否未定义
         */
        const isUndefined = (value) => {
            return typeof value === 'undefined' || value === null;
        };

        return new Vue({
            el,
            data() {
                return {
                    subtitle: '',
                    isChapter: 0,
                    editorCurrentIdx: 0,
                    editorCount: 1,
                    editorItems: [{ ...DEFAULT_ITEM }],
                    loading: false,
                    currentEditorWidget: 'ueditor'
                };
            },

            computed: {
                /**
                 * 当前激活的编辑器项
                 * @returns {Object} 当前编辑器项
                 */
                currentItem() {
                    return this.editorItems[this.editorCurrentIdx] || {};
                },

                /**
                 * 是否只有一个编辑器项
                 * @returns {boolean} 是否只有一个项
                 */
                isSingleItem() {
                    return this.editorItems.length === 1;
                }
            },

            methods: {
                /**
                 * 显示指定索引的编辑器项
                 * @param {number} idx - 编辑器项索引
                 */
                showItem(idx = this.editorCurrentIdx) {
                    if (isUndefined(idx) || idx < 0 || idx >= this.editorItems.length) {
                        console.warn(`无效的编辑器索引: ${idx}`);
                        return;
                    }

                    this.editorItems.forEach((item, index) => {
                        item.show = index === idx;
                    });

                    this.editorCurrentIdx = idx;
                },

                /**
                 * 创建新的编辑器项
                 * @param {boolean} show - 是否显示
                 * @returns {Object} 新的编辑器项
                 */
                createItem(show = true) {
                    return {
                        ...DEFAULT_ITEM,
                        show: !isUndefined(show) ? show : true
                    };
                },

                /**
                 * 初始化所有编辑器
                 */
                initEditors() {
                    this.$nextTick(() => {
                        document.querySelectorAll('.cms-editor').forEach(el => {
                            if (!el.classList.contains('js-editor-enabled')) {
                                editor.init({ el });
                            }
                        });
                    });
                },

                /**
                 * 编辑器切换事件处理
                 * @param {Event} e - 事件对象
                 */
                handleEditorChange(e) {
                    const value = parseInt(e.target.value);
                    if (!isNaN(value)) {
                        this.showItem(value);
                    }
                },

                /**
                 * 删除当前编辑器项
                 */
                async deleteEditor() {
                    try {
                        const idx = this.editorCurrentIdx;

                        if (idx < 0 || idx >= this.editorItems.length) {
                            console.warn('无效的删除索引');
                            return;
                        }

                        // 标记为不显示
                        this.$set(this.editorItems[idx], 'display', false);

                        // 延迟执行删除操作，提供视觉反馈
                        await this.$nextTick();

                        setTimeout(() => {
                            if (this.isSingleItem) {
                                // 如果只有一个项，重置为默认项
                                this.editorItems = [this.createItem()];
                            } else {
                                // 删除当前项
                                this.editorItems.splice(idx, 1);
                            }

                            // 重新选择显示项
                            this.selectNewActiveItem(idx);
                        }, 100);
                    } catch (error) {
                        console.error('删除编辑器项失败:', error);
                    }
                },

                /**
                 * 选择新的激活项
                 * @param {number} deletedIdx - 被删除项的索引
                 */
                selectNewActiveItem(deletedIdx) {
                    if (this.editorItems.length === 0) return;

                    let newIdx = 0;
                    if (deletedIdx >= 0 && deletedIdx < this.editorItems.length) {
                        newIdx = deletedIdx;
                    } else {
                        newIdx = this.editorItems.length - 1;
                    }

                    this.showItem(newIdx);
                },

                /**
                 * 加载编辑器数据
                 */
                async loadEditorData() {
                    try {
                        this.loading = true;
                        const id = $('[name="rs[id]"]').val();

                        if (!iCMS?.api?.post) {
                            throw new Error('iCMS API 不可用');
                        }

                        const response = await new Promise((resolve, reject) => {
                            iCMS.api.post("article/data", { id }, (json) => {
                                if (json && json.data) {
                                    resolve(json);
                                } else {
                                    reject(new Error('无效的响应数据'));
                                }
                            }, (error) => {
                                reject(error);
                            });
                        });

                        // 更新数据
                        this.subtitle = response.data.subtitle || '';
                        this.isChapter = response.data.isChapter || 0;
                        this.editorItems = response.data.items || [this.createItem()];

                        // 确保至少有一个项是显示的
                        if (this.editorItems.length > 0) {
                            this.showItem(0);
                        }

                        // 数据加载完成后初始化编辑器
                        this.$nextTick(() => this.initEditors());
                    } catch (error) {
                        console.error('加载编辑器数据失败:', error);
                        // 设置默认数据
                        this.editorItems = [this.createItem()];
                        this.showItem(0);
                        // 错误情况下也需要初始化编辑器
                        this.$nextTick(() => this.initEditors());
                    } finally {
                        this.loading = false;
                    }
                },

                /**
                 * 添加新的编辑器项
                 */
                addEditor() {
                    try {
                        const newItem = this.createItem();
                        const newIndex = this.editorItems.push(newItem) - 1;
                        this.showItem(newIndex);
                        // DOM 更新后初始化新编辑器，使用当前编辑器类型
                        this.$nextTick(() => {
                            const editorId = 'editor-body-' + newIndex;
                            const newTextarea = document.getElementById(editorId);
                            console.log('currentEditorWidget :>> ', this.currentEditorWidget);
                            newTextarea.setAttribute('data-widget', this.currentEditorWidget);
                            // if (newTextarea && this.currentEditorWidget !== 'ueditor') {
                            //     // if (this.currentEditorWidget === 'vditor') {
                            //     //     newTextarea.setAttribute('data-markdown', 'true');
                            //     // }
                            // }
                            this.initEditors();
                        });
                    } catch (error) {
                        console.error('添加编辑器项失败:', error);
                    }
                },

                /**
                 * 切换编辑器类型
                 * @param {Event} e - 事件对象
                 * @param {string} type - 编辑器类型: 'markdown' | 'json'
                 */
                toggleEditor(e, type) {
                    const isChecked = e.target.checked;
                    const self = this;

                    // 编辑器类型配置
                    const editorConfig = {
                        html: { widget: 'ueditor', otherCheckbox: 'isJson', attrs: {} },
                        markdown: { widget: 'vditor', otherCheckbox: 'isJson', attrs:{} },//{ 'data-markdown': 'true' }
                        json: { widget: 'jsoneditor', otherCheckbox: 'isMarkdown', attrs: {} }
                    };
                    const config = editorConfig[type];
                    const targetWidget = isChecked ? config.widget : 'ueditor';

                    // 保存当前编辑器类型，供新增编辑器时使用
                    this.currentEditorWidget = targetWidget;

                    // 互斥：取消另一个编辑器的选中状态
                    if (isChecked) {
                        const otherCheckbox = document.getElementById(config.otherCheckbox);
                        if (otherCheckbox?.checked) {
                            otherCheckbox.checked = false;
                        }
                    }

                    // 获取所有编辑器包装容器
                    document.querySelectorAll('.editor-wrap').forEach((wrap, index) => {
                        const editorId = 'editor-body-' + index;
                        const formGroup = wrap.querySelector('.editor-bodys');
                        const currentTextarea = document.getElementById(editorId);
                        const currentWidget = currentTextarea?.getAttribute('data-widget') || 'ueditor';

                        // 从iEditor实例获取内容
                        let content = '';
                        if (window.iEditor?.instances?.has(editorId)) {
                            const instance = window.iEditor.instances.get(editorId);
                            if (instance?.getContent) {
                                try {
                                    content = instance.getContent() || '';
                                } catch (e) {
                                    console.warn('[toggleEditor] 获取内容失败:', e);
                                }
                            }
                        }

                        // 从JSON编辑器切换到其他编辑器时，提取text内容
                        if (currentWidget === 'jsoneditor' && targetWidget !== 'jsoneditor' && content) {
                            try {
                                const parsed = typeof content === 'string' ? JSON.parse(content) : content;
                                if (parsed?.text) {
                                    content = parsed.text;
                                }else{
                                    content = JSON.stringify(content);
                                }
                            } catch (e) {
 
                                // JSON解析失败，如果content是对象则转为字符串
                                if (typeof content === 'object') {
                                    content = content.text || JSON.stringify(content);
                                }
                            }
                        }

                        // 切换到JSON编辑器时，将HTML内容包装为文本对象
                        if (targetWidget === 'jsoneditor' && currentWidget !== 'jsoneditor' && content) {
                            // content = JSON.stringify({ text: content }, null, 2);
                            try {
                                const parsed = typeof content === 'string' ? JSON.parse(content) : content;
                                if (typeof parsed === 'string') {
                                    content = JSON.stringify({ text: content }, null, 2);
                                }else{
                                    content = parsed;
                                }
                            } catch (e) {
 
                                // JSON解析失败，如果content是对象则转为字符串
                                if (typeof content === 'object') {
                                    content = content.text || JSON.stringify(content);
                                }
                            }
                        }

                        console.log(`[toggleEditor] ${editorId}: ${currentWidget} -> ${targetWidget}, 内容长度: ${content.length}`);

                        // 销毁当前编辑器
                        if (window.iEditor?.instances?.has(editorId)) {
                            window.iEditor.destroy(editorId);
                        }

                        // 清空容器并创建新textarea
                        formGroup.innerHTML = '';
                        const newTextarea = document.createElement('textarea');
                        newTextarea.type = 'text/plain';
                        newTextarea.id = editorId;
                        newTextarea.name = 'body[]';
                        newTextarea.className = 'cms-editor';
                        newTextarea.value = content;
                        newTextarea.innerHTML = content;
                        newTextarea.setAttribute('data-widget', targetWidget);

                        // 设置额外属性
                        if (isChecked) {
                            Object.entries(config.attrs).forEach(([key, val]) => newTextarea.setAttribute(key, val));
                        }

                        formGroup.appendChild(newTextarea);

                        // 初始化编辑器并设置内容
                        self.$nextTick(() => {
                            editor.init({ el: newTextarea });
                            setTimeout(() => {
                                if (content && window.iEditor?.instances?.has(editorId)) {
                                    window.iEditor.setContent(editorId, content);
                                }
                            }, 500);
                        });
                    });

                    const messages = {
                        markdown: isChecked ? '已切换到Markdown编辑器' : '已切换到富文本编辑器',
                        json: isChecked ? '已切换到JSON编辑器' : '已切换到富文本编辑器'
                    };
                    iCMS.notify.info(messages[type]);
                },

                // 兼容旧方法调用
                toggleMarkdownEditor(e) { this.toggleEditor(e, 'markdown'); },
                toggleJsonEditor(e) { this.toggleEditor(e, 'json'); }
            },

            async mounted() {
                try {
                    //  this.$nextTick();
                    await this.loadEditorData();
                    // setTimeout(async () => {
                    // this.loadEditorData();
                    console.log('组件已挂载完成');
                    // }, 1000);
                } catch (error) {
                    console.error('组件挂载失败:', error);
                }
            },

            // 移除空的 updated 钩子
            beforeDestroy() {
                // 清理工作
                this.editorItems = [];
            }
        });
    };
});