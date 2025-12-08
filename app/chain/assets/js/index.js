define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls:{
                detail:false,
                trash:false,
                publish:false,
            },
            columns: [{
                field: 'id',
                title: 'fields:id',
                width: "90",
                sortable: true
            },
            {
                field: 'keyword',
                title: 'fields:keyword',
            },
            {
                field: 'replace',
                title: 'fields:replace',
                input_update: true,
                formatter: Table.format.input,
            }
            ]
        });
    };
});