define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                detail: false,
                trash: false,
                publish: false,
            },
            columns: [{
                field: 'id',
                title: 'fields:id',
                width: "90",
                sortable: true
            },
            {
                field: 'sortnum',
                title: 'fields:sortnum',
            },
            {
                field: 'app',
                title: 'fields:app',
                setting: { field: 'sapp' },
                formatter: Table.format.search,
            }, {
                field: 'name',
                title: 'fields:name',
            },
            {
                field: 'field',
                title: 'fields:field',
                formatter: Table.format.search,
            },

            {
                field: 'val',
                title: 'fields:val',
            },
            {
                field: 'status',
                title: 'fields:status',
                formatter: Table.format.status,
            },
            {
                field: 'operate',
                buttons: [{
                    title: Lang('copy'),
                    class: 'btn btn-alt-primary',
                    ui: 'cms:ui:ajax',
                    icon: 'fa fa-fw fa-copy',
                    url: './copy/id/{{id}}'
                }],
                formatter: Table.format.operate,
            }
            ]
        });
    };
});