//回复评论
function event_comment_reply(self, vars, e) {
    var $self = $(self);
    var $wrap = $self.closest("[data-param]");
    var $param = $wrap.data("param");
    console.log($wrap);
    var $reply = iCMS.$i("form:comment:reply",$wrap);
    console.log($reply.length);
    if($reply.length>0){
        $reply.remove();
        return;
    }
    var $form = iCMS.$i("form:comment:reply").clone("true");
    // $('[name="action"]', $form).val('reply');
    $('[name="param"]', $form).val(JSON.stringify($param));
    $('[name="content"]', $form).attr("placeholder", "回复" + $param.username);
    $form.show();
    $form.appendTo($wrap);
    iCMS.ui.reCaptcha($(".captcha-img", $wrap));
}

function event_comment_reply_all(self, vars, e) {
    var $self = $(self);
    var $wrap = $self.closest("[data-param]");
    var $pp = $self.parent();
    var $param = $wrap.data("param");
    var $row = $wrap.data("row")||100;
    console.log($param);
    var api = iCMS.api("comment", "reply");
    iCMS.request
        .post(api, { id: $param["id"], row: $row })
        .then(function (json) {
            if (json.code) {
                if (json.data.html) {
                    console.log(json.data.html);
                    // var $div = $("<div></div>");
                    $pp.before(json.data.html);
                    // $div.html(json.data.html);
                    // $commentForm.appendTo($div).show();
                    // iCMS.ui.reCaptcha();
                }
            }
        })
        .catch(function (error) {
            iCMS.notify.error(error.message);
        });
}

jQuery(() => {
    (function ($) {
        submit("评论", "form:comment", {
            rules: {
                content: {
                    required: true,
                    minlength: 14,
                },
                captcha: {
                    required: true,
                },
            },
            messages: {
                content: {
                    required: "请输入您的评论",
                    minlength: "评论不能少于14个字符",
                },
                captcha: "请输入验证码",
            },
        });
        submit("回复评论", "form:comment:reply", {
            rules: {
                content: {
                    required: true,
                    minlength: 14,
                },
                captcha: {
                    required: true,
                },
            },
            messages: {
                content: {
                    required: "请输入您的回复",
                    minlength: "回复不能少于14个字符",
                },
                captcha: "请输入验证码",
            },
        });
    })(jQuery);
});
