define(["cms"], function (cms) {
    return {
        dialog_index: 0,
        dialog: function ({ $el, el }) {
            var me = this;
            var params = $el.params('userid');
            iCMS.ui.dialogTpl(['message', 'dialog.send', 1], {
                params,
                title: Lang('Send message'),
                success: function (layero, index) {
                    me.dialog_index = index;
                    $(layero).on('click', '[name="cancel"]', function () {
                        Layer.close(index)
                    });
                }
            });
        },
        send: function ({ $el, el }) {
            var me = this;
            return iCMS.api.submit("发送", $el, {
                rules: {
                    content: {
                        required: true,
                        maxlength: 1024,
                    }
                },
                messages: {
                    content: {
                        required: "请填写私信内容",
                        maxlength: "最多输入1024字符"
                    }
                }
            }, function () {
                el.reset();
                var isNotify = $el.data('notify');
                isUndef(isNotify) && (isNotify=true);
                if(isNotify){
                    iCMS.notify.success("发送成功！");
                    Layer.close(me.dialog_index);
                }
            });
        }
    }
});