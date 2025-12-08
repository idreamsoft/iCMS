define(function () {
    return function ($el) {
        return iCMS.api.submit("账号注册", $el, {
            rules: {
                account: {
                    required: true,
                    minlength: 6,
                    remote: {
                        url: iCMS.url('/UserPassport/check'), // 远程地址
                        type: "post" // 请求类型
                    }
                },
                phone: {
                    required: true,
                    number: true,
                    minlength: 11,
                    isMobile: true,
                    remote: {
                        url: iCMS.url('/UserPassport/check'), // 远程地址
                        type: "post" // 请求类型
                    }
                },
                email: {
                    required: true,
                    email: true,
                    remote: {
                        url: iCMS.url('/UserPassport/check'), // 远程地址
                        type: "post" // 请求类型
                    }
                },
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
                smscode: {
                    required: true,
                },
                "signup-terms": {
                    required: true,
                    // required: function (ele) {
                    //     return $(ele).prop("checked");
                    // }
                },
                captcha: {
                    required: true,
                },
            },
            messages: {
                account: {
                    required: "请输入您的账号",
                    minlength: "账号不能少于6个字符",
                    remote: "该账号已经注册过了",
                },
                phone: {
                    required: "请输入手机号",
                    number: "手机号只能输入数字",
                    minlength: "手机号不能少于11个字符",
                    isMobile: "请填写正确的手机号码",
                    remote: "该手机号已经注册过了",
                },
                email: {
                    required: "请输入邮箱",
                    email: "邮箱格式不正确",
                    remote: "该邮箱已经注册过了",
                },
                password: {
                    required: "请输入您的密码",
                    minlength: "密码不能少于6个字符",
                },
                passwd: {
                    required: "请输入您的新密码",
                    minlength: "新密码不能少于6个字符",
                },
                "signup-terms": {
                    required: "请我同意iCMS网站服务条款",
                },
                "passwd-confirm": {
                    required: "请确认您的密码",
                    minlength: "确认密码不能少于6个字符",
                    equalTo: "确认密码与您的密码不一致",
                },
                smscode: "请输入短信验证码",
                captcha: Lang("auth:enter_captcha_code"),
            },
        });
    }
});