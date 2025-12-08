define(["cms", "vue"], function (cms, Vue) {
    const factory = {
        $widget: {},
        vote: function ($el, event, callback) {
            // var $parent = $el.parents('[data-index]');
            // var params = $el.params();
            // var index = $parent.data('index');
            // var commentItem = factory.LISTVM.commentList[index];
            // iCMS.api.spost('comment/vote', { params, event }, function ({ data }) {
            //     commentItem.is_down = data.is_down;
            //     commentItem.is_up = data.is_up;
            //     commentItem.up = data.up;
            // });
        },
        //cms:comment:vote:up
        vote_up: function ({ $el, el }) {
            this.vote($el, 'up');
        },
        vote_down: function ({ $el, el }) {
            this.vote($el, 'down');
        },
        replyEvent: function ($el, event, callback) {
            // var $parent = $el.parents('[data-index]');
            // var params = $el.params();
            // var pindex = $parent.data('pindex');
            // var index = $parent.data('index');
            // var commentItem = factory.LISTVM.commentList[pindex];
            // var replyItem = commentItem.replyList[index];
            // iCMS.api.spost('comment/vote', { params, event }, function ({ data }) {
            //     replyItem.is_down = data.is_down;
            //     replyItem.is_up = data.is_up;
            //     replyItem.up = data.up;
            // });
        },
        reply_up: function ({ $el, el }) {
            this.replyEvent($el, 'reply_up');
        },
        reply_down: function ({ $el, el }) {
            this.replyEvent($el, 'reply_down');
        },
        //cms:comment:reply:page     
        reply_page: function ({ $el, el }) {
            // var params = $el.params();
            // var page = $el.data("pageno");
            // var pindex = $el.data("pindex");
            // console.log({ params, page });
            // var replyList = factory.LISTVM.commentList.rows[pindex].replyList;
            // iCMS.api.spost('comment/replyList', { params, page }, function (json) {
            //     // replyList.page = json.data.page;
            //     // replyList.pageData = json.data.pageData;
            //     // replyList.rows = replyList.rows.concat(json.data.rows);
            // });
        },
        //cms:comment:user:reply:delete     
        user_reply_delete: function ({ $el, el }) {
            var $wrap = $el.closest("[data-id]");
            var id = $wrap.data("id");
            if (!confirm(Lang('Confirm to delete?'))) {
                return false;
            }
            iCMS.api.spost('CommentUser/reply_delete', { id }, function (json) {
                iCMS.notify.success(json.message);
                $wrap.remove();
            }, function (json) {
                iCMS.notify.error(json.message, 3000);
            })
        },
        //cms:comment:user:delete   
        user_delete: function ({ $el, el }) {
            var $wrap = $el.closest("[data-id]");
            var id = $wrap.data("id");
            if (!confirm(Lang('Confirm to delete?'))) {
                return false;
            }
            iCMS.api.spost('CommentUser/delete', { id }, function (json) {
                iCMS.notify.success(json.message);
                $wrap.remove();
            }, function (json) {
                iCMS.notify.error(json.message, 3000);
            })
        },
        //cms:comment:user:manage     
        user_manage: function ({ $el, el }) {
            $sel = $(".btn-showall");
            $sel.click(function (e) {
                e.preventDefault();
                var pp = $(this).closest('.comment_content');
                if (pp.hasClass('showall')) {
                    pp.removeClass('showall');
                } else {
                    pp.addClass('showall');
                }

            });
            // $sel.toggle();
        },
        //cms:comment:vpage     
        vpage: function ({ $el, el }) {
            var url = $el.attr('href');
            var pn = $el.data("pageno");
            require(["js/app/comment.vlist"], function (vlist) {
                vlist.load(pn);
            })
        },
        //cms:comment:vlist     
        vlist: function ({ $el, el }) {
            this.$widget['vlist'] = $el;
            require(["js/app/comment.vlist"], function (vlist) {
                vlist.get({ $el, el });
            })
        },
        //cms:comment:reply:box
        reply_box: function ({ $el, el }) {
            this.$widget['reply_box'] = $el;
            // this.REPLY_BOX_EL = $el.clone(true).show().removeClass("hide");
        },
        /**
         * cms:comment:reply
         * 添加评论
         * @param {object} param
         */
        reply: function ({ $el, el }) {

        },
        reply_form: function ({ $el, el }) {
            return iCMS.api.submit("回复", $el, {
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
                    captcha: Lang("auth:enter_captcha_code"),
                },
            }, {
                success: function () {
                    el.reset();
                    // require(["js/app/comment.vlist"], function (vlist) {
                    //     vlist.load(1);
                    // })
                }
            });
        },

        form: function ({ $el, el }) {
            let that = this;
            return iCMS.api.submit("评论", $el, {
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
                    captcha: Lang("auth:enter_captcha_code"),
                },
            }, {
                success: function () {
                    el.reset();
                    var $vlist = that.$widget['vlist'];
                    if ($vlist.length > 0) {
                        require(["js/app/comment.vlist"], function (vlist) {
                            vlist.load(1);
                        })
                    }
                }
            });
        },
    }
    return factory;
});