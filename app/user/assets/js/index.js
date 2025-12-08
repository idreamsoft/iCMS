define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                detail: false
            },
            columns: [{
                field: 'id',
                title: 'fields:id',
                width: "90",
                sortable: true
            },
            {
                field: 'account',
                title: '账号',
                // width: "90",
                formatter: function (value, row, index) {
                    return '<div style="text-overflow:ellipsis;white-space:nowrap;word-break:keep-all;overflow: hidden; width: 90px; ">' + value + '</div>'
                }
            },
            {
                field: 'nickname',
                title: 'fields:nickname',
            }, {
                field: 'phone',
                title: 'fields:phone',
            },
            {
                field: 'role_id',
                text: '{{role_name}}',
                title: 'fields:role',
                formatter: Table.format.search,
            },
            {
                field: 'regdate',
                title: 'fields:reginfo',
                formatter: function (value, row, index) {
                    var format = 'YYYY-MM-DD HH:mm:ss';
                    return row['regip'] +
                        '<br />' +
                        (row['regdate'] > 0 ? iCMS.utils.moment.unix(row['regdate']).format(format) : '-')

                }
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
                    title: Lang('login'),
                    class: 'btn btn-alt-primary',
                    icon: 'fa fa-fw fa-sign-in-alt',
                    url: './login/userid/{{id}}',
                    target: '_blank',
                    visible: function (row) {
                        return $APP.admin.isSuperRole;
                    }
                }, {
                    title: Lang('查看文章'),
                    class: 'btn btn-alt-primary',
                    icon: 'fa fa-fw fa-list-alt',
                    ui:"cms:ui:modal",
                    url: '/article/index/user/userid/{{id}}/pt/0'
                }],
                formatter: Table.format.operate,
            }
            ]
        });
    };
});