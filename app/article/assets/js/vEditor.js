define(['vue'], function (Vue) {
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
                    loading: false
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
                    } catch (error) {
                        console.error('加载编辑器数据失败:', error);
                        // 设置默认数据
                        this.editorItems = [this.createItem()];
                        this.showItem(0);
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
                    } catch (error) {
                        console.error('添加编辑器项失败:', error);
                    }
                }
            },

             mounted() {
                try {
                    //  this.$nextTick();
                    // await this.loadEditorData();
                    // setTimeout(async () => {
                        this.loadEditorData();
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