jQuery(() => {
    // Load default options for jQuery Validation plugin
    One.helpers("validation");

    var $form = $("#iCMS-feedback");
    var $title = $("#title");
    var $content = $("#content");
    var $captcha = $("#captcha");
    // Init Form Validation
    var $fvd = $form.validate({
        rules: {
            title: {
                required: true,
                minlength: 12,
            },
            captcha: {
                required: true,
            },
        },
        messages: {
            title: {
                required: "请填写问题的标题",
                minlength: "问题的标题不能少于12个字符",
            },
            captcha: "请输入图形验证码",
        },
    });
    $form.submit(function (e) {
        e.preventDefault();
        if ($fvd.valid()) {
            iCMS.notify.info("问题提交中请稍候......");
            var $data = {
                title: $title.val(),
                content: $content.val(),
                captcha: $captcha.val(),
            };
            iCMS.request
                .post($ADMINCP_URL+"=developer&do=bugs&CSRF_TOKEN=" + $CSRF_TOKEN, $data)
                .then(function (json) {
                    if (json.code) {
                        iCMS.notify.success(json.message);
                        $captcha.val("");
                        $title.val("");
                        iCMS.ui.reCaptcha();
                    } else {
                        iCMS.notify.error(json.message);
                        $captcha.val("");
                        iCMS.ui.reCaptcha();
                    }
                })
                .catch(function (error) {
                    console.log(error);
                    iCMS.ui.alert(error.message || error.responseText, 300000);
                });
        }
    });
});