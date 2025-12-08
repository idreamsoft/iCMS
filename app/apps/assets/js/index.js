define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            editModal:false,
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
                text: '{{app}} {{version}}',
                title: 'fields:appver',
                formatter: Table.format.text,
            },
            {
                field: 'apptype',
                title: 'fields:apptype',
            },
            {
                field: 'status',
                title: 'fields:status',
                formatter: Table.format.status,
            },
            {
                field: 'operate',
                buttons: [{
                    name: 'index',
                    text: '',
                    title: Lang('内容管理'),
                    class: 'btn btn-alt-success',
                    icon: 'fa fa-fw fa-list-alt',
                    url: '{{app}}/index',
                    target: '_blank',
                    visible: function (row) {
                        if (row.is_content) {
                            return true;
                        }
                        return false;
                    }
                },
                {
                    name: 'add',
                    text: '',
                    title: Lang('添加内容'),
                    class: 'btn btn-alt-success',
                    icon: 'fa fa-fw fa-plus-circle',
                    url: '{{app}}/add',
                    target: '_blank',
                    visible: function (row) {
                        if (row.is_content) {
                            return true;
                        }
                        return false;
                    }
                },
                {
                    name: 'pack',
                    text: '',
                    title: Lang('打包'),
                    class: 'btn btn-alt-danger',
                    icon: 'fa fa-fw fa-file-zipper',
                    url: './pack/id/{{id}}',
                    target: '_blank',
                    visible: function (row) {
                        if (row.is_pack) {
                            return true;
                        }
                        return false;
                    }
                }
                ],
                formatter: Table.format.operate,
            }
            ]
        });
    };
});