define(["cms", "storage"], function (cms, Storage) {
    const factory = {
        cache: {},
        login: function (json) {
            iCMS.ui.dialogTpl(['user', 'dialog.user.login'],{
                title: Lang('User Login')
            });
        },
        report: function (params) {
            iCMS.ui.dialogTpl(['user', 'dialog.report'], {
                title: Lang('请选择举报理由'),
                btn: ['举报', '关闭'],
                success: function (layero, index, that) {
                    layero.on('change', '[name="reason"]', function () {
                        var value = $(this).val();
                        var rc = $('.reason-content', layero)
                        value > 0 ? rc.hide() : rc.show();
                    });
                },
                yes: function (index, layero) {
                    var cEl = $('[name="content"]', layero);
                    var checked = $('[name="reason"]:checked', layero);
                    var content = cEl.val();
                    var reason = checked.val();
                    if (!reason && content == '') {
                        var msg = cEl.attr('placeholder') || Lang('请填写举报原因');
                        cEl.focus();
                        iCMS.notify.alert(msg);
                        return;
                    }
                    iCMS.api.post('user/report', { params, content, reason }, function (json) {
                        iCMS.notify.alert(json.message);
                        Layer.close(index);
                    });
                }
            });
        },
        status: function (param) {
            //避免多次执行
            if (!factory.cache['status']) {
                factory.cache['status'] = true;
                var s1 = iCMS.$("cms:user:status:1");
                var s0 = iCMS.$("cms:user:status:0");

                iCMS.api.spost('user/status',
                    function ({ data }) {
                        // console.log(s1);
                        s1.show();
                        s0.hide();
                        iCMS.$("cms:user:info").data(data)
                            .addClass('cms-ui-template')
                            .trigger('cms:event:template');
                        $(document).data("userdata", data)
                            .trigger('cms:event:userdata');
                        Storage.set('userdata', data);
                    },
                    function (json) {
                        s1.hide();
                        s0.show();
                    });
            }
        },
        timeline: function () {
            var params = $('[name="params"]').data("params");
            iCMS.api.spost('user/timeline', { params }, function (json) {
                $.each(json.data, function (ikey, val) {
                    if (val) {
                        var $el = iCMS.$(ikey);

                        $("i", $el).removeClass('fa')
                            .removeClass('far')
                            .removeClass('text-primary')
                            .addClass("fa text-primary");
                    }
                });
            });
        }
    }
    return factory;
});