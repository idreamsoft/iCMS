function event_public_captcha(self, vars, e) {
    iCMS.ui.reCaptcha(self);
}

function event_vote_good(self, vars, e) {
    var $self = $(self);
    var $wrap = $self.closest("[data-param]");
    var $param = $wrap.data("param");
    var $api = iCMS.api("vote");
    var $data = {
        action: "add",
        event: "good",
        param: $param,
    };
    console.log($api, $data);

    post($api, $data, function (json) {
        if (json.code) {
            var numObj = iCMS.$i("vote:good:num", $wrap),
                count = parseInt(numObj.text()) || 0;
            numObj.text(count + json.data[0]);

            if ($self.hasClass("btn")) {
                if (json.data[0] > 0) {
                    $self.removeClass();
                    $self.addClass("btn btn-primary");
                } else {
                    $self.removeClass();
                    $self.addClass("btn btn-light");
                }
            }
        } else {
            iCMS.notify.info(json.message);
        }
    });
}
function event_favorite_add(self, vars, e) {
    var $self = $(self);
    var $wrap = $self.closest("[data-param]");
    var $param = $wrap.data("param");
    var $api = iCMS.api("favorite");
    var $data = {
        action: "add",
        param: $param,
    };
    console.log($api, $data);

    post($api, $data, function (json) {
        if (json.code) {
            if ($self.hasClass("btn")) {
                if (json.data[0] > 0) {
                    $self.removeClass();
                    $self.addClass("btn btn-danger");
                } else {
                    $self.removeClass();
                    $self.addClass("btn btn-light");
                }
            }
        } else {
            iCMS.notify.info(json.message);
        }
    });
}

function event_public_smscode(self, vars, e) {
    var $self = $(self);
    var $form = $self.closest("form");
    var $phone = $('[name="phone"]', $form);
    var $captcha = $('[name="captcha"]', $form);

    if ($form.validate().element($phone) && $form.validate().element($captcha)) {
        iCMS.request
            .post(iCMS.api("public"), {
                action: "smscode",
                phone: $phone.val(),
                captcha: $captcha.val(),
            })
            .then(function (json) {
                if (json.code) {
                    iCMS.notify.success("短信验证码已发送");
                    $self.attr("disabled", true);
                    var sTime = 60;
                    var timer = setInterval(function () {
                        $self.text(sTime + "s 后可重发");
                        sTime--;
                        console.log(sTime);
                        if (sTime < 0) {
                            // $captcha.val("");
                            iCMS.ui.reCaptcha();
                            $self.text("获取短信验证码");
                            $self.removeAttr("disabled");
                            clearInterval(timer);
                        }
                    }, 1000);
                } else {
                    // $captcha.val("");
                    iCMS.ui.reCaptcha();
                    iCMS.notify.error(json.message);
                }
            })
            .catch(function (error) {
                iCMS.notify.error(error.message);
            });
    }
}

function form_search(self, vars, e) {
    var $form = $(self);
    var $keyword = $('[name="keyword"]', $form);
    var placeholder = $keyword.attr("placeholder");
    if (!$keyword.val()) {
        iCMS.notify.warning(placeholder || "请输入关键词");
        $keyword.focus();
        return false;
    }
}
function event_vote_comment_up(self, vars, e) {
    var $self = $(self);
    var $wrap = $self.closest("[data-param]");
    var $param = $wrap.data("param");
    var $api = iCMS.api("vote");
    var $data = {
        action: "add",
        event: "up",
        param: $param,
    };
    console.log($api, $data);
    post($api, $data, function (json) {
        console.log(json);
        if (json.code) {
            var numObj = iCMS.$i("vote:comment:up:num", $wrap),
                count = parseInt(numObj.text()) || 0;
            numObj.text(count + json.data[0]);

            if ($self.hasClass("btn")) {
                if (json.data[0] > 0) {
                    $self.removeClass("btn-alt-primary");
                    $self.addClass("btn-primary");
                } else {
                    $self.removeClass("btn-primary");
                    $self.addClass("btn-alt-primary");
                }
            }
        } else {
            iCMS.notify.info(json.message);
        }
    });
}
function event_vote_comment_down(self, vars, e) {
    var $self = $(self);
    var $wrap = $self.closest("[data-param]");
    var $param = $wrap.data("param");
    var $api = iCMS.api("vote");
    var $data = {
        action: "add",
        event: "down",
        param: $param,
    };
    console.log($api, $data);
    post($api, $data, function (json) {
        console.log(json);
        if (json.code) {
            if ($self.hasClass("btn")) {
                if (json.data[0] > 0) {
                    $self.removeClass("btn-alt-primary");
                    $self.addClass("btn-primary");
                } else {
                    $self.removeClass("btn-primary");
                    $self.addClass("btn-alt-primary");
                }
            }
        } else {
            iCMS.notify.info(json.message);
        }
    });
}

