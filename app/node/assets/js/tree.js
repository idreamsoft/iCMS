define(["iCMS", 'treeview', 'jquery-ui'], function (iCMS, treeview) {
    return function () {
        var update_url = iCMS.url('./update_sort',false);
        var url = iCMS.url('./tree/expanded/1',false);
        var nodeTree = $("#nodeTree").treeview({
            update_url, url,
            template: iCMS.utils.template(),
            tplId: 'tree-li',
            collapsed: false,
            sortable: function (el) {
                $(el).sortable({
                    delay: 300,
                    helper: "clone",
                    placeholder: "ui-state-highlight",
                    start: function (event, ui) {
                        $(ui.item).show().css({
                            'opacity': 0.5
                        });
                    },
                    stop: function (event, ui) {
                        $(ui.item).css({
                            'opacity': 1
                        });

                        var ul = ui.item.parent();
                        // var $data = $("input.sortnum", ul).serializeJSON();
                        // console.log($data);
                        var sortnum = [];
                        $("input.sortnum", ul).each(function (i) {
                            $(this).val(i);
                            var id = $(this).data("id");
                            sortnum.push(id);
                        });
                        iCMS.api.post(update_url, { sortnum });

                    }
                }).disableSelection();
            },
            animated: "medium",
            control: "#tree_control",
            callback: function (a) {
                // console.log('sdfsdf');
            }
        });
    };
});