// var vm = new Vue({
//     el: '#page-container',
//     data: {
//         message: 'Hello Vue!'
//     }
// });
var $chosen = {
    max_selected_options: 10,
    allow_single_deselect: true,
    search_contains: true,
    // inherit_select_classes: true,
    disable_search_threshold: 20,
    no_results_text: "没找到相关结果",
    placeholder_text_single: "请选择...",
    placeholder_text_multiple: "请选择(可多选)...",
};

$(function () {
    One.helpers(["validation", "table-tools-checkable"]);
    // One.helpers(["select2"]);
    // One.helpers("flatpickr");
    jQuery.validator.addMethod(
        "isMobile",
        function (value, element) {
            var length = value.length;
            var mobile = /^(13[0-9]{9})|(18[0-9]{9})|(14[0-9]{9})|(17[0-9]{9})|(15[0-9]{9})$/;
            return this.optional(element) || (length == 11 && mobile.test(value));
        },
        "请正确填写手机号码"
    );

    // One.helpers(['datepicker',, 'masked-inputs']);
    // $('[data-toggle="chosen"]:not(.js-chosen-enabled)').each((index, element) => {
    $("select:not(.js-chosen-enabled)")
        .not(".js-chosen-disable")
        .not('[data-toggle="chosen2"]')
        .each((index, element) => {
            $(element).addClass("js-chosen-enabled").chosen($chosen);
            // var $key = $(element).attr('id') || $(element).attr('name');
            // // var value = iCMS.Vue.$data[id];
            // var $value = $VueData[$key];
            // if (typeof $value === "undefined") {
            //     $(element).val($value);
            // }
        });
    $('[data-toggle="select2"]:not(.js-select2-enabled)').each((index, element) => {
        let el = $(element);
        // Add .js-select2-enabled class to tag it as activated and init it
        el.addClass("js-select2-enabled").select2({
            placeholder: el.data("placeholder") || false,
        });
    });

    $("[tip-title]:not(.js-tooltip-enabled)").each((index, element) => {
        $(element)
            .addClass("js-tooltip-enabled")
            .tooltip({
                title: $(element).attr("tip-title"),
            });
    });
    // $(".table-responsive").each((index, element) => {
    //     $(element).addClass("overflow-hidden")
    //     $("table.table",element).bootstrapTable({
    //         // toolbar: "#toolbar",
    //         // buttonsToolbar:"#buttonsToolbar",
    //         striped: true, //是否显示行间隔色
    //         height: 600,
    //         sortable: false, //是否排序
    //         search: true, //是否显示表格搜索，此搜索是客户端搜索，不会进服务端
    //         strictSearch: false, //是否显示刷新
    //         showColumns: true, //是否显示所有的列
    //         showRefresh: false, //是否显示刷新按钮
    //         minimumCountColumns: 2, //最少允许的列数
    //         showToggle: true, //是否显示详细视图和列表视图的切换按钮
    //         cardView: false, //是否显示详细视图
    //         fixedColumns: true,
    //         fixedNumber:1,
    //         fixedRightNumber: 1,
    //         cellStyle: 'asd'
    //     });
    // });
    $("a.btn[title]:not([data-toggle=modal])").tooltip({
        html: true,
    });
    $(".tip").tooltip({
        html: true,
    });
    $(".tip-left").tooltip({
        placement: "left",
        html: true,
    });
    $(".tip-right").tooltip({
        placement: "right",
        html: true,
    });
    $(".tip-top").tooltip({
        placement: "top",
        html: true,
    });
    $(".tip-bottom").tooltip({
        placement: "bottom",
        html: true,
    });

    //
    // $('[data-toggle="chosen"],.js-select2').chosen($chosen);
    $(".ui-datepicker").datepicker({
        format: "yyyy-mm-dd hh:ii:ss",
    });
    $(".js-flatpickr:not(.js-flatpickr-enabled)").each((index, element) => {
        let el = $(element);

        // Add .js-flatpickr-enabled class to tag it as activated
        el.addClass("js-flatpickr-enabled");

        // Init it
        flatpickr(el, {
            enableTime: true,
            time_24hr: true,
            locale: "zh",
        });
    });
    $('[data-toggle="popover"]').popover({
        html: true,
    });

    $.fn.bootstrapSwitch.defaults.onText = "开启";
    $.fn.bootstrapSwitch.defaults.offText = "关闭";
    $('[data-toggle="switch"]').bootstrapSwitch({
        onInit: function (event, state) {
            var id = $(event).prop("name");
            var checked = $(event).prop("checked");
            var ipt = '<input type="hidden" name="' + id + '" value="' + (checked ? 1 : 0) + '">';
            $(event).after(ipt);
        },
        onSwitchChange: function (event, state) {
            var id = $(event.target).prop("name");
            console.log(event, id);
            $('input[name="' + id + '"]').val(state ? 1 : 0);
        },
    });

    $(".custom-switch:not(.js-switch-default)").each(function (index, element) {
        var el = $(this);
        var on_text = el.data("on-text") || "开启";
        var off_text = el.data("off-text") || "关闭";
        var label = $("label.custom-control-label", el);
        var $text = label.text();

        var _label = function (e) {
            var $ed = $(e).is(":checked");
            label.text(($ed ? on_text : off_text) + $text);
        };
        var $box = $("input:checkbox", this);
        $box.on("change", function (e) {
            _label(this);
        }).on("click", function (e) {
            _label(this);
        });
        _label($box);
    });

    $("[target='iPHP_FRAME']").each(function () {
        var _add = function (url) {
            if (url && url.indexOf(".php") != "-1") {
                if (url.indexOf("frame=iPHP") == "-1") {
                    url += url.indexOf("?") == "-1" ? "?" : "&";
                    url += "frame=iPHP";
                }
                if (url.indexOf("CSRF_TOKEN=") == "-1") {
                    url += url.indexOf("?") == "-1" ? "?" : "&";
                    url += "CSRF_TOKEN=" + $CSRF_TOKEN;
                }
            }
            if ($IS_MODAL && url) {
                if (url.indexOf("modal=true") == "-1") {
                    url += url.indexOf("?") == "-1" ? "?" : "&";
                    url += "modal=true";
                }
            }
            return url;
        };
        if (this.href) {
            if ($(this).hasClass("js-frame") || this.href.indexOf("action=") != "-1") {
                this.href = _add(this.href);
            }
        }
        var action = $(this).attr("action");
        if (action) {
            action = _add(action);
            $(this).attr("action", action);
        }
    });
    var doc = $(document);
    // doc.on("chang", "select", function (event) {
    //     $(this).trigger("chosen:updated");
    // });
    // doc.on("click", ".nav-main-link", function (event) {
    //     // event.preventDefault();
    //     // var href = $(this).attr('href');
    //     // console.log(this.href,root);
    //     Cookies.set('MenuLink', this.href)
    // });
    doc.on("click", ".captcha-img,.captcha-text", function (event) {
        event.preventDefault();
        return iCMS.ui.reCaptcha();
    });
    doc.on("click", "a[target=iPHP_FRAME]:not(.js-frame)", function (event) {
        event.preventDefault();
        var me = $(this);
        var $action = me.data("action");
        var $wrap = me.closest('[id^="id"]');
        var $title = me.attr("title") || me.data("original-title") || me.text();
        if ($action == "delete") {
            $title = $title || "删除";
            if (!confirm("确定要" + $title)) {
                return false;
            }
        }

        var $url = me.attr("href");
        if ($url.indexOf("CSRF_TOKEN=") == "-1") {
            $url += $url.indexOf("?") == "-1" ? "?" : "&";
            $url += "CSRF_TOKEN=" + $CSRF_TOKEN;
        }

        iCMS.notify.info($title + "中请稍候......");

        iCMS.request
            .post($url, [])
            .then(function (json) {
                if (json.code) {
                    iCMS.notify.success($title + "成功！");
                    console.log($action);
                    if ($action == "remove" || $action == "delete") {
                        $wrap.fadeOut("slow", function () {
                            $(this).remove();
                        });
                        // p.remove();
                        return;
                    }
                }
                AdmDialog(json);
            })
            .catch(function (error) {
                console.log(error);
                iCMS.ui.alert(error.message || error.responseText, 300000);
            });
    });
    // doc.on("click", "button[i=upload]", function (event) {
    //     $("input[i=upload]").click();
    // });
    // doc.on("change", "input[i=upload]", function (event) {
    //     $("form[i=upload]").submit();
    // });
    doc.on("change", "input[i=upfile]", function (event) {
        event.preventDefault();
        $("form[i=upload]").submit();
    });

    doc.on("click", "[i=meta-delete]", function () {
        //元属性操作
        $(this).parent().parent().parent().find("td").remove();
    });
    doc.on("click", "[i=meta-add]", function () {
        var tb = $(this).parent().parent().parent(),
            tbody = $("tbody", tb),
            count = $("tr", tbody).length;
        var ntr = $(".meta_clone", tb).clone(true).removeClass("hide meta_clone");
        $("[disabled]", ntr)
            .removeAttr("disabled")
            .each(function () {
                this.name = this.name.replace("{key}", count);
            });
        ntr.appendTo(tbody);
        return false;
    });
    doc.on("click", '[data-toggle="random"]', function (event) {
        event.preventDefault();
        var a = $(this),
            target = a.data("target"),
            len = a.data("len") || 8;

        $(target).val(iCMS.random(len));
        return false;
    });
    doc.on("click", "[data-insert]", function (event) {
        event.preventDefault();
        var a = $(this),
            data = a.attr("data-insert"),
            target = a.attr("data-target"),
            mode = a.attr("data-mode"),
            val = a.text();
        if (data == "<%var%>") {
            data = "<%var_" + iCMS.random(2) + "%>";
        }
        if (mode) {
            $(target).val(data);
        } else {
            $(target).insertData(data);
        }
        return false;
    });
    doc.on("click", '[data-toggle="delete"]:not(.js-delete-enabled)', function (e) {
        e.preventDefault();
        let el = $(this).addClass("js-delete-enabled");
        let wrap = el.data("delete");
        console.log(el, wrap);
        if (wrap) {
            el.parents(wrap).remove();
        } else {
            el.remove();
        }
        return false;
    });
    doc.on("click", '[data-toggle="checkAll"]', function () {
        var target = $(this).attr("data-target"),
            checked = $(this).prop("checked");
        //$('[data-toggle="checkAll"]').prop("checked", checked);
        $("input:checkbox", $(target)).each(function () {
            this.checked = checked;
            // $.uniform.update($(this));
        });
    });
    doc.on("click", ".dropdown-submenu a.dropdown-toggle", function (e) {
        e.preventDefault();
        if (!$(this).next().hasClass("show")) {
            $(this).parents(".dropdown-menu").first().find(".show").removeClass("show");
        }
        var $subMenu = $(this).next(".dropdown-menu");
        $subMenu.toggleClass("show").attr("x-placement", "right-start");

        // $(this).parents('li.nav-item.dropdown.show').on('hidden.bs.dropdown', function(e) {
        //   $('.dropdown-submenu .show').removeClass('show');
        // });

        return false;
    });
    doc.on("click", '[data-toggle="modal"],[data-modal]', function (e) {
        e.preventDefault();
        var height = $(window).height() * 0.8;
        window.iCMS_MODAL = new iModal(
            {
                width: "85%",
                height: height,
                overflow: true,
            },
            this
        );
        return false;
    });
    doc.on("click", "[data-preview]", function (e) {
        var field = $(this).data("preview"),
            pic = $("#" + field).val();
        if (pic) {
            var src = $("#modal-iframe").attr("src");
            $("#modal-iframe").attr("src", src + "&pic=" + pic);
        } else {
            var check = $(this).data("check"),
                title = $(this).attr("title");
            if (check) {
                window.iCMS_MODAL.destroy();
                iCMS.ui.alert("暂无图片,您现在不能" + title);
            }
        }
    });
    if ($IS_MODAL) {
        // var modalHeight = $($APP_MAINID).height();
        // if (modalHeight < 600) {
        //     window.parent.$("#modal-iframe").height(modalHeight);
        // }
    }
    $($APP_FORMID).batch(iCMS.$SET["batch"] || {});
});

