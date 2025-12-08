define(["validation"], function () {
    return function () {
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
                    required: Lang('auth:enter_account'),
                    minlength: Lang('msg:minlength', 'auth:account', 5),
                },
                iPassWord: {
                    required: Lang('auth:enter_password'),
                    minlength: Lang('msg:minlength', 'auth:password', 6),
                },
                captcha: {
                    required: Lang('auth:enter_captcha'),
                },
            },
        });
        $("form").submit(function (e) {
            e.preventDefault();
            if (!$(this).valid()) {
                return false;
            }
            iCMS.notify.info("auth:login:processing");
            var $api = $(this).attr("action");
            var $data = $(this).serializeJSON();
            iCMS.request.post($api, $data)
                .then(function (json) {
                    localStorage.setItem('admin_token', json.data.token);
                    localStorage.setItem('admin_expire', json.data.expire);
                    localStorage.setItem('admin_info', JSON.stringify(json.data.info));
                    iCMS.notify.success("auth:login:success");
                    window.location.reload();
                }).catch(function (json) {
                    iCMS.ui.reCaptcha();
                    iCMS.notify.warning(json.message || "auth:login:error", 3000);
                });
            return false;
        });
    };
});