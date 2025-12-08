define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                detail: false,
                publish: false,
                edit: false,
                trash: false,
            },
            columns: [{
                field: 'id',
                title: 'fields:id',
                sortable: true
            },
            {
                field: 'name',
                title: 'fields:name',
            },
            {
                field: 'app',
                title: 'fields:app',
                formatter: Table.format.text,
            },
            {
                field: 'description',
                title: 'fields:description',
                formatter: Table.format.content,
            },
            {
                field: 'status',
                title: 'fields:status',
                formatter: Table.format.status,
            },
            {
                field: 'buttons',
                title: '内容管理',
                buttons: [
                    {
                        name: 'dashboard',
                        text: '',
                        title: Lang('表单'),
                        class: 'btn btn-alt-primary',
                        icon: 'fa fa-fw fa-dashboard',
                        url: '{{url}}',
                        target: '_blank',
                        visible: function (row) {
                            return true;
                        }
                    },
                    {
                        name: 'data',
                        text: '',
                        title: Lang('表单内容'),
                        class: 'btn btn-alt-primary',
                        icon: 'fa fa-fw fa-list-alt',
                        url: '/formsData/index/fid/{{id}}',
                        target: '_blank',
                        visible: function (row) {
                            return true;
                        }
                    },
                    {
                        name: 'submit',
                        text: '',
                        title: Lang('添加表单内容'),
                        class: 'btn btn-alt-primary',
                        icon: 'fa fa-fw fa-plus-circle',
                        url: '/formsData/add/fid/{{id}}',
                        target: '_blank',
                        visible: function (row) {
                            return true;
                        }
                    }
                ],
                formatter: Table.format.buttons,
            }
            ]
        });
    };
});