define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
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
                field: 'rid',
                title: 'fields:ruleName',
                setting: { map: 'spiderRule/mapper' },
                formatter: Table.format.search,
            },
            {
                field: 'pid',
                title: 'fields:projectName',
                setting: { map: 'spiderProject/mapper' },
                formatter: Table.format.search,
            },
            {
                field: 'app',
                title: 'fields:app',
                setting: { map: "apps/mapper" },
                formatter: Table.format.search,
            },
            {
                field: 'type',
                title: 'fields:type',
                formatter: Table.format.search,
            },
            {
                field: 'date',
                title: 'fields:date',
                formatter: Table.format.search,
            },
            {
                field: 'msg',
                title: 'fields:msg',
            },
            {
                field: 'url',
                title: 'fields:url',
            },
            {
                field: 'work',
                title: 'fields:work',
            }
            ]
        });
    };
});