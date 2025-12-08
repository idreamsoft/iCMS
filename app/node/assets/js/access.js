define(['ui'], function (UI) {
    const factory = {
        access_node: function () {
            // UI.tooltip(params);
            var $el = iCMS.$("cms:node:access:select");
            $el.each(function (index, element) {
                UI.run($(element), 'tooltip');
            });
            iCMS.api.post('./access_node', { id: $APP.get.id, key: 'node' }, function (json) {
                $.each(json.data.access, function (idx, ev) {
                    $("[value='" + ev + "']").prop("checked", true);
                });
            });
        },
        access_select: function ({ $el, el }) {
            var ev = $el.data("value");
            var type = $el.data("type");
            var checked = $el.prop("checked")
            console.log(checked);
            if (type == 'e') {
                $("[value$='" + ev + "']", "#node-list").prop("checked", checked);
            }
            if (type == 's') {
                $("[value^='" + ev + "']", "#node-list").prop("checked", checked);
            }
            if (type == 'r') {
                $.each(ev, function (idx, nodeid) {
                    $("[value^='" + nodeid + "']", "#node-list").prop("checked", checked);
                });
            }
        }
    }
    return factory;
});