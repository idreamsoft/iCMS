$(function() {
    $("#cid").on('change', function() {
        getAppPreMeta(this.value, "#AppPreMeta", 'app_meta');
    });
});

function getAppPreMeta(id, el, preid) {
    $.getJSON(window.iCMS.$CONFIG.API, {
            'app': 'node',
            'do': 'appMeta',
            'id': id
        },
        function(json) {
            if (!json.data) return;

            var tb = $(el),
                tbody = $("tbody", tb);
            $.each(json.data, function(n, v) {
                if (v['key']) {
                    var eid = preid + '_' + id + '_' + v['key'];
                    if ($("#" + eid).length > 0) {
                        return
                    }
                    var tr = $(".meta_clone", tb).clone(true).removeClass("hide meta_clone");
                    var count = $('tr', tbody).length;
                    tr.attr('id', eid);
                    $('[name="metadata[{key}][name]"]', tr).val(v['name']);
                    $('[name="metadata[{key}][key]"]', tr).val(v['key']).attr('readonly', true);;
                    $('[disabled]', tr).removeAttr("disabled").each(function() {
                        this.name = this.name.replace("{key}", count);
                    });
                    tbody.append(tr);
                }
            });
        }
    );
}