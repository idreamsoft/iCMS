
jQuery(() => {
    (function ($) {
        submit("登录", "form:user:login:account", {
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
        submit("登录", "form:user:login:phone", {
            rules: {
                phone: {
                    required: true,
                    number: true,
                    minlength: 11,
                    isMobile: true,
                },
                smscode: {
                    required: true,
                },
                captcha: {
                    required: true,
                },
            },
            messages: {
                phone: {
                    required: "请输入手机号",
                    number: "手机号只能输入数字",
                    minlength: "手机号不能少于11个字符",
                    isMobile: "请填写正确的手机号码",
                },
                smscode: "请输入短信验证码",
                captcha: "请输入图形验证码",
            },
        });
    })(jQuery);
});
