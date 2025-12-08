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
                field: 'send_uid',
                text: "{{send_name}}",
                title: 'fields:send_name',
                formatter: Table.format.search,
            },
            {
                field: 'receiv_uid',
                text: "{{receiv_name}}",
                title: 'fields:receiv_name',
                formatter: Table.format.search,
            },
            {
                field: 'content',
                title: 'fields:content',
                formatter: Table.format.content,
            },
            {
                field: 'sendtime',
                title: 'fields:datetime',
                formatter: function (value, row, index) {
                    var format = 'YYYY-MM-DD HH:mm:ss';
                    return (row['sendtime'] > 0 ? iCMS.utils.moment.unix(row['sendtime']).format(format) : '-') +
                        '<br />' +
                        (row['readtime'] > 0 ? iCMS.utils.moment.unix(row['readtime']).format(format) : '-')
                }
            },
            {
                field: 'type',
                title: 'fields:type',
                formatter: Table.format.flag,
            },
            {
                field: 'status',
                title: 'fields:status',
                formatter: Table.format.status,
            },
            ]
        });
    };
});