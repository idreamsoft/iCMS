define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                // edit: false,
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
                field: 'url',
                title: 'fields:url',
                buttons: [
                    {
                        title: Lang('manage url'),
                        class: 'btn btn-alt-primary',
                        icon: 'fa fa-fw fa-list',
                        url: '/spiderUrl/index/rid/{{id}}',
                        ui: 'cms:ui:modal',
                        // target: '_blank'
                    },
                    {
                        title: Lang('error url'),
                        class: 'btn btn-alt-primary',
                        icon: 'fa fa-fw fa-list',
                        url: '/spiderError/index/rid/{{id}}',
                        ui: 'cms:ui:modal',
                        // target: '_blank'
                    }
                ],
                formatter: Table.format.buttons,
            },
            {
                field: 'project',
                title: 'fields:project',
                buttons: [
                    {
                        title: Lang('manage project'),
                        class: 'btn btn-alt-primary',
                        icon: 'fa fa-fw fa-list ',
                        url: '/spiderProject/index/rid/{{id}}',
                        target: '_blank'
                    },
                    {
                        title: Lang('export project'),
                        class: 'btn btn-alt-primary',
                        icon: 'fa fa-fw fa-download',
                        url: '/spiderProject/export/rid/{{id}}',
                        target: 'iPHP_FRAME'
                    }
                ],
                formatter: Table.format.buttons,
            },
            {
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
                }, {
                    title: Lang('export'),
                    class: 'btn btn-alt-primary',
                    icon: 'fa fa-fw fa-download',
                    url: './export/id/{{id}}',
                    target: 'iPHP_FRAME'
                }, {
                    title: Lang('test rule'),
                    class: 'btn btn-alt-primary',
                    ui:"cms:ui:modal",
                    icon: 'fa fa-fw fa-bolt',
                    url: './test/rid/{{id}}',
                }
                ],
                formatter: Table.format.operate,
            }
            ]
        });
    };
});