jQuery(() => {
    let $formValidation = jQuery(".js-wizard-validation-form");

    var $email = $("#email");
    var $emailcode = $("#emailcode");

    iCMS.$i("public:emailcode:1").click(function (e) {
        e.preventDefault();
        var me = $(this);
        iCMS.tools.sendEmailCode($email.val(), me);
    });

    var $email2 = $("#email2");
    var $emailcode2 = $("#emailcode2");

    iCMS.$i("public:emailcode:2").click(function (e) {
        e.preventDefault();
        var me = $(this);
        iCMS.tools.sendEmailCode($email2.val(), me);
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
            emailcode: {
                required: true,
            },
            email2: {
                required: true,
                email: true,
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
            emailcode: "请输入邮箱验证码",
            emailcode2: "请输入邮箱验证码",
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
                checkCode($email.val(), $emailcode.val());
            } else if (currentIndex == 1) {
                checkCode($email2.val(), $emailcode2.val());
            } else {
            }
        }
    });
    function checkCode(email, code) {
        var currentIndex = wizard.bootstrapWizard("currentIndex");
        var api = iCMS.api("UserProfile");
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
