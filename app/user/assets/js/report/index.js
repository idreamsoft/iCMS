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
                field: 'app',
                title: 'fields:app',
            },
            {
                field: 'iid',
                title: 'fields:iid',
            },
            {
                field: 'userid',
                title: 'fields:userid',
            },
            {
                field: 'ip',
                title: 'fields:ip',
            },
            {
                field: 'reason',
                title: 'fields:reason',
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