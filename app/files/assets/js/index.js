define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                trash: false
            },
            columns: [{
                field: 'id',
                title: 'fields:id',
                width: "90",
                sortable: true
            },
            {
                field: 'userid',
                title: 'fields:userid',
                formatter: Table.format.search,
            },
            {
                field: 'path',
                url: '{{href}}',
                ui: 'cms:ui:tooltip',
                title: 'fields:path',
                formatter: Table.format.link,
            },
            {
                field: 'ext',
                text: '{{icon}}',
                title: 'fields:ext',
                formatter: Table.format.search,
            },
            {
                field: 'size',
                text: '{{sizeUnit}}',
                title: 'fields:size',
                formatter: Table.format.text,
            },
            {
                field: 'time',
                title: 'fields:time',
                formatter: Table.format.datetime
            },
            {
                field: 'operate',
                buttons: [{
                    title: Lang('download'),
                    class: 'btn btn-alt-primary',
                    ui: 'cms:ui:ajax',
                    icon: 'fa fa-fw fa-download',
                    url: './download/id/{{id}}',
                    visible: function (row) {
                        return row.source.isUrl();
                    }
                }, {
                    title: Lang('download'),
                    class: 'btn btn-alt-primary',
                    ui: 'cms:ui:ajax',
                    icon: 'fa fa-fw fa-download',
                    url: './download/id/{{id}}/noWatermark/0',
                    visible: function (row) {
                        return row.source.isUrl();
                    }
                }, {
                    title: Lang('upload'),
                    class: 'btn btn-alt-primary',
                    ui:'cms:ui:modal',
                    icon: 'fa fa-fw fa-upload',
                    url: './edit/id/{{id}}',
                }],
                // formatter: Table.format.operate,
            }
            ]
        });
    };
});