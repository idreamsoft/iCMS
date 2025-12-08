define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $tableId = $tableId || $('#table');
        var $table = Table.init($tableId, {
            app: $APP.name,
            urls: {
                "index": './list',
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
                width: "90",
                sortable: true,
                input_update: true,
                formatter: Table.format.input
            },
            {
                field: 'rootid',
                text: '{{rootid_text}}',
                title: 'fields:rootName',
                formatter: Table.format.search,
            },
            {
                field: 'name',
                title: 'fields:name',
                input_update: true,
                formatter: Table.format.input
            },
            {
                field: 'dir',
                title: 'fields:dir',
                input_update: true,
                formatter: Table.format.input,
            },
            {
                field: 'title',
                title: 'fields:title',
                input_update: true,
                formatter: Table.format.input,
            },
            {
                field: 'description',
                title: 'fields:description',
                input_update: true,
                formatter: Table.format.input,
            },
            {
                field: 'count',
                title: 'fields:count',
            },
            {
                field: 'status',
                title: 'fields:status',
                formatter: Table.format.status,
            },
            {
                field: 'operate',
                buttons: [{
                    title: Lang('links'),
                    class: 'btn btn-alt-primary',
                    target:'_blank',
                    icon: 'fa fa-fw fa-link',
                    url: '{{url}}'
                },{
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