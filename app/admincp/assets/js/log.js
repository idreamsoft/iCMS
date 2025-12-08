define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $tableId = $tableId || $('#table');
        var $table = Table.init($tableId, {
            app: $APP.name,
            urls: {
                index: './index',
                add: false,
                edit: false,
                detail: "./detail",
                publish: false,
                update: false,
                trash: false,
                import: false,
                delete: false,
            },
            fixedRightNumber: 0,
            columns: [{
                field: 'id',
                title: Lang("id"),
                width: "90",
                sortable: true
            },
            {
                field: 'uid',
                text: '{{username}}',
                title: Lang("admin id"),
                formatter: Table.format.search,
            },
            {
                field: 'app',
                title: Lang("app"),
                setting: { field: 'sapp' },
                formatter: Table.format.search,
            },
            {
                field: 'method',
                title: Lang("method"),
                formatter: Table.format.search,
            }, {
                field: 'ip',
                title: Lang("ip"),
                formatter: Table.format.search,
            },
            {
                field: 'uri',
                title: Lang("uri"),
                formatter: function (value, row, index) {
                    var format = 'YYYY-MM-DD HH:mm:ss';
                    var value = row['uri'] +
                        '<br />' +
                        (row['create_time'] > 0 ? iCMS.utils.moment.unix(row['create_time']).format(format) : '-')
                    return Table.format.content.call(this, value, row, index);
                }
            },
            {
                field: 'useragent',
                title: Lang("useragent"),
                formatter: function (value, row, index) {
                    var value = row['useragent'] +
                        '<br />' +
                        row['referer'];
                    return Table.format.content.call(this, value, row, index)
                }
            }
            ]
        });
    };
});