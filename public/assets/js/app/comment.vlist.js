/**
 * <!--{iCMS:comment:vlist}-->
 * 评论列表组件 - 优化版本
 * @author iCMS
 * @version 2.0
 */
define(["cms", "vue"], function (cms, Vue) {
    const appParams = $APP.params || {};

    const factory = {
        vm: null,
        /**
         * 获取通用参数
         * @returns {Object} vars的对象
         */
        getVars() {
            const $vlist = iCMS.$("cms:comment:vlist");
            return $vlist.getDatas('vars');
        },

        /**
         * 检查分页是否有效
         * @param {number} pn 页码
         * @param {Object} pageData 分页数据
         * @returns {boolean} 是否有效
         */
        _isValidPage(pn, pageData) {
            return pn <= pageData.pageTotal || pageData.pageTotal === 0;
        },

        /**
         * 更新Vue实例数据
         * @param {Object} data 响应数据
         * @param {boolean} isConcat 是否合并数据
         */
        _updateVmData(data, isConcat = false) {
            if (!factory.vm || !data) return;

            factory.vm.page = data.page;
            factory.vm.pageData = data.pageData;

            if (isConcat) {
                factory.vm.commentList = factory.vm.commentList.concat(data.rows);
            } else {
                factory.vm.commentList = data.rows;
            }
        },

        /**
         * 加载更多评论
         * @param {number} pn 页码
         * @param {string} url API地址
         */
        loadmore(pn, url = 'comment/vlist') {
            const vars = this.getVars();
            const pageData = factory.vm?.pageData;

            if (!pageData) return;

            pn = pn || pageData.current + 1;

            if (!this._isValidPage(pn, pageData)) {
                return;
            }

            iCMS.api.spost(url, { params: appParams, vars, pn }, (json) => {
                if (json?.data) {
                    this._updateVmData(json.data, true);
                }
            });
        },

        /**
         * 加载评论列表
         * @param {number} pn 页码
         * @param {string} url API地址
         */
        load(pn = 1, url = 'comment/vlist') {
            const vars = this.getVars()
            const pageData = factory.vm?.pageData;

            if (pageData) {
                pn = pn || pageData.current + 1;

                if (!this._isValidPage(pn, pageData)) {
                    return;
                }
            }

            iCMS.api.spost(url, { params: appParams, vars, pn }, (json) => {
                if (json?.data) {
                    this._updateVmData(json.data, false);
                }
            });
        },
        /**
         * 初始化Vue组件
         * @param {Object} options 包含$el和el的选项对象
         */
        get({ $el, el }) {
            const vars = this.getVars()

            iCMS.api.spost('comment/vlist', { params: appParams, vars }, (json) => {
                if (!json?.data) return;

                $el.show();
                factory.vm = new Vue({
                    el,
                    methods: {
                        /**
                         * 通用投票方法
                         * @param {Object} item 评论项
                         * @param {string} event 投票类型
                         * @param {string} url API地址
                         */
                        _vote(item, event, url = 'comment/vote') {
                            if (!item?.params) return;

                            iCMS.api.spost(url, { params: item.params, event }, ({ data }) => {
                                if (data) {
                                    this.$set(item, 'is_down', data.is_down);
                                    this.$set(item, 'is_up', data.is_up);
                                    this.$set(item, 'up', data.up);
                                }
                            });
                        },

                        /**
                         * 用户举报
                         * @param {Object} item 评论项
                         */
                        user_report(item) {
                            if (!item?.params) return;

                            require(["app@user"], (factory) => {
                                factory.report(item.params);
                            });
                        },

                        /**
                         * 评论点赞
                         * @param {Object} item 评论项
                         */
                        comment_up(item) {
                            this._vote(item, 'up');
                        },

                        /**
                         * 评论点踩
                         * @param {Object} item 评论项
                         */
                        comment_down(item) {
                            this._vote(item, 'down');
                        },
                        /**
                         * 创建回复表单
                         * @param {Object} item 评论项
                         * @param {Function} success 成功回调
                         * @returns {jQuery} 表单元素
                         */
                        _createReplyForm(item, success) {
                            if (!item?.id || !item?.params) return null;

                            const selector = "cms@comment:reply:vform";
                            const $vlist = iCMS.$("cms:comment:vlist");

                            // 移除已存在的表单
                            iCMS.$(selector, $vlist).remove();

                            // 克隆并显示表单
                            const form = iCMS.$(selector).clone(true).show().removeClass("hide");

                            // 设置表单数据
                            $('[name="id"]', form).val(item.id);
                            $('[name="params"]', form).val(JSON.stringify(item.params));

                            // 表单验证配置
                            const validationConfig = {
                                rules: {
                                    content: {
                                        required: true,
                                        minlength: 10,
                                    },
                                    captcha: {
                                        required: true,
                                    },
                                },
                                messages: {
                                    content: {
                                        required: "请输入您的评论",
                                        minlength: "评论不能少于10个字符",
                                    },
                                    captcha: "请输入图形验证码",
                                },
                            };

                            iCMS.api.submit("回复", form, validationConfig, { success });
                            return form;
                        },

                        /**
                         * 回复评论
                         * @param {Object} item 评论项
                         * @param {Event} event 事件对象
                         */
                        comment_reply(item, event) {
                            if (!item?.replyList) return;

                            const $btn = $(event.target);
                            const $wrap = $btn.parents(iCMS.$s("comment:item"));

                            // 切换回复表单显示状态
                            item.replyList.show = !item.replyList.show;
                            this.isFormVisible = item.replyList.show;

                            if (this.isFormVisible) {
                                const $form = this._createReplyForm(item, (json) => {
                                    if (!$form) return;

                                    $form[0].reset();

                                    // 更新回复计数
                                    if (json?.data?.status === 1) {
                                        item.reply_count++;
                                    }

                                    // 移除表单并加载新回复
                                    $form.fadeOut(() => {
                                        $form.remove();
                                    });

                                    this.reply_loadmore(item, 0, json?.data?.id);
                                });

                                if ($form) {
                                    $wrap.find(iCMS.$s("reply:form")).append($form);
                                }
                            }
                        },

                        /**
                         * 回复评论的评论
                         * @param {Object} replyItem 回复项
                         * @param {Object} item 主评论项
                         * @param {Event} event 事件对象
                         */
                        reply_reply(replyItem, item, event) {
                            const $btn = $(event.target);
                            const $wrap = $btn.parents(iCMS.$s("reply:item"));

                            const $form = this._createReplyForm(replyItem, (json) => {
                                if (!$form) return;

                                $form[0].reset();
                                $form.fadeOut(() => {
                                    $form.remove();
                                });

                                this.reply_loadmore(item, 0, json?.data?.id);
                            });

                            if ($form) {
                                $wrap.find(iCMS.$s("reply2:form")).append($form);
                            }
                        },
                        /**
                         * 回复投票
                         * @param {Object} replyItem 回复项
                         * @param {string} event 投票类型
                         */
                        reply_vote(replyItem, event) {
                            this._vote(replyItem, event, 'commentReply/vote');
                        },

                        /**
                         * 回复点赞
                         * @param {Object} item 回复项
                         */
                        reply_like(item) {
                            this.reply_vote(item, 'reply_up');
                        },

                        /**
                         * 回复点赞
                         * @param {Object} item 回复项
                         */
                        reply_up(item) {
                            this.reply_vote(item, 'reply_up');
                        },

                        /**
                         * 回复点踩
                         * @param {Object} item 回复项
                         */
                        reply_down(item) {
                            this.reply_vote(item, 'reply_down');
                        },
                        /**
                         * 加载更多回复
                         * @param {Object} item 评论项
                         * @param {number} page 页码
                         * @param {string} id 回复ID
                         */
                        reply_loadmore(item, page, id) {
                            if (!item?.params || !item?.replyList) return;

                            const { params, replyList } = item;
                            const row = vars?.reply_row;
                            const pageData = replyList.pageData;

                            // 确定页码
                            if (typeof page === 'undefined' || page === null) {
                                page = pageData?.current ? pageData.current + 1 : 1;
                            }

                            // 检查页码有效性
                            if (pageData && page > pageData.pageTotal) {
                                return;
                            }

                            iCMS.api.spost('commentReply/vlist', { params: params, page, row, id }, (json) => {
                                if (!json?.data) return;

                                const data = json.data;

                                // 更新分页信息（仅在非特定ID请求时）
                                if (!id) {
                                    replyList.page = data.page;
                                    replyList.pageData = data.pageData;
                                }

                                // 更新回复列表
                                if (page === 1 && !id) {
                                    // 首页加载
                                    replyList.rows = data.rows || [];
                                } else if (id && data.rows?.length > 0) {
                                    // 添加新回复到顶部
                                    replyList.rows.unshift(data.rows[0]);
                                } else {
                                    // 加载更多
                                    replyList.rows = replyList.rows.concat(data.rows || []);
                                }
                            });
                        },

                        /**
                         * 格式化日期
                         * @param {string|Date} date 日期
                         * @param {string} format 格式
                         * @returns {string} 格式化后的日期
                         */
                        format_date(date, format) {
                            return typeof format_date === 'function' ? format_date(date, format) : date;
                        }
                    },
                    data: {
                        isFormVisible: true,
                        config: json.data.config || {},
                        page: json.data.page || {},
                        pageData: json.data.pageData || {},
                        commentList: json.data.rows || []
                    }
                });
            });
        }
    };

    return factory;
});