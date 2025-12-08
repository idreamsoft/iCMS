define(function () {
    return function ($el) {
        return iCMS.api.submit("登录", $el, {
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
                captcha: Lang("auth:enter_captcha_code"),
            },
        });
    }
});