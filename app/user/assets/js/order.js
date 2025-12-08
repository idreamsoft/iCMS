define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                index: './order',
                add: false,
                edit: false,
                detail: "./detail",
                publish: false,
                update: false,
                trash: false,
                import: false,
                delete: false,
            },
            columns: [{
                field: 'id',
                title: Lang("id"),
                width: "90",
                sortable: true
            },

            {
                field: 'subject',
                title: Lang("subject"),
            }, {
                field: 'order_no',
                title: Lang("order_no"),
            },
            {
                field: 'trade_id',
                title: Lang("trade_id"),
                formatter: Table.format.search,
            },
            {
                field: 'amount',
                title: Lang("amount"),
            },
            {
                field: 'create_time',
                title: Lang("create_time"),
                formatter: Table.format.datetime
            },
            {
                field: 'status',
                title: Lang("status"),
                formatter: Table.format.status,
            },
            {
                field: 'operate',
                formatter: Table.format.operate,
            }
            ]
        });
    };
});