function event_comment_load(self, vars, e) {
    var $self = $(self);
    var $wrap = $self.closest("[data-param]");
    var $param = $wrap.data("param");

    var $api = iCMS.api(vars[1], "html");
    var $id = [vars[1], "wrap", $param["iid"], $param["appid"]].join("_");
    var $div = $("#" + $id);
    if ($div.length > 0) {
        $div.slideUp(300, function () {
            $div.remove();
        });
        return;
    }
    var $data = {
        name: "event_comment_load",
        userid: $param["userid"],
        iid: $param["iid"],
        appid: $param["appid"],
    };
    post($api, $data, function (json) {
        if (json.code) {
            var $div = $('<div class="row"></div>');
            $div.attr("id", $id);
            $div.html(json.data.html);
            $wrap.parent().after($div);
        } else {
        }
    });
}
function event_comment_list(self, vars, e) {
    var $self = $(self);
    var $wrap = $self.closest("[data-param]");
    var $param = $wrap.data("param");
    console.log($param);
    var $api = iCMS.api("comment", "list");
    var $id = ["comment_list", $param["iid"], $param["appid"]].join("_");
    var $div = $("#" + $id);
    if ($div.length > 0) {
        $div.slideUp(300, function () {
            $div.remove();
        });
        return;
    }
    var $data = {
        userid: $param["userid"],
        iid: $param["iid"],
        appid: $param["appid"],
    };
    post($api, $data, function (json) {
        if (json.code) {
            var $div = $('<div class="row"></div>');
            $div.attr("id", $id);
            $div.html(json.data.html);
            $wrap.parent().after($div);
        } else {
        }
    });
}

function event_user_report(self, vars, e) {
    var $self = $(self);
    var $wrap = $self.closest("[data-param]");
    var $param = $wrap.data("param");
    var $api = iCMS.api("user");
    var $data = {
        action: "report",
        param: $param,
    };

    var d = $("#iCMS-REPORT-DIALOG");d.modal("show");
    var c = $('[name="content"]', d);
    $('[name="reason"]:not(.js-reason-enabled)', d).addClass('js-reason-enabled').on("click", function () {
        var cv = $(this).data('content');
        if (this.value==0) {
            c.parent().removeClass("d-none");
            c.val('');
        } else {
            c.parent().addClass("d-none");
            c.val(cv);
        }
    });
    $('button[type="submit"]',d).data("param",$param);
    $('button[type="submit"]:not(.js-reason-submit)',d).addClass('js-reason-submit').on("click", function () {
        $data['reason'] = $('[name="reason"]:checked').val();
        $data['content'] = c.val();
        $data['param'] = $(this).data("param");
        if(!$data['reason']){
            return iCMS.notify.warning("请选择举报理由");
        }
        if($data['reason']=='其他' && !$data['content']){
            return iCMS.notify.warning("请填写举报原因");
        }
        post($api, $data, function (json) {
            if (json.code) {
                d.modal("hide");
                iCMS.notify.success(json.message);
            } else {
                iCMS.notify.info(json.message);
            }
        });
    });
  


}
