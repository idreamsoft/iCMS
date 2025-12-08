define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                index: "./getTables",
                add: false,
                edit: false,
                detail: false,
                publish: false,
                update: false,
                trash: false,
                import: false,
                delete: false,
            },
            buttonsClass: ' hide',
            columns: [{
                field: 'id',
                title: Lang("id"),
                width: "90",
                formatter: function (value, row, index) {
                    return value + 1;
                }
            },
            {
                field: 'Name',
                title: Lang("Name"),
                setting: { type: 'hidden', name: 'tables' },
                formatter: function (value, row, index) {
                    var html = [value, Table.format.input.apply(this, arguments)];
                    return html.join('')
                }
            },

            {
                field: 'Rows',
                title: Lang("Rows"),
            },
            {
                field: 'Engine',
                title: Lang("Engine"),
            },
            {
                field: 'Data_length',
                text: '{{Data_length_text}}',
                title: Lang("Data_length"),
            },
            {
                field: 'Index_length',
                text: '{{Index_length_text}}',
                title: Lang("Index_length"),
            },
            {
                field: 'Length',
                title: Lang("Length")
            },
            {
                field: 'time',
                title: Lang("Create_time"),
                formatter: function (value, row, index) {
                    var format = 'YYYY-MM-DD HH:mm';
                    return (row['Create_time'] > 0 ? iCMS.utils.moment.unix(row['Create_time']).format(format) : '-') +
                        '<br />' +
                        (row['Update_time'] > 0 ? iCMS.utils.moment.unix(row['Update_time']).format(format) : '-')
                },
            },
            {
                field: 'Collation',
                title: Lang("Collation"),
            },
            {
                field: 'Comment',
                title: Lang("Comment"),
            },
            {
                field: 'operate',
                visible: false,
            }
            ]
        });
    };
});