define(["admincp", "vue"], function (admincp, Vue) {
    const factory = {
        config_vued: function ({ el }) {
            new Vue({
                el,
                data: {
                    verify_phone: false,
                    verify_email: false,
                    register_mode: ['account'],
                    auto_register: false,
                    qrcode_auto_register: false,
                    login_mode: ['account'],
                },
                mounted: function () {
                    var self = this;
                    iCMS.api.get('user/config', function (json) {
                        self.register_mode = json.data.register.mode || [];
                        self.verify_phone = self.register_mode.includes("phone");
                        self.verify_email = self.register_mode.includes("email");
                        self.login_mode = json.data.login.mode || [];
                        self.auto_register = json.data.login.auto_register || 0;
                        self.qrcode_auto_register = json.data.login.qrcode_auto_register|| json.data.login.mode.includes("qrcode") || 0;
                    });
                },
                watch: {
                    register_mode: function (val) {
                        var checked = $("#user_register_enable").prop("checked");
                        if (val.length < 1 && checked) {
                            iCMS.ui.alert('开放注册时至少需要一种注册方式');
                        }
                    },
                    login_mode: function (val) {
                        var checked = $("#user_login_enable").prop("checked");
                        if (val.length < 1 && checked) {
                            iCMS.ui.alert('开放登录时至少需要一种登录方式');
                        }
                    }
                }
            })
        },
    }
    return factory;
});