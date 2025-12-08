define(function () {
    return function ($el) {
        return iCMS.api.submit("资料修改", $el, {
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
                'data[slogan]': {
                    maxlength: 120,
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
                'data[slogan]': {
                    maxlength: "自我介绍不能大于120个字符",
                },
            },
        });
    }
});