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
                field: 'account',
                title: 'fields:account',
            },
            {
                field: 'nickname',
                title: 'fields:nickname',
            },
            {
                field: 'role_id',
                text: '{{role_name}}',
                title: 'fields:role',
                formatter: Table.format.search,
            },
            {
                field: 'lastloginip',
                title: 'fields:lastlogin',
                formatter: function (value, row, index) {
                    var format = 'YYYY-MM-DD HH:mm:ss';
                    return row['lastloginip'] +
                        '<br />' +
                        (row['lastlogintime'] > 0 ? iCMS.utils.moment.unix(row['lastlogintime']).format(format) : '-')

                }
            },
            {
                field: 'status',
                title: 'fields:status',
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
                        return !row.isSuperAdmin && $APP.admin.isSuperRole;
                    }
                }, {
                    title: Lang('设置节点权限'),
                    class: 'btn btn-alt-primary',
                    icon: 'fa fa-fw fa-sitemap',
                    url: './access_node/id/{{id}}',
                    visible: function (row) {
                        return !row.isSuperAdmin && $APP.admin.isSuperRole;
                    }
                }],
                formatter: Table.format.operate,
            }
            ]
        });
    };
});