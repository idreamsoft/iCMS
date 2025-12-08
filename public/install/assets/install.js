define(['bs-wizard', 'validation'], function () {

    return function () {
        $.fn.bootstrapWizard.defaults.tabClass = "nav nav-tabs";
        $.fn.bootstrapWizard.defaults.nextSelector = '[data-wizard = "next"]';
        $.fn.bootstrapWizard.defaults.previousSelector = '[data-wizard = "prev"]';
        $.fn.bootstrapWizard.defaults.firstSelector = '[data-wizard = "first"]';
        $.fn.bootstrapWizard.defaults.lastSelector = '[data-wizard = "lsat"]';
        $.fn.bootstrapWizard.defaults.finishSelector = '[data-wizard = "finish"]';
        $.fn.bootstrapWizard.defaults.backSelector = '[data-wizard="back"]';
        // Get forms
        let $form = $(".js-wizard-validation-form");

        // Init form validation on validation wizard form
        let validator = $form.validate({
            rules: {
                "wizard-validation-step3": {
                    required: true,
                },
                "wizard-validation-step4": {
                    required: true,
                },
                DB_HOST: {
                    required: function (ele) {
                        let driver = $('[name="DB_DRIVER"]:checked').val();
                        return driver == 'mysql';
                    }
                },
                DB_USER: {
                    required: function (ele) {
                        let driver = $('[name="DB_DRIVER"]:checked').val();
                        return driver == 'mysql';
                    }
                },
                DB_PASSWORD: {
                    required: function (ele) {
                        let driver = $('[name="DB_DRIVER"]:checked').val();
                        return driver == 'mysql';
                    }
                },
                DB_NAME: {
                    required: function (ele) {
                        let driver = $('[name="DB_DRIVER"]:checked').val();
                        return driver == 'mysql';
                    }
                },
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
                $(el).addClass("is-invalid");
                var p = $(el).parent();
                if (p.attr("class") == "input-group") {
                    error.insertAfter(p);
                } else {
                    p.append(error);
                }
            },
            highlight: el => {
                $(el).parents(".form-group").find(".is-invalid").removeClass("is-invalid").addClass("is-invalid");
            },
            success: el => {
                $(el).parents(".form-group").find(".is-invalid").removeClass("is-invalid");
                $(el).remove();
            },
        });
        $('[name="DB_DRIVER"]').change(function () {
            if (this.value == 'mysql') {
                $("#mysql_db").show();
            }
            if (this.value == 'sqlite') {
                $("#mysql_db").hide();
            }
        });
        // $form.submit(function (e) {
        //     e.preventDefault();
        //     var $form = $(this);
        //     if (!$form.valid()) {
        //         return false;
        //     }
        //     console.log('sdf');
        // });
        // Modern step progress update function
        function updateStepProgress(currentIndex, totalSteps) {
            // Update step items
            $('.step-item').each(function(index) {
                const $item = $(this);
                $item.removeClass('active completed');
                
                if (index < currentIndex) {
                    $item.addClass('completed');
                } else if (index === currentIndex) {
                    $item.addClass('active');
                }
            });
            
            // Update progress line
            const progressPercent = totalSteps > 1 ? (currentIndex / (totalSteps - 1)) * 100 : 0;
            $('.step-progress-line::before').css('width', progressPercent + '%');
            
            // Add CSS animation for progress line
            const style = document.createElement('style');
            style.textContent = `
                .step-progress-line::before {
                    width: ${progressPercent}% !important;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Init wizard with validation
        $(".js-wizard-validation").bootstrapWizard({
            tabClass: "",
            onTabShow: (tab, nav, index) => {
                var $next = $("a", tab).data("next");
                var $prev = $("a", tab).data("prev");

                if ($next === false) {
                    $('[data-wizard="next"]', "#wizard-btn").hide();
                } else {
                    $('[data-wizard="next"]', "#wizard-btn").show();
                }
                if ($prev === false) {
                    $('[data-wizard="prev"]', "#wizard-btn").hide();
                } else {
                    $('[data-wizard="prev"]', "#wizard-btn").show();
                }

                $(".badge", tab).removeClass("bg-dark");
                $(".badge", tab).addClass("bg-primary");

                // Update modern step progress
                const totalSteps = nav.find("li").length;
                updateStepProgress(index, totalSteps);
                
                // Add smooth scroll to top when changing steps
                $('html, body').animate({
                    scrollTop: $('.install-wizard').offset().top - 50
                }, 500);

                let percent = ((index + 1) / nav.find("li").length) * 100;

                // Get progress bar
                let progress = nav.parents(".block").find('[data-wizard="progress"] > .progress-bar');

                // Update progress bar if there is one
                if (progress.length) {
                    progress.css({ width: percent + 1 + "%" });
                }
            },
            onNext: (tab, nav, index) => {
                console.log(index);
                if (index == 5) {
                    if (!$form.valid()) {
                        validator.focusInvalid();
                        return false;
                    }
                }
            },
            onTabClick: (tab, nav, index) => {
                $("a", nav).blur();
                // Add modern notification with better styling
                if (typeof iCMS !== 'undefined' && iCMS.notify) {
                    iCMS.notify.info('请点击 "下一步" 按钮');
                } else {
                    // Fallback notification
                    const notification = $('<div class="modern-notification">请点击 "下一步" 按钮</div>');
                    $('body').append(notification);
                    setTimeout(() => notification.fadeOut(() => notification.remove()), 3000);
                }
                return false;
            },
        });
        $("#install-btn").click(function (event) {
            event.preventDefault();
            if ($form.valid()) {
                iCMS.notify.info('安装中，请稍候...');
                var params = $form.serializeJSON();
                iCMS.request.post("./install.php", params)
                    .then(function (json) {
                        iCMS.notify.info("安装完成，更新缓存......");
                        return iCMS.request.post("./cache.php", { hash: json.data.hash });
                    }).then(function (json) {
                        $(".js-wizard-validation").bootstrapWizard("next");
                        iCMS.ui.success("恭喜您！顺利安装完成。");
                    }).catch(function (json) {
                        iCMS.notify.error(json.message);
                        if (json.url) $(json.url).focus();
                    });
            }
            validator.focusInvalid();
            return false;
        });
    }
});