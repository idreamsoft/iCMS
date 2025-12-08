define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                // edit: false,
                update: false,
                detail: false,
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
                field: 'cid',
                title: 'fields:nodeName',
                setting: { map: 'node/mapper' },
                formatter: Table.format.search,
            },
            {
                field: 'rid',
                title: 'fields:ruleName',
                setting: { map: 'spiderRule/mapper' },
                buttons: [
                    {
                        name: 'edit_rule',
                        text: '',
                        title: Lang('edit rule'),
                        class: 'btn btn-alt-light',
                        ui:"cms:ui:modal",
                        icon: 'fa fa-fw fa-edit',
                        url: '/spiderRule/edit/id/{{rid}}',
                        target: '_blank',
                    },
                ],
                formatter: function (value, row, index) {
                    return Table.format.search.call(this, value, row, index) +
                        Table.format.buttons.call(this, value, row, index)
                },
            },
            {
                field: 'poid',
                title: 'fields:postName',
                setting: { map: 'spiderPost/mapper' },
                buttons: [
                    {
                        name: 'edit_post',
                        text: '',
                        title: Lang('edit post'),
                        class: 'btn btn-alt-light',
                        icon: 'fa fa-fw fa-edit',
                        ui:"cms:ui:modal",
                        url: '/spiderPost/edit/id/{{poid}}',
                        target: '_blank',
                    },
                ],
                formatter: function (value, row, index) {
                    return Table.format.search.call(this, value, row, index) +
                        Table.format.buttons.call(this, value, row, index)
                },
            },
            {
                field: 'lastupdate',
                title: 'fields:lastupdate',
                formatter: Table.format.datetime,
            },
            {
                field: 'auto',
                title: 'fields:auto',
                setting: {
                    map: { '0': '未标记', '1': '标记' }
                },
                formatter: Table.format.flag,
            },
            {
                field: 'test',
                title: 'fields:test',
                buttons: [
                    {
                        name: 'test_rule',
                        text: '',
                        title: Lang('test rule'),
                        class: 'btn btn-alt-primary',
                        ui:"cms:ui:modal",
                        icon: 'fa fa-fw fa-bolt',
                        url: '/spiderRule/test/rid/{{rid}}',
                    },
                    {
                        name: 'test_project',
                        text: '',
                        title: Lang('test project'),
                        class: 'btn btn-alt-primary',
                        ui:"cms:ui:modal",
                        icon: 'fa fa-fw fa-bolt',
                        url: '/spiderRule/test/pid/{{id}}',
                    }
                ],
                formatter: Table.format.buttons,
            },
            {
                field: 'crawl',
                title: 'fields:crawl',
                buttons: [
                    {
                        name: 'crawl_manual',
                        text: '',
                        title: Lang('crawl manual'),
                        class: 'btn btn-alt-primary',
                        ui:"cms:ui:modal",
                        icon: 'fa fa-fw fa-hand-point-up',
                        url: '/spider/manual/pid/{{id}}',
                    },
                    {
                        name: 'crawl_start',
                        text: '',
                        title: Lang('crawl start'),
                        class: 'btn btn-alt-success',
                        // ui: 'cms:spider:crawl:auto',
                        // ui: 'cms:ui:ajax',
                        // target: '_blank',
                        ui:"cms:ui:modal",
                        icon: 'fa fa-fw fa-play',
                        url: '/spider/auto/pid/{{id}}',
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
                        if(ret.code){
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