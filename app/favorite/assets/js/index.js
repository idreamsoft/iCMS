define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                detail: false
            },
            columns: [{
                field: 'id',
                title: 'fields:id',
                width: "90",
                sortable: true
            },
            {
                field: 'title',
                title: 'fields:title',
            },
            {
                field: 'userid',
                text: '{{nickname}}',
                title: 'fields:user',
                formatter: Table.format.search,
            },

            {
                field: 'created',
                title: 'fields:created',
                formatter: Table.format.datetime
            },
            {
                field: 'status',
                title: 'fields:status',
                formatter: Table.format.status,
            }]
        });
    };
});