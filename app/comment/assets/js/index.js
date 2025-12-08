define(["admincp", "table"], function (admincp, Table) {

    // console.log(iCMS.url('/aaaa/aaaa'));
    // console.log(iCMS.url('./aaaa/aaaa'));
    // console.log(iCMS.url('./comment/aaaa'));
    // console.log(iCMS.url('comment/aaaa'));
    
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: 'comment',
            urls: {
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
                field: 'appid',
                title: 'fields:appid',
                setting: { map: "apps/mapper" },
                formatter: Table.format.search,
            },
            {
                field: 'cid',
                title: 'fields:nodeName',
                setting: { map: 'node/mapper' },
                search: "=",
                formatter: Table.format.search,
            },
            {
                field: 'userid',
                title: 'fields:user',
                text: "{{username}}",
                formatter: Table.format.search,
            },
            {
                field: 'iid',
                title: 'fields:title',
                text: "{{target_title}}",
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
                field: 'reply_count',
                title: 'fields:reply_count',
                formatter: function (value, row, index) {
                    var url = iCMS.url('./comment/reply/ids/'+row['id']);
                    return '<a href="'+url+'" i="cms:ui:modal">'+value+'</a>';
                }
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
                title: 'fields:up") + '/' + Lang("down',
            }
            ]
        });
    };
});