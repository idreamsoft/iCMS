define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                edit: false,
                update: false,
                trash: false,
                publish: false
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
                field: 'title',
                title: 'fields:title',
            },
            {
                field: 'indexid',
                title: 'fields:indexid',
                formatter: Table.format.search,
            },
            {
                field: 'rid',
                title: 'fields:rid',
                formatter: Table.format.search,
            },
            {
                field: 'pid',
                title: 'fields:pid',
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
                field: 'addtime',
                title: 'fields:addtime',
                // formatter: Table.format.datetime,
                formatter: function (value, row, index) {
                    var format = 'YYYY-MM-DD HH:mm';
                    return (row['addtime'] > 0 ? iCMS.utils.moment.unix(row['addtime']).format(format) : '-') +
                        '<br />' +
                        (row['pubdate'] > 0 ? iCMS.utils.moment.unix(row['pubdate']).format(format) : '-')
                },
            },
            {
                field: 'publish',
                title: 'fields:publish',
                // setting: {map: 'publishMap'},
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