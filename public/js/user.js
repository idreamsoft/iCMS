(function ($) {
    class iUser {
        constructor() {
            var ime = this;
            this.url = new (function () {
                this.home = function () {};
            })();
            this.auth = function () {
                return Cookies.get(iCMS.$CONFIG.AUTH) ? true : false;
            };
            this.noavatar = function (img) {
                img.src = iCMS.$CONFIG.PUBLIC + "/img/avatar.gif";
            };
            this.nocover = function (img, type) {
                var name = "coverpic";
                if (type == "m") {
                    // name = 'm_coverpic';
                    name = "coverpic";
                }
                img.src = iCMS.$CONFIG.PUBLIC + "/img/" + name + ".jpg";
            };
            this.ui = new (function () {})();
            this.logout = function () {
                iCMS.$i("user:logout").click(function (event) {
                    event.preventDefault();
                    var api = iCMS.api("user", "logout");
                    iCMS.request
                        .get(api)
                        .then(function (json) {
                            if (json.code) {
                                iCMS.notify.success(json.message);
                                window.location.reload();
                            } else {
                                iCMS.notify.alert(json.message);
                            }
                        })
                        .catch(function (error) {
                            console.log(error);
                            // iCMS.ui.alert(error.message, 300000);
                        });
                });
            };
            this.status = function () {
                return new Promise(function (resolve, reject) {
                    var api = iCMS.api("user");
                    var $data = {
                        action: "status",
                    };
                    iCMS.request
                        .post(api, $data)
                        .then(function (json) {
                            // console.log(json);
                            if (json.code) {
                                resolve(json);
                            } else {
                                reject(json);
                            }
                        })
                        .catch(function (error) {
                            console.log(error);
                            // iCMS.ui.alert(error.message, 300000);
                        });
                    // me.send(url, param, resolve, reject, "GET");
                });
            };

            this.check = new (function () {
                this.login = function () {
                    var auth = ime.auth();
                    if (auth) {
                        return true;
                    } else {
                        return ime.toLogin();
                    }
                };
            })();

            this.toLogin = function () {
                window.location.href = iCMS.api("user", "login");
            };
            $(function () {
                ime.logout();
            });
        }
    }
    window.iUser = new iUser();
})(jQuery);

$(function () {
    One.helpers("validation");
    jQuery.validator.addMethod(
        "isMobile",
        function (value, element) {
            var length = value.length;
            var mobile = /^(13[0-9]{9})|(18[0-9]{9})|(14[0-9]{9})|(17[0-9]{9})|(15[0-9]{9})$/;
            return this.optional(element) || (length == 11 && mobile.test(value));
        },
        "请正确填写手机号码"
    );
});
