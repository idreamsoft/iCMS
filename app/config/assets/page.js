$(function () {
    $(".add_template_device").click(function () {
        var TD = $("#template_device");
        var length = parseInt($("tr:last", TD).attr("data-key")) + 1;
        var tdc = $(".template_device_clone tr").clone(true);
        if (!length) length = 0;
        tdc.attr("data-key", length);

        $("input", tdc).each(function () {
            this.id = this.id.replace("{key}", length);
            this.removeAttribute('disabled');
            if (this.name) this.name = this.name.replace("{key}", length);
        });

        $(".files_modal", tdc).each(function (index, el) {
            var href = $(this).attr("href").replace("{key}", length);
            $(this).attr("href", href);
        });

        tdc.appendTo(TD);
        return false;
    });
});

function modal_tplfile(el, a) {
    if (!el) return;
    if (!a.checked) return;

    var e = $("#" + el) || $("." + el);
    var def = $("#template_desktop_tpl").val();
    var val = a.value.replace(def + "/", "{iTPL}/");
    e.val(val);
    return "off";
}

function modal_tpl_index(el, a) {
    if (!el) return;
    if (!a.checked) return;

    var e = $("#" + el) || $("." + el),
        p = e.parent().parent(),
        pid = p.attr("id"),
        dir = $("#" + pid + "_tpl").val(),
        val = a.value.replace(dir + "/", "{iTPL}/");
    e.val(val);
    return "off";
}
