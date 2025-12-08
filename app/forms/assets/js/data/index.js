define(["admincp", "table"], function (admincp, Table) {
    return function () {
        if (!app.fid) {
            Layer.alert('sf');
            return;
        }
        var $table = $table || $('#table');
        var columns = [{
            field: 'id',
            title: 'fields:id',
            width: "90",
            sortable: true
        }];
        columns.push.apply(columns, app.columns())

        $.each(columns, function (idx, value) { 
            if(value._formatter){
                columns[idx]['formatter'] = Table.format[value._formatter];
            }
        });

        console.log(columns);
        Table.init($table, {
            app: $APP.name,
            urls: {
                index: './index/fid/' + app.fid,
                delete: './delete/fid/' + app.fid,
                edit: './edit/fid/' + app.fid,
                detail: './detail/fid/' + app.fid,
                update: './update/fid/' + app.fid,
                trash: false,
                publish: false
            },
            columns
        });
    };
});