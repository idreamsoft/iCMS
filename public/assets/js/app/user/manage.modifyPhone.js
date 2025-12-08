define(["cms", "tools", "bs-wizard", "validation"], function (cms, tools) {
    let $form = $(".js-wizard-validation-form");
    var $phone = $("#phone", $form);
    var $captcha = $("#captcha", $form);
    var $smscode = $("#smscode", $form);
    iCMS.$("event:public:smscode:1").click(function (e) {
        e.preventDefault();
        var me = $(this);
        if ($form.validate().element($captcha)) {
            tools.sendSMScode($phone.val(), $captcha, me);
        }
    });

    var $phone2 = $("#phone2", $form);
    var $captcha2 = $("#captcha2", $form);
    var $smscode2 = $("#smscode2", $form);

    iCMS.$("event:public:smscode:2").click(function (e) {
        e.preventDefault();
        var me = $(this);
        if ($form.validate().element($phone2) && $form.validate().element($captcha2)) {
            tools.sendSMScode($phone2.val(), $captcha2, me);
        }
    });

    let validator = $form.validate({
        rules: {
            smscode: {
                required: true,
            },
            captcha: {
                required: true,
            },
            phone2: {
                required: true,
                number: true,
                minlength: 11,
                isMobile: true,
            },

            smscode2: {
                required: true,
            },
            captcha2: {
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
            smscode2: "请输入短信验证码",
            captcha: Lang("auth:enter_captcha_code"),
            captcha2: Lang("auth:enter_captcha_code"),
        },
    });

    $.fn.bootstrapWizard.defaults.tabClass = "nav nav-tabs";
    $.fn.bootstrapWizard.defaults.nextSelector = '[data-wizard = "next"]';
    $.fn.bootstrapWizard.defaults.previousSelector = '[data-wizard = "prev"]';
    $.fn.bootstrapWizard.defaults.firstSelector = '[data-wizard = "first"]';
    $.fn.bootstrapWizard.defaults.lastSelector = '[data-wizard = "lsat"]';
    $.fn.bootstrapWizard.defaults.finishSelector = '[data-wizard = "finish"]';
    $.fn.bootstrapWizard.defaults.backSelector = '[data-wizard="back"]';

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
            if ($form.validate().element($phone) &&
                $form.validate().element($captcha) &&
                $form.validate().element($smscode)) {
                checkCode($phone.val(), $smscode.val());
            }
        } else if (currentIndex == 1) {
            if ($form.validate().element($phone2) &&
                $form.validate().element($captcha2) &&
                $form.validate().element($smscode2)) {
                checkCode($phone2.val(), $smscode2.val());
            }
        } else {
            if (!$form.valid()) {
                validator.focusInvalid();
                return false;
            }
        }
        // }
    });


    // Init form validation on validation wizard form

    function checkCode(phone, smscode) {
        var currentIndex = wizard.bootstrapWizard("currentIndex");
        var api = iCMS.api.url("UserProfile");
        var data = {
            action: "modifyPhoneStep",
            phone: phone,
            smscode: smscode,
        };
        iCMS.request
            .post(api, data)
            .then(function (json) {
                $captcha.val("");
                iCMS.ui.reCaptcha();
                if (json.code) {
                    $("#phone" + currentIndex).val(phone);
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

