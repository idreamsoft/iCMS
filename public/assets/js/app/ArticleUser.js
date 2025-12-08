define(["cms"], function (cms) {
    return {
        publish: function ({ $el, el }) {
            console.log('fff');
        },
        publish_form: function ({ $el, el }) {
            var me = this;
            return iCMS.api.submit("Submit", $el, {
                rules: {
                    title: {
                        required: true,
                    },
                    cid: {
                        required: true,
                        min: 1,
                    },
                    body: {
                        required: true,
                    },
                    captcha: {
                        required: true,
                    },
                },
                messages: {
                    title: Lang("user:publish:empty:title"),
                    cid: Lang("user:publish:empty:cid"),
                    body: Lang("user:publish:empty:body"),
                    captcha: Lang("auth:enter_captcha_code"),
                },
            });
        },
        delete: function ({ $el, el }) {
            var $wrap = $el.closest("[data-id]");
            var id = $wrap.data("id");
            if (!confirm(Lang('msg:delete_confirm'))) {
                return false;
            }
            iCMS.api.spost('ArticleUser/delete', { id }, function (json) {
                iCMS.notify.success(json.message);
                $wrap.remove();
            }, function (json) {
                iCMS.notify.error(json.message, 3000);
            })
        }
    }
});