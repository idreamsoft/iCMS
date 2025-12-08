define(["cms", "tools", "bs-wizard", "validation"], function (cms, tools) {
    let $form = $(".js-wizard-validation-form");

    var $email = $("#email");
    var $captcha = $("#captcha", $form);
    var $emailcode = $("#emailcode");

    iCMS.$("event:public:emailcode:1").click(function (e) {
        e.preventDefault();
        var me = $(this);
        if ($form.validate().element($email) && $form.validate().element($captcha)) {
            tools.sendEmailCode($email.val(), $captcha, me);
        }
    });

    var $email2 = $("#email2");
    var $captcha2 = $("#captcha2", $form);
    var $emailcode2 = $("#emailcode2");

    iCMS.$("event:public:emailcode:2").click(function (e) {
        e.preventDefault();
        var me = $(this);
        if ($form.validate().element($email2) && $form.validate().element($captcha2)) {
            tools.sendEmailCode($email2.val(), $captcha2, me);
        }
    });

    $.fn.bootstrapWizard.defaults.tabClass = "nav nav-tabs";
    $.fn.bootstrapWizard.defaults.nextSelector = '[data-wizard = "next"]';
    $.fn.bootstrapWizard.defaults.previousSelector = '[data-wizard = "prev"]';
    $.fn.bootstrapWizard.defaults.firstSelector = '[data-wizard = "first"]';
    $.fn.bootstrapWizard.defaults.lastSelector = '[data-wizard = "lsat"]';
    $.fn.bootstrapWizard.defaults.finishSelector = '[data-wizard = "finish"]';
    $.fn.bootstrapWizard.defaults.backSelector = '[data-wizard="back"]';

    // Init form validation on validation wizard form
    let validator = $form.validate({
        rules: {
            emailcode: {
                required: true,
            },
            captcha: {
                required: true,
            },
            email2: {
                required: true,
                email: true,
            },
            captcha2: {
                required: true,
            },
            emailcode2: {
                required: true,
            },
        },
        messages: {
            email2: {
                required: "请输入邮箱",
                email: "邮箱格式不正确",
            },
            captcha: Lang("auth:enter_captcha_code"),
            captcha2: Lang("auth:enter_captcha_code"),
            emailcode: "请输入邮箱验证码",
            emailcode2: "请输入邮箱验证码",
        },
    });
    // Init wizard with validation
    var wizard = $(".js-wizard-validation").bootstrapWizard({
        tabClass: "",
        onTabShow: (tab, nav, index) => {
            let percent = ((index + 1) / nav.find("li").length) * 100;

            // Get progress bar
            let progress = nav.parents(".block").find('[data-wizard="progress"] > .progress-bar');

            // Update progress bar if there is one
            if (progress.length) {
                progress.css({ width: percent + 1 + "%" });
            }
        },
        onNext: (tab, nav, index) => {
            return false;
        },
        onTabClick: (tab, nav, index) => {
            $("a", nav).blur();

            return false;
        },
    });
    $('[data-wizard="next"]').click(function (e) {
        e.preventDefault();
        // if (!$form.valid()) {
        //     validator.focusInvalid();
        //     return false;
        // } else {
        var currentIndex = wizard.bootstrapWizard("currentIndex");
        if (currentIndex == 0) {
            if ($form.validate().element($email) &&
                $form.validate().element($emailcode) &&
                $form.validate().element($captcha)) {
                checkCode($email.val(), $emailcode.val());
            }
        } else if (currentIndex == 1) {
            if ($form.validate().element($email2) &&
                $form.validate().element($emailcode2) &&
                $form.validate().element($captcha2)) {
                checkCode($email2.val(), $emailcode2.val());
            }
        } else {
            if (!$form.valid()) {
                validator.focusInvalid();
                return false;
            }
        }
        // }
    });
    function checkCode(email, code) {
        var currentIndex = wizard.bootstrapWizard("currentIndex");
        var api = iCMS.api.url("UserProfile");
        var data = {
            action: "modifyEmailStep",
            email: email,
            code: code,
        };
        iCMS.request
            .post(api, data)
            .then(function (json) {
                $captcha.val("");
                iCMS.ui.reCaptcha();
                if (json.code) {
                    $("#email" + currentIndex).val(email);
                    var nextIndex = wizard.bootstrapWizard("nextIndex");
                    wizard.bootstrapWizard("show", nextIndex);
                } else {
                    iCMS.notify.error(json.message);
                }
            })
            .catch(function (error) {
                // console.log(error);
                iCMS.notify.error(error.message);
                // iCMS.ui.alert(error.message, 300000);
            });
    }
    $form.submit(function (e) {
        e.preventDefault();
        iCMS.notify.info("修改中请稍候......");
        var api = $(this).attr("action");
        var data = $(this).serializeJSON();
        iCMS.request
            .post(api, data)
            .then(function (json) {
                if (json.code) {
                    iCMS.notify.success("修改成功！");
                    if (json.url) {
                        // window.location.href = json.url;
                    } else {
                        // window.location.reload();
                    }
                } else {
                    iCMS.ui.reCaptcha();
                    iCMS.notify.error(json.message);
                }
            })
            .catch(function (error) {
                iCMS.notify.error(error.message);
            });
    });
});

