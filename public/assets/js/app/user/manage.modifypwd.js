define(function () {
    return function ($el) {
        return iCMS.api.submit("密码修改", $el, {
            rules: {
                password: {
                    required: true,
                    minlength: 6,
                },
                passwd: {
                    required: true,
                    minlength: 6,
                },
                "passwd-confirm": {
                    required: true,
                    equalTo: "#passwd",
                },
                captcha: {
                    required: true,
                },
            },
            messages: {
                password: {
                    required: "请输入您的密码",
                    minlength: "密码不能少于6个字符",
                },
                passwd: {
                    required: "请输入您的新密码",
                    minlength: "新密码不能少于6个字符",
                },
                "passwd-confirm": {
                    required: "请确认您的密码",
                    minlength: "确认密码不能少于6个字符",
                    equalTo: "确认密码与您的密码不一致",
                },
                captcha: Lang("auth:enter_captcha_code"),
            },
        });
    }
});