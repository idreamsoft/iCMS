$(function () {
    One.helpers("validation");
    iCMS.ui.reCaptcha(0,"click");
    $("form").validate({
        rules: {
            iAccount: {
                required: true,
                minlength: 5,
            },
            iPassWord: {
                required: true,
                minlength: 6,
            },
            captcha: {
                required: true,
            },
        },
        messages: {
            iAccount: {
                required: "请输入管理员账号",
                minlength: "管理员账号长度不小于5个字符",
            },
            iPassWord: {
                required: "请输入管理员密码",
                minlength: "管理员密码长度不小于6个字符",
            },
            captcha: {
                required: "请输入验证码",
            },
        },
    });
    $("form").submit(function (e) {
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
                    window.location.reload();
                } else {
                    iCMS.ui.reCaptcha();
                    iCMS.notify.error(json.message || "账号或密码错误！",3000);
                }
            })
            .catch(function (error) {
                console.log(error);
            });
        return false;
    });
});
