$(function () {
    // $("#title").focus();

    $(".editor-page").change(function () {
        $(".editor-container").hide();
        $("#editor-wrap-" + this.value).show();
        iEditor.get("editor-body-" + this.value).focus();
        $(".editor-page").val(this.value).trigger("chosen:updated");
    });

    $("#isChapter").click(function () {
        var checkedStatus = $(this).prop("checked"),
            chapter = $("input[name=chapter]").val();
        subtitleToggle(checkedStatus);
        if (!checkedStatus && chapter > 1) {
            return confirm("确定要取消章节模式?");
        }
    });
    var hotkey = false;

    $($APP_FORMID).submit(function () {
        var me = this;
        if (hotkey) {
            if (this.action.indexOf("&keyCode=ctrl-s") === -1) {
                this.action += "&keyCode=ctrl-s";
            }
        }

        var cid = $("#cid option:selected").val();
        if (cid == "0") {
            $("#cid").focus();
            iCMS.ui.alert("请选择所属栏目");
            return false;
        }
        if ($("#title").val() == "") {
            $("#title").focus();
            iCMS.ui.alert("标题不能为空!");
            return false;
        }
        if ($("#url").val() == "") {
            var n = $(".editor-page:eq(0) option:first").val(),
                ed = iEditor.get("editor-body-" + n);
            var hasContents = iEditor.hasContents();
            if (!hasContents) {
                ed.focus();
                iCMS.ui.alert("第" + n + "页内容不能为空!");
                $("#editor-wrap-" + n).show();
                $(".editor-page").val(n).trigger("chosen:updated");
                return false;
            }
        }
        var submitBtn = $('button[type="submit"]', this);
        submitBtn.button("loading");
        var text = submitBtn.data("loading-text") || "提交中，请稍候...";
        // iCMS.ui.success(text, function() {
        //     submitBtn.button('reset');
        // }, 10000);

        iCMS.ui.dialog(
            {
                id: "iCMS-DIALOG-ALERT",
                content: text,
                time: 5000,
                height: "150",
                icon: "loading",
            },
            function () {
                submitBtn.button("reset");
            }
        );

        // if($('#isChapter').prop("checked") && $("#subtitle").val()==''){
        //   $("#subtitle").focus();
        //   iCMS.ui.alert("章节模式下 章节标题不能为空!");
        //   return false;
        // }
    });
    $(document).keydown(function (e) {
        var keyCode = e.keyCode || e.which || e.charCode;
        var ctrlKey = e.ctrlKey || e.metaKey;
        if (ctrlKey && keyCode == 83) {
            e.preventDefault();
            hotkey = true;
            $($APP_FORMID).submit();
        }
        hotkey = false;
    });
    $("#tag_extrac").click(function (event) {
        var that = this;
        var title = $("#title").val();
        var n = $(".editor-page:eq(0) option:first").val(),
            ed = iEditor.get("editor-body-" + n);
        var content = $isMarkdown ? ed.getValue() : ed.getContent();
        var $url = $(that).data("url");
        $.post(
            $url,
            {
                title: title,
                content: content,
            },
            function (data) {
                var target = $(that).data("target");
                if (data.length) {
                    $(target).val(data.join(","));
                }
            },
            "json"
        );
    });
    $("#title").focus(function () {
        if (!$APP_CONFIG.repeatitle) {
            return;
        }
        var me = $(this);
        var isblur = me.data("blur");
        $("#title-help").text("");
        console.log("isblur", Boolean(isblur));
        if (Boolean(isblur)) {
            me.unbind("blur");
            me.data("blur", false);
        }

        me.bind("blur", function (e) {
            me.data("blur", true);
            var title = me.val();
            $.getJSON(
                $APP_URL,
                {
                    do: "check",
                    id: $ID,
                    title: title,
                },
                function (json) {
                    var tip = "";
                    if (!json.code) {
                        tip = '<p class="alert alert-danger m-0 p-1">' + json.message + "</p>";
                    }
                    $("#title-tip").html(tip);
                    me.unbind("blur");
                }
            );
        });
    });
});

function mergeEditorPage() {
    if ($isMarkdown) return;

    var html = [];
    $(".editor-container").each(function (n, a) {
        var eid = a.id.replace("editor-wrap-", "editor-body-");
        if (iEditor.container[eid].length) {
            iEditor.container[eid].destroy();
        }
        var content = $("textarea", this).val();
        content && html.push(content);
        if (n) {
            $(this).remove();
        }
    });

    $(".editor-container").show();
    var allHtml = html.join("#--iCMS.PageBreak--#"),
        ned = $("textarea", ".editor-container"),
        neid = $(".editor-container").attr("id").replace("editor-wrap-", "editor-body-");
    ned.val(allHtml).css({
        width: "100%",
        height: "500px",
    });
    iEditor.create(neid).focus();
    $(".editor-page").html('<option value="1">第 1 页</option>').val(1).trigger("chosen:updated");
}

