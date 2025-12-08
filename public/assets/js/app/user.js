define(["cms", "app@user"], function (cms, USER) {
    const factory = {
        manage: function () {
        },
        content: function () {
        },
        usercp_menu: function () {
            One.helpers("one-toggle-class");
            One._uiHandleNav();
        },
        //cms:user:me:avatar
        me_avatar: function ({ $el, el }) {
            var html = $el.html();
            $(document).on('cms:event:userdata', function (event) {
                event.preventDefault();
                var data = $(this).data("userdata");
                // console.log({data,html});
                var template = iCMS.utils.template({
                    openTag: "{{",
                    closeTag: "}}"
                });
                html = template.render(html, data);
                $el.html(html);
            });
        },
        //cms:user:status:0
        status_0: function (a) {
            return USER.status(a);
        },
        //cms:user:status:1
        status_1: function (a) {
            return USER.status(a);
        },
        //cms:user:follow
        follow: function ({ $el, el }) {
            var params = $el.params('userid');
            params['follow'] = $el.data('follow');
            iCMS.api.spost('user/follow', { params }, function (json) {
                $el.removeClass("btn-primary btn-alt-primary");
                if (json.data.follow) {
                    var obj = $el.getDatas('follow0');
                } else {
                    var obj = $el.getDatas('follow1');
                }
                $el.data('follow', json.data.follow);
                $el.addClass(obj.class);
                $el.html(obj.text);

                iCMS.notify.alert(json.message);
            });
        },
        report: function ({ $el, el }) {
            var params = $el.params();
            return USER.report(params);
        },
        ucard: function ({ $el, el }) {
            var userid = $el.data("userid");
            function error() {
                iCMS.notify.alert('出错了');
            }

            if (!userid) {
                return false;
            }
            var tip_index = 0;
            $el.off('mouseleave').on('mouseleave', function (event) {
                event.preventDefault();
                setTimeout(() => {
                    var show = $el.data('tip-show') || false;
                    if (!show) {
                        Layer.close(tip_index);
                    }
                }, 1500);
            });
            if ($el.data('tip-index')) {
                return;
            }
            iCMS.api.spost('user/info', { userid }, function ({ data }) {
                tip_index = Layer.open({
                    type: 4,
                    content: [data.html, el],
                    closeBtn: false,
                    area: '300px',
                    skin: 'cms-user-card',
                    shade: false,
                    resize: false,
                    fixed: false,
                    tips: [1, '#fff'],
                    success: function (layero, index) {
                        $el.data('tip-index', index);
                        $(layero).off('mouseenter').on('mouseenter', function () {
                            $el.data('tip-show', true);
                        }).off('mouseleave').on('mouseleave', function () {
                            $el.data('tip-show', false);
                            Layer.close(index);
                        });
                    },
                    end: function () {
                        $el.data('tip-index', 0);
                        tip_index = 0;
                    }
                });

            });
        },
        //cms:user:avatar 无头象
        avatar: function ({ $el, el }) {
            var $url = "/assets/img/avatar.gif";
            if ((typeof el.naturalWidth != "undefined" && el.naturalWidth == 0)
                || el.readyState == 'uninitialized') {
                $el.attr('src', $url);
            }
        },
        //cms:user:manage:modifyEmail
        manage_modifyEmail: function ({ $el }) {
            require(["./js/app/user/manage.modifyEmail"], function (factory) {
                console.log(factory);
            });
        },
        //cms:user:manage:modifyPhone
        manage_modifyPhone: function ({ $el }) {
            require(["./js/app/user/manage.modifyPhone"], function (factory) {
                console.log(factory);
            });
        },
        //cms:user:manage:modifypwd
        manage_modifypwd: function ({ $el }) {
            require(["./js/app/user/manage.modifypwd"], function (factory) {
                factory($el)
            });

        },
        //cms:user:manage:personstyle
        manage_personstyle: function ({ $el, el, event }) {
            if (event.type == 'input') {
                el.value = el.value.replace(/，/ig, ',');
                el.value = el.value.replace(/\s/ig, ',');
                el.value = el.value.replace(/,{2,}/ig, ',');
            }
        },
        //cms:user:profile
        profile: function ({ $el }) {
            require(["./js/app/user/profile"], function (factory) {
                factory($el)
            });
        },
        //cms:user:info
        info: function ({ $el }) {
        },
        //cms:user:logout
        logout: function ({ $el }) {
            iCMS.api.post('user/logout', function (json) {
                iCMS.notify.success(json.message);
                window.location.reload();
            });
        },
        //cms:user:register:form
        register_form: function ({ $el }) {
            require(["./js/app/user/register"], function (factory) {
                factory($el)
            });
        },
        findpwd: function ({ $el }) {
            require(["./js/app/user/findpwd"], function (factory) {
                
            });
        },
        //api.php?app=user&do=register
        register: function () {},
        //api.php?app=user&do=reminder
        reminder: function () {},
        //api.php?app=user&do=login
        login: function () {},

        //cms:user:login:qrcode
        login_qrcode: function ({ $el }) {
            var state = 'loginQr' + iCMS.utils.random(16);
            var qrcode = $el.find('img.qrcode');
            var src = qrcode.data('src');
            src += src.includes("?") ? "&" : "?";
            qrcode.attr("src", src + "state=" + state);
            require(["jq-timer"], function () {
                var timer = $.timer(function () {
                    var visible = $el.parent().is(':visible');
                    if (visible) {
                        iCMS.api.spost('user/loginQr', { state }, function (json) {
                            console.log(json);
                            if (json.data.userid) {
                                timer.stop();
                                window.location.reload();
                            }
                        });
                    }
                }, 1000, true);
            });
        },
        //cms:user:login:account
        login_account: function ({ $el }) {
            require(["./js/app/user/login_account"], function (factory) {
                factory($el)
            });

        },
        //cms:user:login:phone
        login_phone: function ({ $el }) {
            require(["./js/app/user/login_phone"], function (factory) {
                factory($el)
            });
        },
        //cms:user:login:dialog
        login_dialog: function ({ $el }) {
            USER.login();
        },
        
        //cms:user:goto:login
        goto_login: function ({ $el }) {
            let href = $el.attr('href')
            window.location.href = href;
            return false;
        },
        //cms:user:goto:register
        goto_register: function ({ $el }) {
            let href = $el.attr('href')
            window.location.href = href;
            return false;
        },
        //cms:user:avatar:upload
        // avatar_upload: function ({ $el, el }) {
        //     require(["files"], function (files) {
        //         files.upload.call(el);
        //     });
        // },
    }
    return factory;
});