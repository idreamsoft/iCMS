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
                title: '类型',
            },
            {
                field: 'subject',
                title: '项目',
            },
            {
                field: 'userid',
                title: '用户ID',
            },
            {
                field: 'order_no',
                title: '订单号',
            },
            {
                field: 'transaction_id',
                title: '支付单号',
            },
            {
                field: 'amount',
                title: '金额',
            },
            {
                field: 'product_id',
                title: '产品ID',
            },
            {
                field: 'product_type',
                title: '产品类型',
            },
            {
                field: 'channel',
                title: '支付渠道',
            },
            {
                field: 'remark',
                title: '备注',
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