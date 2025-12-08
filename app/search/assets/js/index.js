define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                detail: false,
                trash: false,
                publish: false,
                edit: false,
            },
            columns: [{
                field: 'id',
                title: 'fields:id',
                width: "90",
                sortable: true
            },{
                field: 'pid',
                title: 'fields:propName',
                setting: { map: "prop/mapper" },
                formatter: Table.format.flag,
            },
            {
                field: 'search',
                title: 'fields:search',
            },
            {
                field: 'times',
                title: 'fields:times',
                sortable: true
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