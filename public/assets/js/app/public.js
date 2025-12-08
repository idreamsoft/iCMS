define(["cms", "tools"], function(cms, tools) {
    return {
        //cms:public:page
        page: function({ $el }) {
            console.log(page);
        },
        //cms:public:captcha
        // captcha: function ({ $el }) {
        //     $el.off('click').on('click', function (event) {
        //         event.preventDefault();
        //         iCMS.ui.reCaptcha(this);
        //     });
        // },
        //cms:public:smscode
        smscode: function({ $el }) {
            var $form = $el.closest("form");
            var $phone = $('[name="phone"]', $form);
            var $captcha = $('[name="captcha"]', $form);

            function validate() {
                return $form.validate().element($phone) &&
                    $form.validate().element($captcha);
            }

            // $el.on("click", function (event) {
            if (validate()) {
                var phone = $phone.val();
                tools.sendSMScode(phone, $captcha, $el);
            }
            // });
        }
    }
});