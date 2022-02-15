jQuery(() => {
    let $formValidation = jQuery(".js-wizard-validation-form");

    var $phone = $("#phone");
    var $captcha = $("#captcha");
    var $smscode = $("#smscode");

    iCMS.$i("public:smscode:1").click(function (e) {
        e.preventDefault();
        var me = $(this);
        if ($formValidation.validate().element($captcha)) {
            iCMS.tools.sendSMScode($phone.val(), $captcha, me);
        }
    });

    var $phone2 = $("#phone2");
    var $captcha2 = $("#captcha2");
    var $smscode2 = $("#smscode2");

    iCMS.$i("public:smscode:2").click(function (e) {
        e.preventDefault();
        var me = $(this);
        if ($formValidation.validate().element($phone) && $formValidation.validate().element($captcha)) {
            iCMS.tools.sendSMScode($phone2.val(), $captcha2, me);
        }
    });

    jQuery.fn.bootstrapWizard.defaults.tabClass = "nav nav-tabs";
    jQuery.fn.bootstrapWizard.defaults.nextSelector = '[data-wizard = "next"]';
    jQuery.fn.bootstrapWizard.defaults.previousSelector = '[data-wizard = "prev"]';
    jQuery.fn.bootstrapWizard.defaults.firstSelector = '[data-wizard = "first"]';
    jQuery.fn.bootstrapWizard.defaults.lastSelector = '[data-wizard = "lsat"]';
    jQuery.fn.bootstrapWizard.defaults.finishSelector = '[data-wizard = "finish"]';
    jQuery.fn.bootstrapWizard.defaults.backSelector = '[data-wizard="back"]';

    // Init form validation on validation wizard form
    let validator = $formValidation.validate({
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
            captcha: "请输入图形验证码",
            captcha2: "请输入图形验证码",
        },
    });
    // Init wizard with validation
    var wizard = jQuery(".js-wizard-validation").bootstrapWizard({
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
            jQuery("a", nav).blur();

            return false;
        },
    });
    $('[data-wizard="next"]').click(function (e) {
        e.preventDefault();
        if (!$formValidation.valid()) {
            validator.focusInvalid();
            return false;
        } else {
            var currentIndex = wizard.bootstrapWizard("currentIndex");
            if (currentIndex == 0) {
                checkCode($phone.val(), $smscode.val());
            } else if (currentIndex == 1) {
                checkCode($phone2.val(), $smscode2.val());
            } else {
            }
        }
    });
    function checkCode(phone, smscode) {
        var currentIndex = wizard.bootstrapWizard("currentIndex");
        var api = iCMS.api("UserProfile");
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
    $formValidation.submit(function (e) {
        e.preventDefault();
        iCMS.notify.info("修改中请稍候......");
        var api = $(this).attr("action");
        var data = $(this).jsonArray();
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
