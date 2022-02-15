jQuery(() => {
    (function ($) {
        submit("注册", "form:user:register", {
            rules: {
                account: {
                    required: true,
                    minlength: 6,
                },
                phone: {
                    required: true,
                    number: true,
                    minlength: 11,
                    isMobile: true,
                },
                email: {
                    required: true,
                    email: true,
                },
                password: {
                    required: true,
                    minlength: 6,
                },
                "password-confirm": {
                    required: true,
                    equalTo: "#password",
                },
                smscode: {
                    required: true,
                },
                captcha: {
                    required: true,
                },
                "signup-terms": {
                    required: true,
                },
            },
            messages: {
                account: {
                    required: "请输入您的账号",
                    minlength: "账号不能少于6个字符",
                },
                phone: {
                    required: "请输入手机号",
                    number: "手机号只能输入数字",
                    minlength: "手机号不能少于11个字符",
                    isMobile: "请填写正确的手机号码",
                },
                email: {
                    required: "请输入邮箱",
                    email: "邮箱格式不正确",
                },
                password: {
                    required: "请输入您的密码",
                    minlength: "密码不能少于6个字符",
                },
                "password-confirm": {
                    required: "请确认您的密码",
                    minlength: "确认密码不能少于6个字符",
                    equalTo: "确认密码与您的密码不一致",
                },
                smscode: "请输入短信验证码",
                captcha: "请输入图形验证码",
                "signup-terms": "请同意网站服务条款!",
            },
        });
    })(jQuery);
});