function activeMenu($link,$app){
    $("li.nav-main-item", 'ul.nav-main').removeClass('open');
    if ($link != "javascript:;") {
        var a = $('a.nav-main-link[href="' + $link + '"]', 'ul.nav-main');
        a.addClass('active');
        navopen(a);
    }
    if(typeof(a)==='undefined' && $app){
        var a = $('li#Menu-'+$app);
        a.addClass('open');
        navopen(a);
    }
    function navopen(a) {
        var $wrap = a.closest('ul.nav-main-submenu:not(.open)');
        // console.log($wrap);
        if($wrap.length){
            $wrap.addClass('open');
            $wrap.parent().addClass('open');
            // var root = $wrap.attr('root');
            // $("#Menu-" + root).addClass('open');
            navopen($wrap);
        }
        // var p = a.parent().parent();
        // if(p.hasClass('nav-main-submenu')){
        //     p.addClass('open');
        //     var root = p.attr('root');
        //     $("#Menu-" + root).addClass('open');
        //     navopen(p);
        // }
    }
}
function AdmDialog(json, time) {
    var $time = time || null;
    if (json.data) {
        if (json.data.action == "delete") {
            $("#id" + json.data.id).remove();
        }
        if (json.data.time) {
            $time = json.data.time * 1000;
        }
        if (json.data.button) {
            iCMS.ui.$button = [];
            $.each(json.data.button, function (idx, item) {
                var button = {};
                if (item["id"]) {
                    button.id = item["id"];
                }
                if (item["disabled"]) {
                    button.disabled = item["disabled"];
                }
                if (item["autofocus"]) {
                    button.autofocus = item["autofocus"];
                }
                button.value = item["title"] || item["text"] || item["value"];
                button.callback = function () {
                    if (item["target"]) {
                        window.top.open(item["url"], "_blank");
                        return true;
                    } else if (item["url"]) {
                        window.top.location.href = item["url"];
                    }
                    if (item["src"]) {
                        window.top.$("#iPHP_FRAME").attr("src", item["src"]);
                    }
                    if (item["alert"]) {
                        alert(item["alert"]);
                    }
                    if (item["content"]) {
                        this.content(item["content"]);
                    }
                    if (item["close"]) {
                        return true;
                    }
                    return false;
                };

                // if (item["next"]) {
                //     iCMS.ui.$on["timeOut"] = button.callback;
                // }
                iCMS.ui.$button.push(button);
            });
        }
        iCMS.ui.$on = [];
        if (json.forward || json.url) {
            iCMS.ui.$on["close"] = function (d) {
                window.top.location.href = json.url || json.forward;
                return false;
            };
        }

        if (json.data.onClose) {
            iCMS.ui.$on["close"] = function (d) {
                if (json.data.onClose["url"]) {
                    window.top.location.href = json.data.onClose["url"];
                    return false;
                }
                if (json.data.onClose["src"]) {
                    window.top.$("#iPHP_FRAME").attr("src", json.data.onClose["src"]);
                    return false;
                }
                if (json.data.onClose["modal"]) {
                    window.top.iCMS_MODAL.destroy();
                }
            };
        }
        if (json.data.message) {
            iCMS.ui.$content = json.data.message;
        }
        // if (json.data.update) {
        //     iCMS.ui.$dialog.content(json.message);
        // }
    }
    AdmDialogMsg(json,$time);
}
function AdmDialogMsg(json,$time) {
    switch (json.code) {
        case -9999:
            window.location.reload();
            break;
        case -1:
            var message = json.message || "错误";
            iCMS.ui.alert(message, null, 100000);
            break;
        case 0:
            var message = json.message || "失败";
            iCMS.ui.alert(message, null, $time);
            break;
        case 1:
            var message = json.message || "成功";
            iCMS.ui.success(message, null, $time);
            break;
    }
}
function AdmAlert(json) {
    AdmDialog(json);
}

