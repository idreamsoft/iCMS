define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $tableId = $tableId || $('#table');
        var $table = Table.init($tableId, {
            app: $APP.name,
            columns: [{
                field: 'id',
                title: 'fields:id',
                width: "90",
                sortable: true
            },
            {
                field: 'name',
                title: 'fields:name',
                input_update: true,
                formatter: Table.format.input,
            }, {
                field: 'level',
                title: '组等级',
                input_update: true,
                formatter: function (value, row, index) {
                    if (row['type'] == 0) {
                        return Table.format.input.call(this, value, row, index);
                    } else {
                        return '-';
                    }
                }
            }, {
                field: 'type',
                title: 'fields:type',
                formatter: Table.format.search,
            },
            // {
            //     field: 'money',
            //     title: 'fields:money',
            //     input_update: true,
            //     formatter: Table.format.input,
            // }, {
            //     field: 'credit',
            //     title: 'fields:credit',
            //     input_update: true,
            //     formatter: Table.format.input,
            // }, {
            //     field: 'scores',
            //     title: 'fields:scores',
            //     input_update: true,
            //     formatter: Table.format.input,
            // }, {
            //     field: 'free',
            //     title: 'fields:free',
            //     input_update: true,
            //     formatter: Table.format.input,
            // },
            {
                field: 'status',
                title: 'fields:status',
                input_update: true,
                formatter: Table.format.status,
            },
            {
                field: 'operate',
                buttons: [{
                    title: Lang('设置后台权限'),
                    class: 'btn btn-alt-primary',
                    icon: 'fa fa-fw fa-crown',
                    url: './access_app/id/{{id}}',
                    visible: function (row) {
                        return row.isAdminRole && !row.isSuperRole;
                    }
                }, {
                    title: Lang('设置节点权限'),
                    class: 'btn btn-alt-primary',
                    icon: 'fa fa-fw fa-sitemap',
                    url: './access_node/id/{{id}}',
                    visible: function (row) {
                        return row.isAdminRole && !row.isSuperRole;
                    }
                }],
                formatter: Table.format.operate,
            }
            ]
        });
    };
});