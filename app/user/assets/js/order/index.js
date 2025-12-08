define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                trash: false,
                publish: false,
                edit: false,
            },
            columns: [{
                field: 'id',
                title: 'fields:id',
                width: "90",
                sortable: true
            },
            {
                field: 'type',
                title: 'fields:type',
            },
            {
                field: 'subject',
                title: 'fields:subject',
            },
            {
                field: 'userid',
                title: 'fields:userid',
            },
            {
                field: 'order_no',
                title: 'fields:order_no',
            },
            {
                field: 'transaction_id',
                title: 'fields:transaction_id',
            },
            {
                field: 'amount',
                title: 'fields:amount',
            },
            {
                field: 'product_id',
                title: 'fields:product_id',
            },
            {
                field: 'product_type',
                title: 'fields:product_type',
            },
            {
                field: 'channel',
                title: 'fields:channel',
            },
            {
                field: 'remark',
                title: 'fields:remark',
            },
            {
                field: 'create_time',
                title: 'fields:create_time',
                setting: { format: "YYYY-MM-DD HH:mm:ss" },
                formatter: Table.format.datetime,
            }
            ]
        });
    };
});