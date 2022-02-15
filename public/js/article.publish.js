jQuery(() => {
    (function ($) {
        var $articlePublish = $('[i="article:publish"]');
        var $captcha2 = $("#captcha2");
        var validator2 = $UserAccountSignin.validate({
            rules: {
                account: {
                    required: true,
                    minlength: 6,
                },
                password: {
                    required: true,
                    minlength: 6,
                },
                captcha: {
                    required: true,
                },
            },
            messages: {
                account: {
                    required: "请输入您的账号",
                    minlength: "账号不能少于6个字符",
                },
                password: {
                    required: "请输入您的密码",
                    minlength: "密码不能少于6个字符",
                },
                captcha: "请输入图形验证码",
            },
        });
        $UserAccountSignin.submit(function (e) {
            e.preventDefault();
            if (!$(this).valid()) {
                return false;
            }
            iCMS.notify.info("登录中请稍候......");
            var api = $(this).attr("action");
            var data = $(this).jsonArray();
            iCMS.request
                .post(api, data)
                .then(function (json) {
                    if (json.code) {
                        iCMS.notify.success("登录成功！");
                        if (json.url) {
                            window.location.href = json.url;
                        } else {
                            window.location.reload();
                        }
                    } else {
                        iCMS.notify.error(json.message);
                        $captcha2.val("");
                        iCMS.ui.reCaptcha();
                    }
                })
                .catch(function (error) {
                    // console.log(error);
                    iCMS.notify.error(error.message);
                    // iCMS.ui.alert(error.message, 300000);
                });
        });
    })(jQuery);
});
