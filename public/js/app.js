function post($url, $data, callback) {
    iCMS.request
        .post($url, $data)
        .then(function (json) {
            if (typeof callback === "function") {
                return callback(json);
            } else {
                if (json.code) {
                    // iCMS.notify.success($title + "成功！");
                    if (json.url) {
                        window.location.href = json.url;
                    } else {
                        // window.location.reload();
                    }
                } else {
                    iCMS.notify.error(json.message);
                }
            }
        })
        .catch(function (error) {
            iCMS.notify.error(error.message);
        });
}
function submit($title, el, $validate, callback,dataCallback) {
    console.log(el, typeof el);
    if (typeof el == "string") {
        // el = $('[i="' + el + '"]');
        el = iCMS.$i(el);
    }
    if (el.length == 0) {
        return;
    }

    el.validate($validate);
    el.submit(function (e) {
        e.preventDefault();
        var $self = $(this);
        if (!$self.valid()) {
            return false;
        }
        iCMS.notify.info($title + "中请稍候......");
        var api = $self.attr("action");
        var data = $self.jsonArray();
        if (typeof dataCallback === "function") {
            data = dataCallback(data);
        }
        if (typeof callback !== "function") {
            callback = function (json) {
                console.log(json);
                if (json.code) {
                    iCMS.notify.success($title + "成功！");
                    if (json.url) {
                        if (json.url === true) {
                            window.location.reload();
                        } else {
                            window.location.href = json.url;
                        }
                    }
                } else {
                    iCMS.ui.reCaptcha();
                    iCMS.notify.error(json.message);
                    // iCMS.ui.alert(json.message, 300000);
                }
            };
        }
        post(api, data, callback);
    });
}

function callfun(s, e, pd) {
    var vars = iCMS.$iv(s);
    var fun = vars.join("_");
    var app = vars[0] + "_" + vars[1];

    console.log(vars);
    console.log(app, fun);
    if (typeof window[fun] !== "undefined") {
        if (typeof window[app] !== "undefined") {
            window[app](s, vars, e, window[fun]);
        }
        if (pd) {
            e.preventDefault();
        }
        return window[fun](s, vars, e);
    } else {
        if (typeof window[app] !== "undefined") {
            window[app](s, vars, e);
        }
    }
    return false;
}
jQuery(() => {
    (function ($) {
        var doc = $(document);
        //监听所有以event:开头的事件，并调用相关函数
        //eg  event:xx:ooo  调用 event_xx_ooo 函数
        doc.on("click", '[i^="event:"]', function (event) {
            // console.log(event,this);
            callfun(this, event, true);
        });
        //监听所有以form:开头的表单，并调用相关函数
        //eg  form:xx:ooo  调用 form_xx_ooo 函数
        doc.on("submit", '[i^="form:"]', function (event) {
            var flag = callfun(this, event, false);
            if (flag === false) {
                return false;
            }
        });
        doc.on("change", "input[i=upfile]", function (event) {
            event.preventDefault();
            $("form[i=upload]").submit();
        });

        doc.on("click", '[data-toggle="modal"],[data-modal]', function (event) {
            event.preventDefault();
            window.iCMS_MODAL = new iModal(
                {
                    width: "85%",
                    height: "600px",
                    overflow: true,
                },
                this
            );
            return false;
        });

        iUser.status().then(
            function (json) {
                iCMS.$i("user:status:1").show().addClass("visible");
                iCMS.$i("user:status:0").hide().addClass("invisible");
                iCMS.$i("user:status:avatar").attr("src", json.user.avatar);
                iCMS.$i("user:status:name").text(json.user.nickname);
                iCMS.$i("user:status:role").text(json.user.role.name);
                iCMS.$i("user:status:msgNum").text(json.message_num);
            },
            function (json) {
                iCMS.$i("user:status:1").hide().addClass("invisible");
                iCMS.$i("user:status:0").show().addClass("visible");
            }
        );
    })(jQuery);
});
