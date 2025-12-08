define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                update: false,
                trash: false,
                publish: false
            },
            columns: [{
                field: 'id',
                title: 'fields:id',
                width: "90",
                sortable: true
            },
            {
                field: 'name',
                title: 'fields:name',
            },
            {
                field: 'app',
                title: 'fields:app',
                setting: { map: "apps/mapper" },
                formatter: Table.format.search,
            }, {
                field: 'operate',
                buttons: [{
                    title: Lang('copy'),
                    class: 'btn btn-alt-primary',
                    ui: 'cms:ui:ajax',
                    icon: 'fa fa-fw fa-copy',
                    url: './copy/id/{{id}}',
                    success: function (ret, data) {
                        if (ret.code) {
                            Table.method.refresh();
                        }
                    }
                }],
                formatter: Table.format.operate,
            }
            ]
        });
    };
});