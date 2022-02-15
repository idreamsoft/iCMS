(function ($) {
    class iCMS {
        constructor() {
            var ime = this;
            this.$CONFIG = {
                API: "/public/api.php",
                PUBLIC: "/",
                COOKIE: "iCMS_",
                AUTH: "USER_AUTH",
                DIALOG: [],
            };
            this.$SET = {};
            this.$CALLBACK = {};
            this.set = function (key, options) {
                this.$SET[key] = $.extend(this.$SET[key], options);
                return this;
            };
            this.init = function (options) {
                this.$CONFIG = $.extend(this.$CONFIG, options);
                // console.log(this);
            };
            this.ui = new (function () {
                // console.log(ime,iCMS);
                var ui = this;
                this.$dialog = {};
                this.$button = [];
                this.$on = [];
                this.$content = "";

                this.success = function (msg, callback, time) {
                    if (typeof callback === "number") {
                        time = callback;
                        callback = null;
                    }
                    return this.sdialog(msg, true, callback, time);
                };
                this.alert = function (msg, callback, time) {
                    if (typeof callback === "number") {
                        time = callback;
                        callback = null;
                    }
                    return this.sdialog(msg, false, callback, time);
                };
                this.sdialog = function (msg, ok, callback, time) {
                    var opts = ok
                        ? {
                              label: "success",
                              icon: "check",
                          }
                        : {
                              label: "warning",
                              icon: "times",
                          };
                    opts.id = "iPHP-DIALOG-ALERT";
                    opts.skin = "iCMS_dialog_alert";
                    opts.content = msg;
                    opts.height = 150;
                    opts.modal = true;
                    opts.time = time || 3000;
                    return this.dialog(opts, callback);
                };
                this.dialog = function (options, callback) {
                    var defaults = {
                            id: "iCMS-DIALOG",
                            title: "iCMS - 提示信息",
                            width: "auto",
                            height: "auto",
                            className: "iCMS-UI-dialog",
                            backdropBackground: "#333",
                            backdropOpacity: 0.5,
                            fixed: true,
                            autofocus: false,
                            quickClose: true,
                            modal: true,
                            time: null,
                            label: "success",
                            icon: "check",
                            api: false,
                            zIndex: 9999,
                        },
                        timeOutID = null,
                        opts = $.extend(defaults, ime.$CONFIG.DIALOG, options);
                    if (opts.follow) {
                        opts.fixed = false;
                        opts.modal = false;
                        opts.skin = "iCMS-UI-tooltip";
                        opts.className = "ui-popup";
                        opts.backdropOpacity = 0;
                    }
                    var content = opts.content;
                    //console.log(typeof content);
                    if (content instanceof jQuery) {
                        // opts.content = content;
                    } else if (typeof content === "string") {
                        opts.content = this.display(content, opts);
                    }
                    opts.onclose = function () {
                        runCallback("close");
                    };
                    opts.onbeforeremove = function () {
                        runCallback("beforeremove");
                    };
                    opts.onremove = function () {
                        runCallback("remove");
                    };

                    var d = window.dialog(opts);

                    if (!$.isEmptyObject(opts.button)) {
                        d.button(opts.button);
                    }
                    if (!$.isEmptyObject(this.$button)) {
                        d.button(this.$button);
                    }
                    if (this.$content) {
                        d.content(this.display(this.$content, opts));
                    }

                    if (opts.modal) {
                        d.showModal();
                        // $(d.backdrop).addClass("ui-popup-overlay").click(function(){
                        //     d.close().remove();
                        // })
                    } else {
                        d.show(opts.follow);
                        if (opts.follow) {
                            //$(d.backdrop).remove();
                            // $("body").bind("click",function(){
                            //     d.close().remove();
                            // })
                        }
                        //$(d.backdrop).css("opacity","0");
                    }
                    if (opts.time) {
                        timeOutID = window.setTimeout(function () {
                            if (this.$on && typeof this.$on["timeOut"] === "function") {
                                return this.$on["timeOut"](d);
                            } else {
                                // console.log(d.destroyed,typeof(d));
                                if (d && d.destroyed === false) {
                                    d.destroy();
                                }
                            }
                        }, opts.time);
                    }
                    d.destroy = function () {
                        d.close().remove();
                    };

                    function runCallback(type) {
                        // console.log(type);
                        window.clearTimeout(timeOutID);
                        if (ui.$on) {
                            console.log(type);
                            if (typeof ui.$on[type] === "function") {
                                return ui.$on[type](d);
                            }
                        }
                        if (typeof callback === "function") {
                            return callback(type, d);
                        }
                    }

                    ime.ui.$dialog = d;
                    return d;
                };
                this.display = function (content, opts) {
                    return (
                        '<table class="ui-dialog-table" align="center"><tr><td valign="middle">' +
                        '<div class="ui-dialog-msg">' +
                        '<div class="badge badge-' +
                        opts.label +
                        '">' +
                        '<i class="fa fa-fw fa-' +
                        opts.icon +
                        '"></i>' +
                        content +
                        "</div></div>" +
                        "</td></tr></table>"
                    );
                };
                this.reCaptcha = function ($el, event = "reload") {
                    $el = $el || $(".captcha-img");
                    if (!($el instanceof jQuery)) {
                        $el = $($el);
                    }
                    var _this = this;
                    if (event == "click") {
                        $el.click(function (e) {
                            e.preventDefault();
                            _this.reCaptcha();
                        });
                    } else {
                        var $src = $el.attr("src");
                        if ($src) {
                            $src = $src.replace(/[\?|&]t=.+/g, "");
                            $src += $src.indexOf("?") == "-1" ? "?" : "&";
                            $el.attr("src", $src + "t=" + Math.random());
                            var $wrap = $el.closest(".input-group");
                            $('[name="captcha"]', $wrap).val("");
                        }
                    }
                };
            })();
            this.request = new (function () {
                var me = this;
                this.send = function (url, param, resolve, reject, type) {
                    $.ajax({
                        type: type || "GET",
                        url: url,
                        data: param,
                        async: true, //默认为true,即异步请求；false为同步请求
                        // success:resolve,
                        success: function (json) {
                            // if (json.code > 0) {
                            //     resolve(json);
                            // } else
                            if (json.code == "-9999") {
                                //无登录
                                if (typeof ime.$CALLBACK["login"] === "function") {
                                    ime.$CALLBACK["login"](json);
                                } else {
                                    ime.notify.warning(json.message);
                                }
                                console.log("login", json);
                            } else if (json.code == "-1") {
                                ime.notify.error(json.message);
                                reject(json);
                            } else {
                                resolve(json);
                            }
                        },
                        error: function (error) {
                            console.log("ajax error", error);
                            ime.notify.error(error.message || error.responseText);
                            reject(error);
                        },
                        dataType: "json",
                    });
                };
                this.get = function (url, param) {
                    return new Promise(function (resolve, reject) {
                        me.send(url, param, resolve, reject, "GET");
                    });
                };

                this.post = function (url, param) {
                    return new Promise(function (resolve, reject) {
                        me.send(url, param, resolve, reject, "POST");
                    });
                };
            })();
            this.notify = new (function () {
                this.info = function (msg) {
                    One.helpers("notify", {
                        type: "info",
                        icon: "fa fa-info-circle mr-1",
                        message: msg,
                        z_index: 999999,
                    });
                };
                this.success = function (msg) {
                    One.helpers("notify", {
                        type: "success",
                        icon: "fa fa-check mr-1",
                        message: msg,
                        z_index: 999999,
                    });
                };
                this.error = function (msg, time) {
                    One.helpers("notify", {
                        type: "danger",
                        icon: "fa fa-times mr-1",
                        message: msg,
                        timer: time || 60000,
                        z_index: 999999,
                    });
                };
                this.warning = function (msg, time) {
                    One.helpers("notify", {
                        type: "warning",
                        icon: "fa fa-exclamation mr-1",
                        message: msg,
                        timer: time || 5000,
                        z_index: 999999,
                    });
                };
            })();
            this.tools = new (function () {
                var me = this;
                this.sendSMScode = function (phone, $captcha, $that) {
                    ime.request
                        .post(ime.api("public"), {
                            action: "smscode",
                            phone: phone,
                            captcha: $captcha.val(),
                        })
                        .then(function (json) {
                            if (json.code) {
                                ime.notify.success("短信验证码已发送");
                                $that.attr("disabled", true);
                                var sTime = 60;
                                var timer = setInterval(function () {
                                    $that.text(sTime + "s 后可重发");
                                    sTime--;
                                    console.log(sTime);
                                    if (sTime < 0) {
                                        $captcha.val("");
                                        ime.ui.reCaptcha();
                                        $that.text("获取短信验证码");
                                        $that.removeAttr("disabled");
                                        clearInterval(timer);
                                    }
                                }, 1000);
                            } else {
                                $captcha.val("");
                                ime.ui.reCaptcha();
                                ime.notify.error(json.message);
                            }
                        })
                        .catch(function (error) {
                            ime.notify.error(error.message);
                        });
                };
                this.sendEmailCode = function (email, $that) {
                    ime.request
                        .post(ime.api("public"), {
                            action: "emailcode",
                            email: email,
                        })
                        .then(function (json) {
                            if (json.code) {
                                ime.notify.success("邮箱验证码已发送");
                                $that.attr("disabled", true);
                                var sTime = 60;
                                var timer = setInterval(function () {
                                    $that.text(sTime + "s 后可重发");
                                    sTime--;
                                    console.log(sTime);
                                    if (sTime < 0) {
                                        $that.text("获取邮箱验证码");
                                        $that.removeAttr("disabled");
                                        clearInterval(timer);
                                    }
                                }, 1000);
                            } else {
                                ime.notify.error(json.message);
                            }
                        })
                        .catch(function (error) {
                            ime.notify.error(error.message);
                        });
                };
            })();
            this.api = function (app, $do) {
                var url = ime.$CONFIG.API;
                if (app) url += "?app=" + app;
                if ($do) url += "&do=" + $do;
                return url;
            };
            this.$i = function (i, doc) {
                var doc = doc || document;
                if (i instanceof jQuery) {
                    return i;
                } else if (typeof i === "string") {
                    var a = i.substr(0, 1);
                    if (a == "." || a == "#") {
                        return $(i, doc);
                    }
                }
                return $('[i="' + i + '"]', doc);
            };
            this.$iv = function (a, i) {
                var iv = $(a).attr("i");
                if (i) iv = iv.replace(i + ":", "");
                return iv.split(":");
            };
            this.multiple = function (a) {
                var $this = $(a),
                    $parent = $this.parent(),
                    param = ime.param($this),
                    _param = ime.param($parent);
                return $.extend(param, _param);
            };
            this.param = function (a, _param) {
                if (_param) {
                    a.attr("data-param", ime.json2str(_param));
                    return;
                }
                var param = a.attr("data-param") || false;
                if (!param) return {};
                return $.parseJSON(param);
            };
            this.tip = function (el, title, placement) {
                placement = placement || el.attr("data-placement");
                var container = el.attr("data-container");
                if (container) {
                    $(container).html("");
                }
                el.tooltip("destroy");
                el.tooltip({
                    html: true,
                    container: container || false,
                    placement: placement || "right",
                    trigger: "manual",
                    title: title,
                }).tooltip("show");
            };

            this.random = function (len, ischar) {
                len = len || 16;
                var chars = "abcdefhjmnpqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWYXZ";
                if (ischar) {
                    var chars = "abcdefhjmnpqrstuvwxyz";
                }
                var code = "";
                for (var i = 0; i < len; i++) {
                    code += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return code;
            };
            this.json2str = function (o) {
                var arr = [];
                var fmt = function (s) {
                    if (typeof s == "object" && s != null) return ime.json2str(s);
                    return /^(string|number)$/.test(typeof s) ? '"' + s + '"' : s;
                };
                for (var i in o) arr.push('"' + i + '":' + fmt(o[i]));
                return "{" + arr.join(",") + "}";
            };
            this.format = function (content, ubb) {
                // console.log(content);
                content = content
                    .replace(/\/"/g, '"')
                    .replace(/\\\&quot;/g, "")
                    .replace(/\r/g, "")
                    .replace(/on(\w+)="[^"]+"/gi, "")
                    .replace(/<script[^>]*?>(.*?)<\/script>/gi, "")
                    .replace(/<style[^>]*?>(.*?)<\/style>/gi, "")
                    .replace(/style=[" ]?([^"]+)[" ]/gi, "")
                    .replace(/<a[^>]+href=[" ]?([^"]+)[" ]?[^>]*>(.*?)<\/a>/gi, "[url=$1]$2[/url]")
                    .replace(/<img[^>]+src=[" ]?([^"]+)[" ]?[^>]*>/gi, "[img]$1[/img]")
                    .replace(/<pre\s*class="brush:(.+)">(.*?)<\/pre>/gis, '[brush="$1"]$2[/brush]')
                    .replace(/<embed/g, "\n<embed")
                    .replace(/<embed[^>]+class="edui-faked-video"[^"].+src=[" ]?([^"]+)[" ]+width=[" ]?([^"]\d+)[" ]+height=[" ]?([^"]\d+)[" ]?[^>]*>/gi, "[embed video=$2,$3]$1[/embed]")
                    .replace(/<embed[^>]+class="edui-faked-music"[^"].+src=[" ]?([^"]+)[" ]+width=[" ]?([^"]\d+)[" ]+height=[" ]?([^"]\d+)[" ]?[^>]*>/gi, "[embed music=$2,$3]$1[/embed]")
                    .replace(/<video[^>]*?width=[" ]?([^"]\d+)[" ]+height=[" ]?([^"]\d+)[" ]+src=[" ]?([^"]+)[" ]+?[^>]*>*<source src=[" ]?([^"]+)[" ]+type=[" ]?([^"]+)[" ]\/>*<\/video>/gim, '[video=$1,$2 type="$5"]$3[/video]')
                    .replace(/<h([1-6])[^>]*>(.*?)<\/h([1-6])>/gi, "[h$1]$2[/h$1]")
                    .replace(/<b[^>]*>(.*?)<\/b>/gi, "[b]$1[/b]")
                    .replace(/<strong[^>]*>(.*?)<\/strong>/gi, "[b]$1[/b]")
                    .replace(/<p[^>]*?>/g, "\n")
                    .replace(/<br[^>]*?>/g, "\n")
                    .replace(/<[^>]*?>/g, "");

                if (ubb) {
                    content = content.replace(/\n+/g, "[iCMS.N]");
                    content = this.n2p(content, ubb);
                    return content;
                }
                content = content
                    .replace(/\[brush="(.+?)"\](.*?)\[\/brush\]/gis, '<pre class="brush:$1">$2</pre>')
                    .replace(/\[url=([^\]]+)\]\n(\[img\]\1\[\/img\])\n\[\/url\]/g, "$2")
                    .replace(/\[img\](.*?)\[\/img\]/gi, '<p><img src="$1" /></p>')
                    .replace(/\[b\](.*?)\[\/b\]/gi, "<b>$1</b>")
                    .replace(/\[h([1-6])\](.*?)\[\/h([1-6])\]/gi, "<h$1>$2</h$1>")
                    .replace(/\[url=([^\]|#]+)\](.*?)\[\/url\]/g, "$2")
                    .replace(/\[url=([^\]]+)\](.*?)\[\/url\]/g, '<a target="_blank" href="$1">$2</a>');
                // .replace(/\n+/g, "[iCMS.N]");
                // content = this.n2p(content);
                content = content
                    .replace(/#--iCMS.PageBreak--#/g, "<!---->#--iCMS.PageBreak--#")
                    .replace(/<p>\s*<p>/g, "<p>")
                    .replace(/<\/p>\s*<\/p>/g, "</p>")
                    .replace(/<p>\s*<\/p>/g, "")
                    .replace(
                        /\[video=(\d+),(\d+)\stype="(.+?)"\](.*?)\[\/video\]/gi,
                        '<video class="edui-upload-video  vjs-default-skin  video-js" controls="" preload="none" width="$1" height="$2" src="$4" data-setup="{}">' + '<source src="$4" type="$3"/>' + "</video>"
                    )
                    .replace(
                        /\[embed\svideo=(\d+),(\d+)\](.*?)\[\/embed\]/gi,
                        '<embed type="application/x-shockwave-flash" class="edui-faked-video" pluginspage="http://www.macromedia.com/go/getflashplayer" src="$3" width="$1" height="$2" wmode="transparent" play="true" loop="false" menu="false" allowscriptaccess="never" allowfullscreen="true"/>'
                    )
                    .replace(
                        /\[embed\smusic=(\d+),(\d+)\](.*?)\[\/embed\]/gi,
                        '<embed type="application/x-shockwave-flash" class="edui-faked-music" pluginspage="http://www.macromedia.com/go/getflashplayer" src="$3" width="$1" height="$2" wmode="transparent" play="true" loop="false" menu="false" allowscriptaccess="never" allowfullscreen="true" align="none"/>'
                    )
                    .replace(/<p><br\/><\/p>/g, "");
                return content;
            };
            this.n2p = function (cc, ubb) {
                var c = "",
                    s = cc.split("[iCMS.N]");
                for (var i = 0; i < s.length; i++) {
                    while (s[i].substr(0, 1) == " " || s[i].substr(0, 1) == "　") {
                        s[i] = s[i].substr(1, s[i].length);
                    }
                    if (s[i].length > 0) {
                        if (ubb) {
                            c += s[i] + "\n";
                        } else {
                            c += "<p>" + s[i] + "</p>";
                        }
                    }
                }
                return c;
            };
        }
    }
    window.iCMS = new iCMS();
})(jQuery);

(function ($) {
    $.fn.jsonArray = function (options) {
        // var opts = $.extend({}, defaults, options);
        var param = $(this).serializeArray();
        var data = {};
        $.each(param, function (index, val) {
            var name = val["name"];
            if (name.indexOf("[") != -1) {
                name = name.replace(/\[\d+\]/g, "[]");
                name = name.replace("[]", "");
                if (!data[name]) {
                    data[name] = [];
                }
                if (val["value"] != "") {
                    data[name].push(val["value"]);
                }
            } else {
                data[name] = val["value"];
            }
        });
        return data;
    };
})(jQuery);
(function ($) {
    $.parseTmpl = function parse(str, data) {
        var tmpl =
            "var __p=[],print=function(){__p.push.apply(__p,arguments);};" +
            "with(obj||{}){__p.push('" +
            str
                .replace(/\\/g, "\\\\")
                .replace(/'/g, "\\'")
                .replace(/<%=([\s\S]+?)%>/g, function (match, code) {
                    return "'," + code.replace(/\\'/g, "'") + ",'";
                })
                .replace(/<%([\s\S]+?)%>/g, function (match, code) {
                    return "');" + code.replace(/\\'/g, "'").replace(/[\r\n\t]/g, " ") + "__p.push('";
                })
                .replace(/\r/g, "\\r")
                .replace(/\n/g, "\\n")
                .replace(/\t/g, "\\t") +
            "');}return __p.join('');";
        var func = new Function("obj", tmpl);
        return data ? func(data) : func;
    };
})(jQuery);

function isEmpty(value) {
    return (Array.isArray(value) && value.length === 0) || (Object.prototype.isPrototypeOf(value) && Object.keys(value).length === 0);
}
function iModal(options, a) {
    var defaults = {
        width: "360px",
        height: "auto",
        maxHeight: $(window).height() * 0.9,
        title: $(a).attr("title") || $(a).data("title") || "iCMS 提示",
        href: $(a).attr("href") || $(a).data("href") || false,
        target: $(a).data("target") || "#iCMS-MODAL",
        zIndex: $(a).data("zindex") || false,
        size: $(a).data("size") || false,
        footer: $(a).data("footer") || false,
        pos: $(a).data("pos") || "centered",
    };

    var meta = $(a).data("meta");
    if (typeof meta == "string") meta = $.parseJSON(meta);
    var opts = $.extend(defaults, options, meta);
    var target = $(opts.target);

    $.fn.modal.call(target, opts, a);

    if (opts.target != "#iCMS-MODAL") {
        return;
    }
    // target.on('show.bs.modal', function(e) {

    // });

    var mTitle = target.find(".modal-title");
    var mDialog = target.find(".modal-dialog");
    var mBody = target.find(".modal-body");
    var mFooter = target.find(".modal-footer");

    target.one("hidden.bs.modal", function (e) {
        console.log(e);
        mBody.empty();
    });

    mTitle.html(opts.title);
    mBody.empty();

    if (opts.html) {
        var content = opts.html;
        if (content instanceof jQuery) {
            content.show();
            html = content.html();
            mBody.html(html);
        } else if (content.nodeType === 1) {
            if (this._elemBack) {
                this._elemBack();
                delete this._elemBack;
            }
            // artDialog 5.0.4
            // 让传入的元素在对话框关闭后可以返回到原来的地方
            var display = content.style.display;
            var prev = content.previousSibling;
            var next = content.nextSibling;
            var parent = content.parentNode;
            this._elemBack = function () {
                if (prev && prev.parentNode) {
                    prev.parentNode.insertBefore(content, prev.nextSibling);
                } else if (next && next.parentNode) {
                    next.parentNode.insertBefore(content, next);
                } else if (parent) {
                    parent.appendChild(content);
                }
                content.style.display = display;
                this._elemBack = null;
            };
            $(content).show();
            mBody[0].appendChild(content);
        } else {
            mBody.html(html);
        }
    } else if (opts.href) {
        var src = opts.href;
        // console.log(src, (src.indexOf('?') == '-1'));
        if (src.indexOf("modal=true") == "-1") {
            if (src.indexOf("?") == "-1") {
                src += "?";
            } else {
                src += "&";
            }
            src += "modal=true";
        }
        var mFrame = $('<iframe id="modal-iframe" frameborder="no" allowtransparency="true" scrolling="auto" hidefocus="" src="' + src + '"></iframe>');
        var mFrameFix = $('<div id="modal-iframeFix"></div>');
        mFrameFix.appendTo(mBody);
        mFrame.appendTo(mBody);
    }
    // console.log(opts.zIndex);

    opts.zIndex &&
        target.css({
            cssText: "z-index:" + opts.zIndex + "!important",
        });
    mDialog.attr("class", "modal-dialog");

    if (opts.size) {
        mDialog.addClass("modal-" + opts.size);
    } else {
        mDialog.css({
            width: opts.width,
            "max-width": opts.width,
        });
    }
    !opts.footer && mFooter.hide();

    opts.pos && mDialog.addClass("modal-dialog-" + opts.pos);
    // mBody.css({"height": opts.height,"max-height": opts.maxHeight });
    $("#modal-iframe").css({
        height: opts.height,
        "max-height": opts.maxHeight,
    });

    target.addClass("show").show();
    var zIndex = parseInt(target.css("z-index")) - 1 || 9998;
    $(".modal-backdrop").css("z-index", zIndex);

    this.destroy = function () {
        target.modal("hide");
        mTitle.html("iCMS 提示");
        window.stop ? window.stop() : document.execCommand("Stop");
    };
    return this;
}
function pad(num, n) {
    num = num.toString();
    return Array(n > num.length ? n - ("" + num).length + 1 : 0).join(0) + num;
}
function isMobile() {
    var b = /iPhone/i,
        c = /iPod/i,
        d = /iPad/i,
        e = /(?=.*\bAndroid\b)(?=.*\bMobile\b)/i,
        f = /Android/i,
        g = /(?=.*\bAndroid\b)(?=.*\bSD4930UR\b)/i,
        h = /(?=.*\bAndroid\b)(?=.*\b(?:KFOT|KFTT|KFJWI|KFJWA|KFSOWI|KFTHWI|KFTHWA|KFAPWI|KFAPWA|KFARWI|KFASWI|KFSAWI|KFSAWA)\b)/i,
        i = /Windows Phone/i,
        j = /(?=.*\bWindows\b)(?=.*\bARM\b)/i,
        k = /BlackBerry/i,
        l = /BB10/i,
        m = /Opera Mini/i,
        n = /(CriOS|Chrome)(?=.*\bMobile\b)/i,
        o = /(?=.*\bFirefox\b)(?=.*\bMobile\b)/i,
        p = /UCWEB/i,
        q = /spider/i,
        r = /bot/i,
        s = /Windows; U;/i,
        t = new RegExp("(?:Nexus 7|BNTV250|Kindle Fire|Silk|GT-P1000)", "i"),
        u = function (a, b) {
            return a.test(b);
        },
        v = function (a) {
            var v = a || navigator.userAgent,
                w = v.split("[FBAN");
            return (
                "undefined" != typeof w[1] && (v = w[0]),
                (w = v.split("Twitter")),
                "undefined" != typeof w[1] && (v = w[0]),
                (this.apple = { phone: u(b, v), ipod: u(c, v), tablet: !u(b, v) && u(d, v), device: u(b, v) || u(c, v) || u(d, v) }),
                (this.amazon = { phone: u(g, v), tablet: !u(g, v) && u(h, v), device: u(g, v) || u(h, v) }),
                (this.android = { phone: u(g, v) || u(e, v), tablet: !u(g, v) && !u(e, v) && (u(h, v) || u(f, v)), device: u(g, v) || u(h, v) || u(e, v) || u(f, v) }),
                (this.windows = { phone: u(i, v), tablet: u(j, v), device: u(i, v) || u(j, v) }),
                (this.other = { blackberry: u(k, v), blackberry10: u(l, v), opera: u(m, v), firefox: u(o, v), chrome: u(n, v), ucweb: u(p, v), device: u(k, v) || u(l, v) || u(m, v) || u(o, v) || u(n, v) || u(p, v) }),
                (this.spiders = { spider: u(q, v), bot: u(r, v), u: u(s, v), device: u(q, v) || u(r, v) || u(s, v) }),
                (this.seven_inch = u(t, v)),
                (this.any = this.apple.device || this.android.device || this.windows.device || this.other.device || this.seven_inch),
                (this.phone = this.apple.phone || this.android.phone || this.windows.phone),
                (this.tablet = this.apple.tablet || this.android.tablet || this.windows.tablet),
                (this.spider = this.spiders.device),
                "undefined" == typeof window ? this : void 0
            );
        },
        w = function () {
            var a = new v();
            return (a.Class = v), a;
        };
    return w();
}
function var_dump(a) {
    console.log(a);
}
