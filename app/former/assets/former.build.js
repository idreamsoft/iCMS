function formerInit(el) {
    el = el || ".iFormer-layout";
    $(el)
        .sortable({
            placeholder: "ui-state-highlight",
            cancel: ".clearfloat",
            delay: 300,
            sort: function (event, ui) {
                var target = $(event.target);
                $(".clearfloat", target).remove();
                target.append('<div class="clearfloat mt-4"></div>');
            },
            receive: function (event, ui) {
                var helper = ui.helper,
                    tag = helper.attr("tag"),
                    aclass = helper.attr("ui-class"),
                    field = helper.attr("field"),
                    type = helper.attr("type"),
                    label = helper.attr("label"),
                    after = helper.attr("label-after"),
                    len = helper.attr("len"),
                    id = iCMS.random(6, true);
                var target = $(event.target);
                // console.log(target);
                var tabs = target.data("tabs");
                // console.log(tabs);
                label = (label || "表单") + id;
                if (type == "relation:id") {
                    var root = $("#rootid").find("option:selected");
                    var relation_app = root.data("app");
                    var relation_title = root.data("title");
                    id = relation_app + "_id";
                    label = relation_title + "ID";
                }

                var html = iFormer.render(helper, {
                    id: id,
                    label: label,
                    "label-after": after,
                    field: field,
                    class: aclass,
                    name: id,
                    default: "",
                    type: type,
                    len: len,
                    tabs: tabs,
                });
                helper.replaceWith(html);
            },
        })
        .disableSelection();
}
$(function () {
    formerInit();
    $("[i='layout'],[i='field']", ".iFormer-design")
        .draggable({
            placeholder: "ui-state-highlight",
            connectToSortable: ".iFormer-layout",
            helper: "clone",
            revert: "invalid",
            start: function( event, ui ) {
                if($('.nav-item',"#former-tabs").length<2){
                    iCMS.ui.alert("请先添加一个表单卡!");
                    return false;
                }
            }
        })
        .disableSelection();

    // $(".iFormer-design").draggable().disableSelection();

    var doc = $(document);
    doc.on("click", "button.close", function (e) {
        e.preventDefault();
        var item = $(this).parent();
        var paneId = $("a", item).attr("href");
        item.remove();
        $(paneId).remove();
    });
    doc.on("click", "[data-action=add_tabs]", function (event) {
        event.preventDefault();
        iCMS.ui.dialog({
            follow: this,
            height: "auto",
            content: document.getElementById("mktabs-box"),
            modal: false,
            title: "创建新表单卡",
            okValue: "创建",
            ok: function () {
                var $name = $("#newtabname").val(),
                    $id = $("#newtabid").val(),
                    $icon = $("#newtabicon").val() || "",
                    d = this;
                if ($name == "") {
                    iCMS.ui.alert("请输入表单卡名称!");
                    $("#newtabname").focus();
                    return false;
                }
                if ($id == "") {
                    $id = iCMS.random(6, true);
                }
                var formerId = "former-layout-" + $id;
                console.log($("#" + formerId), $("#" + formerId).length);
                if ($("#" + formerId).length > 0) {
                    iCMS.ui.alert("表单卡ID已经存在!请重新输入！");
                    return false;
                }
                var wrapId = "former-wrap-" + $id;
                var block = $("#former-block");
                var tabs = $("#former-tabs").removeClass("js-tabs-enabled");
                var navlast = $(".block-options", tabs).parent();
                var tab_content = $(".tab-content", block);
                var item = '<li class="nav-item"><button type="button" class="close"><span>×</span></button><a class="nav-link active" href="#' + formerId + '"><i class="' + $icon + '"></i> ' + $name + "</a> </li>";
                var pane = '<div id="' + formerId + '" class="tab-pane active"><ul id="' + wrapId + '" class="iFormer-layout"><div class="clearfloat m-4"></div></ul></div>';
                $(".nav-link", tabs).removeClass("active");
                $(".tab-pane", tab_content).removeClass("active");
                navlast.before(item);
                tab_content.append(pane);
                // Helpers.run('core-bootstrap-tabs');
                One.helpers(["core-bootstrap-tabs"]);
                $("#" + wrapId).data("tabs", [$id, $name, $icon]);
                formerInit("#" + wrapId);
                return true;
            },
        });
    });
});