function addEditorPage() {
    //iCMSed.cleanup(iCMSed.id);
    var index = parseInt($(".editor-page option:last").val()),
        n = index + 1;
    $(".editor-container").hide();
    $("#editor-wrap-" + index).after(
        '<div id="editor-wrap-' +n +'" class="editor-container">' +
            '<div v-show="isChapter" class="form-group row chapter-title">' +
            '<label class="col-xl-1 col-form-label" for="subtitle">章节标题</label>' +
            '<div class="input-group col-sm-8">' +
                '<input type="text" id="chapter-title-' +n +'" disabled="true" name="chapterTitle[]" class="form-control" value="" />' +
            "</div>" +
            '<input name="data_id[]" id="data_id-' +n +'" disabled="true" type="hidden" value="" />' +
            "</div>" +
            '<div class="form-group">' +
            ($isMarkdown ? 
                '<div id="editor-body-' + n + '"></div><textarea type="text/plain" name="body[]"></textarea>' : 
                '<textarea type="text/plain" id="editor-body-' + n + '" name="body[]"></textarea>') +
            "</div>" +
            "</div>"
    );
    $(".editor-page")
        .append('<option value="' + n + '">第 ' + n + " 页</option>")
        .val(n)
        .trigger("chosen:updated");
    iEditor.create("editor-body-" + n).focus();
    var checkedStatus = $("#isChapter").prop("checked");
    subtitleToggle(checkedStatus);
}

function subtitleToggle(checkedStatus) {
    var box = $(".subtitle-box");
    var tit = $(".chapter-title");
    if (checkedStatus) {
        box.addClass("hide");
        $("input", box).attr("disabled", "disabled");
        tit.removeClass("hide");
        $("input", tit).removeAttr("disabled");

        var data_id = $("[name='data_id']", box).val();
        $("[name='data_id[]']", tit).eq(0).val(data_id);
        // var subtitle = $("[name='subtitle']", box).val();
        // $("[name='chaptertitle[]']", tit).eq(0).val(subtitle);
    } else {
        box.removeClass("hide");
        $("input", box).removeAttr("disabled");
        var data_id = $("[name='data_id[]']", tit).eq(0).val();
        $("[name='data_id']", box).val(data_id);
        // var subtitle = $("[name='chaptertitle[]']", tit).eq(0).val();
        // $("[name='subtitle']", box).val(subtitle);
        tit.addClass("hide");
        $("input", tit).attr("disabled", "disabled");
    }
}

function delEditorPage() {
    if ($(".editor-page:eq(0) option").length == 1) return;

    var s = $(".editor-page option:selected"),
        i = s.val(),
        p = s.prev(),
        n = s.next();
    if (n.length) {
        var index = n.val();
    } else if (p.length) {
        var index = p.val();
    }
    s.remove();
    iEditor.destroy("editor-body-" + i);
    $("#editor-body-" + i).remove();
    $("#editor-wrap-" + i).remove();

    $(".editor-page").val(index).trigger("chosen:updated");
    $("#editor-wrap-" + index).show();
    iEditor.eid = "editor-body-" + index;
    iEditor.get("editor-body-" + index).focus();
}

function modal_picture(el, a) {
    if (!a.checked) return;
    var ed = iEditor.get(),
        url = $(a).attr("url");
    // if(a.checked){
    var imgObj = {};
    imgObj.src = url;
    imgObj._src = url;
    ed.fireEvent("beforeInsertImage", imgObj);
    ed.execCommand("insertImage", imgObj);
    _modal_dialog("继续选择");
    // }else{
    //   var html = ed.getContent(),
    //   img = '<img src="'+url+'"/>';

    //   html = html.replace(img,'');
    //   log(html);
    // }
    return true;
}

function modal_sweditor(el) {
    if (!el.checked) return;

    var e = $(el),
        image = e.attr("_image"),
        fileType = e.attr("_fileType"),
        original = e.attr("_original"),
        url = e.attr("url"),
        ed = iEditor.get();

    if (url == "undefined") return;
    var html = '<p class="attachment icon_' + fileType + '"><a href="' + url + '" target="_blank">' + original + "</a></p>";

    if (image == "1") html = '<p><img src="' + url + '" /></p>';

    ed.execCommand("insertHTML", html);
    _modal_dialog("继续上传");
}

function _modal_dialog(cancel_text) {
    iCMS.ui.dialog({
        content: "插入成功!",
        okValue: "完成",
        ok: function () {
            window.iCMS_MODAL.destroy();
            return true;
        },
        cancelValue: cancel_text,
        cancel: function () {
            return true;
        },
    });
}
