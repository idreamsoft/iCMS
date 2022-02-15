class pageFormsWizard {
    /*
     * Init Wizard Defaults
     *
     */
    static initWizardDefaults() {
        jQuery.fn.bootstrapWizard.defaults.tabClass = "nav nav-tabs";
        jQuery.fn.bootstrapWizard.defaults.nextSelector = '[data-wizard = "next"]';
        jQuery.fn.bootstrapWizard.defaults.previousSelector = '[data-wizard = "prev"]';
        jQuery.fn.bootstrapWizard.defaults.firstSelector = '[data-wizard = "first"]';
        jQuery.fn.bootstrapWizard.defaults.lastSelector = '[data-wizard = "lsat"]';
        jQuery.fn.bootstrapWizard.defaults.finishSelector = '[data-wizard = "finish"]';
        jQuery.fn.bootstrapWizard.defaults.backSelector = '[data-wizard="back"]';
    }
    /*
     * Init Validation Wizard functionality
     *
     */
    static initWizardValidation() {
        // Load default options for jQuery Validation plugin
        One.helpers("validation");

        // Get forms
        let formValidation = jQuery(".js-wizard-validation-form");

        // Init form validation on validation wizard form
        let validator = formValidation.validate({
            rules: {
                "wizard-validation-step3": {
                    required: true,
                },
                "wizard-validation-step4": {
                    required: true,
                },
                DB_HOST: { required: true },
                DB_USER: { required: true },
                DB_PASSWORD: { required: true },
                DB_NAME: { required: true },
                ADMIN_NAME: { required: true, minlength: 5 },
                ADMIN_PASSWORD: { required: true, minlength: 6 },
            },
            messages: {
                "wizard-validation-step3": "请确认服务器配置通过程序检测!",
                "wizard-validation-step4": "请确认文件权限通过程序检测!",
                DB_HOST: "请填写数据库服务器地址",
                DB_USER: "请填写数据库用户名",
                DB_PASSWORD: "请填写数据库密码",
                DB_NAME: "请填写数据库名",
                ADMIN_NAME: {
                    required: "请填写超级管理账号",
                    minlength: "管理员账号长度不小于5个字符",
                },
                ADMIN_PASSWORD: {
                    required: "请填写超级管理员密码",
                    minlength: "请设置至少6位以上带字母、数字及符号的密码",
                },
            },
            errorPlacement: (error, el) => {
                jQuery(el).addClass("is-invalid");
                var p = jQuery(el).parent();
                if (p.attr("class") == "input-group") {
                    error.insertAfter(p);
                } else {
                    p.append(error);
                }
            },
            highlight: el => {
                jQuery(el).parents(".form-group").find(".is-invalid").removeClass("is-invalid").addClass("is-invalid");
            },
            success: el => {
                jQuery(el).parents(".form-group").find(".is-invalid").removeClass("is-invalid");
                jQuery(el).remove();
            },
        });

        // Init wizard with validation
        jQuery(".js-wizard-validation").bootstrapWizard({
            tabClass: "",
            onTabShow: (tab, nav, index) => {
                var $btn = $("a", tab).attr("wizard-btn");
                $("#wizard-btn").show();
                if ($btn) {
                    $("#wizard-btn").hide();
                }
                $(".badge", tab).removeClass("badge-dark");
                $(".badge", tab).addClass("badge-primary");

                let percent = ((index + 1) / nav.find("li").length) * 100;

                // Get progress bar
                let progress = nav.parents(".block").find('[data-wizard="progress"] > .progress-bar');

                // Update progress bar if there is one
                if (progress.length) {
                    progress.css({ width: percent + 1 + "%" });
                }
            },
            onNext: (tab, nav, index) => {
                if (!formValidation.valid()) {
                    validator.focusInvalid();
                    return false;
                }
            },
            onTabClick: (tab, nav, index) => {
                jQuery("a", nav).blur();
                iCMS.notify.info('请点击 "下一步" 按钮');
                return false;
            },
        });
        // let toast = Swal.mixin({
        //     buttonsStyling: false,
        //     customClass: {
        //         confirmButton: 'btn btn-success m-1',
        //         cancelButton: 'btn btn-danger m-1',
        //         input: 'form-control'
        //     }
        // });

        jQuery("#install_btn").click(function (event) {
            event.preventDefault();

            if (formValidation.valid()) {
                Swal.fire({
                    type: "info",
                    title: "安装中，请稍候...",
                    showConfirmButton: false,
                });
                // $("#install_form").submit();
                var params = $("form").jsonArray();
                $.post(
                    "./install.php",
                    params,
                    function (json) {
                        if (json.code == "1") {
                            Swal.fire({
                                title: "安装成功",
                                text: "恭喜您！顺利安装完成。",
                                icon: "success",
                                timer: 3000,
                                onClose: function () {
                                    jQuery(".js-wizard-validation").bootstrapWizard("next");
                                },
                            }).then(result => {
                                if (result.value) {
                                    jQuery(".js-wizard-validation").bootstrapWizard("next");
                                }
                            });
                        } else {
                            if (json.code == "-1") {
                                Swal.fire({
                                    icon: "error",
                                    title: "系统出错!",
                                    html: '<div class="text-left">' + json.message + "</div>",
                                });
                            } else if (json.url == "DB") {
                                Swal.fire({
                                    icon: "error",
                                    title: "数据库出错!",
                                    html: '<div class="text-left">' + json.message + "</div>",
                                });
                            } else {
                                Swal.fire({
                                    icon: "warning",
                                    title: "安装出错!",
                                    text: json.message,
                                });
                                if (json.url) $(json.url).focus();
                            }
                            return false;
                        }
                    },
                    "json"
                );
            }
            validator.focusInvalid();
            return false;
        });
    }

    /*
     * Init functionality
     *
     */
    static init() {
        this.initWizardDefaults();
        this.initWizardValidation();
    }
}

// Initialize when page loads
jQuery(() => {
    pageFormsWizard.init();
});