function AdmSuccess(json) {
    AdmDialog(json);
}

function ModalSuccess(json) {
    AdmDialogMsg(json);
    window.iCMS_MODAL.destroy();
}

function inputChecked(vars, el) {
    if (!vars) return;

    $.each(vars, function (i, val) {
        var input = $('input[value="' + val + '"]', $(el));
        input.prop("checked", true);
    });
}

function modal_callback(el, a) {
    console.log(el, a);
    if (!el) return;
    if (!a.checked) return;

    var e = $("#" + el) || $("." + el);
    e.val(val);
    return "off";
}

function dialog_callback(options) {
    console.log(options);
    var d = iCMS.ui.dialog(options);
    if (options.timeout && options.timeout_callback) {
        window.setTimeout(function () {
            options.timeout_callback(d);
        }, options.timeout);
    }
}

(function (o) {
    jQuery.fn.clone = function () {
        var a = o.apply(this, arguments),
            b = this.find("textarea").add(this.filter("textarea")),
            c = a.find("textarea").add(a.filter("textarea")),
            d = this.find("select").add(this.filter("select")),
            e = a.find("select").add(a.filter("select"));

        for (var i = 0, l = b.length; i < l; ++i) $(c[i]).val($(b[i]).val());
        for (var i = 0, l = d.length; i < l; ++i) e[i].selectedIndex = d[i].selectedIndex;

        return a;
    };
})(jQuery.fn.clone);
//批量操作
(function ($) {
    $.fn.extend({
        batch: function (opt) {
            var im = $(this),
                _this = this,
                action = $('<input type="hidden" name="batch">'),
                bmIds = $('<input type="hidden" name="bmIds">'),
                batch_content = $('<div class="batch_content hide"></div>').appendTo(im),
                defaults = {
                    move: function () {
                        var select = $("#cid").clone().show().attr("class", "form-control").attr("id", iCMS.random(3));
                        $("option[value='']", select).remove();
                        $("option[value=all]", select).remove();
                        $("option:selected", select).attr("selected", false);
                        return select;
                    },
                    prop: function () {
                        var select = $("#pid").clone().show().attr("name", "pid[]").attr("multiple", "multiple").attr("class", "form-control").attr("id", iCMS.random(3));
                        $("option[value='']", select).remove();
                        $("option[value=all]", select).remove();
                        $("option:selected", select).attr("selected", false);
                        return select;
                    },
                },
                options = $.extend(defaults, opt);

            var doc = $(document);
            doc.on("click", '[data-toggle="batch"]', function (event) {
                event.preventDefault();
                // $('[data-toggle="batch"]').click(function() {
                var checkbox = $("input[name]:checkbox:checked", im);
                // console.log(checkbox);
                if (checkbox.length == 0) {
                    iCMS.ui.alert("请选择要操作项目!");
                    return true;
                }

                var a = $(this),
                    b = this,
                    act = a.attr("data-action"),
                    _act = act.replace(/,/g, "_").replace(/;/g, "_"),
                    dia = a.attr("data-dialog"),
                    ab = $("#" + _act + "Batch"),
                    box = document.getElementById(_act + "Batch"),
                    title = a.text();

                console.log(box, _act);

                if (dia === "no") {
                    options[act](checkbox);
                    return;
                }
                if (checkbox.length > 900) {
                    var bIds = [];
                    checkbox.each(function (index, el) {
                        var id = $(el).val();
                        bIds.push(id);
                        $(el).attr("disabled", true);
                    });
                    bmIds.val(bIds).appendTo(im);
                }

                action.val(act);
                if ($('input[name="batch"]', im).length == 0) {
                    action.appendTo(im);
                }
                // console.log(box,typeof box);
                // var is_chosen = false;
                if (box == null) {
                    //console.log(typeof options[act]);
                    if (typeof options[act] === "undefined") {
                        box = "确定要" + $.trim(title) + "?";
                        iCMS.$CONFIG.DIALOG = {
                            label: "warning",
                            icon: "warning",
                        };
                    } else {
                        console.log(options[act]());
                        box = document.createElement("div");
                        $(box)
                            .html(options[act]())
                            .attr("id", _act + "Batch");
                    }
                } else {
                    // $("select", $(box)).chosen($chosen);
                    // var is_chosen = true;
                }

                window.batch_dialog = iCMS.ui.dialog({
                    id: "iCMS-batch_dialog",
                    title: title,
                    content: box,
                    okValue: "确定",
                    ok: function () {
                        // console.log($(box));
                        if (typeof box == "object") {
                            var bbox = $(box).clone(true);
                            if ($("select[multiple]", box).length) {
                                $("option:selected", box).each(function () {
                                    console.log(this.value);
                                    $("option[value=" + this.value + "]", bbox).attr("selected", "selected");
                                });
                            }
                            batch_content.html(bbox);
                        }
                        im.submit();
                        batch_content.empty();
                        // return false;
                    },
                    cancelValue: "取消",
                    cancel: function () {
                        action.val(0);
                        bmIds.val("");
                        checkbox.removeAttr("disabled");
                        batch_content.empty();
                    },
                });
            });
            return im;
        },
    });
})(jQuery);
//插入内容
(function ($) {
    $.fn.extend({
        insertData: function (val, t) {
            var $t = $(this)[0];
            if (document.selection) {
                //ie
                this.focus();
                var sel = document.selection.createRange();
                sel.text = val;
                this.focus();
                sel.moveStart("character", -l);
                var wee = sel.text.length;
                if (arguments.length == 2) {
                    var l = $t.value.length;
                    sel.moveEnd("character", wee + t);
                    t <= 0 ? sel.moveStart("character", wee - 2 * t - val.length) : sel.moveStart("character", wee - t - val.length);
                    sel.select();
                }
            } else if ($t.selectionStart || $t.selectionStart == "0") {
                var startPos = $t.selectionStart;
                var endPos = $t.selectionEnd;
                var scrollTop = $t.scrollTop;
                $t.value = $t.value.substring(0, startPos) + val + $t.value.substring(endPos, $t.value.length);
                this.focus();
                $t.selectionStart = startPos + val.length;
                $t.selectionEnd = startPos + val.length;
                $t.scrollTop = scrollTop;
                if (arguments.length == 2) {
                    $t.setSelectionRange(startPos - t, $t.selectionEnd + t);
                    this.focus();
                }
            } else {
                this.value += val;
                this.focus();
            }
        },
    });
})(jQuery);
