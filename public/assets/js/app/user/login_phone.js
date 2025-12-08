define(function () {
    return function ($el) {
        return iCMS.api.submit("登录", $el, {
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
                captcha: Lang("auth:enter_captcha_code"),
            },
        });
    }
});