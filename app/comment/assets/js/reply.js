define(["admincp", "table"], function (admincp, Table) {
    
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                index: window.location.href,
                edit: false,
                // detail: false,
                trash: false,
                publish: false,
            },
            columns: [{
                field: 'id',
                title: 'fields:id',
                width: "90",
                sortable: true
            },
            {
                field: 'comment_id',
                title: 'fields:comment_id',
                formatter: Table.format.search,
            },
            {
                field: 'userid',
                title: 'fields:user',
                text: "{{username}}",
                formatter: Table.format.search,
            },
            {
                field: 'content',
                title: 'fields:content',
                formatter: Table.format.content,
            },
            {
                field: 'status',
                title: 'fields:status',
                formatter: Table.format.status,
            },
            {
                field: 'ip',
                title: 'fields:ip',
                formatter: Table.format.search,
            },
            {
                field: 'create_time',
                title: 'fields:datetime',
                formatter: function (value, row, index) {
                    var format = 'YYYY-MM-DD HH:mm:ss';
                    return (row['create_time'] > 0 ? iCMS.utils.moment.unix(row['create_time']).format(format) : '-') +
                        '<br />' +
                        (row['update_time'] > 0 ? iCMS.utils.moment.unix(row['update_time']).format(format) : '-')
                }
            },
            {
                field: 'up',
                text: "{{up}}/{{down}}",
                title: 'fields:up_down_text',
            }
            ]
        });
    };
});