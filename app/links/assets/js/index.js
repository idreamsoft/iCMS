define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                detail: false,
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
                field: 'cid',
                title: 'fields:cid',
                formatter: Table.format.search,
            },
            {
                field: 'name',
                title: 'fields:name',
            },
            {
                field: 'url',
                title: 'fields:url',
                formatter: Table.format.link,
            },
            {
                field: 'sortnum',
                title: 'fields:sortnum',
            }
            ]
        });
    };
});