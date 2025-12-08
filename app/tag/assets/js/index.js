define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                detail: false,
            },
            columns: [{
                field: 'id',
                title: 'fields:id',
                sortable: true
            },
            {
                field: 'name',
                title: 'fields:name',
                url:'{{url}}',
                formatter: Table.format.link,
            },
            {
                field: 'cid',
                title: 'fields:nodeName',
                setting: { map: 'node/mapper' },
                search: "=",
                formatter: Table.format.search,
            },
            {
                field: 'tcid',
                title: 'fields:tagNodeName',
                setting: { map: 'node/mapper' },
                search: "=",
                formatter: Table.format.search,
            },
            {
                field: 'pid',
                title: 'fields:propName',
                setting: { map: "prop/mapper" },
                formatter: Table.format.flag,
            },
            {
                field: 'tkey',
                title: 'fields:tkey',
                input_update:true,
                formatter: Table.format.input,
            },
            {
                field: 'field',
                title: 'fields:field',
                formatter: Table.format.search,
            },
            {
                field: 'count',
                title: 'fields:count',
                formatter: function (value, row, index) {
                    var s = {
                        href: "javascript:;",
                        text: value,
                        format: '<a class="btn btn-sm btn-light" href="{{href}}" title="{{title}}" {{attr}}>{{text}}</a>',
                    }
                    s.attr = [
                        'data-bs-toggle="popover"',
                        'data-bs-html="true"',
                        'data-bs-placement="top"',
                        'data-bs-content="' + [
                            '总点击数: ' + row.hits,
                            '当天点击数: ' + row.hits_today,
                            '昨天点击数: ' + row.hits_yday,
                            '周点击数: ' + row.hits_week,
                            '月点击数: ' + row.hits_month,
                            '---------------',
                            '赞: ' + row.good,
                            '踩: ' + row.bad,
                            '评论数: ' + row.comment,
                            '收藏数: ' + row.favorite,
                        ].join('<br/>') + '"'
                    ].join(" ");
                    return iCMS.utils.display(s.format, s);
                }
            },
            {
                field: 'pubdate',
                title: 'fields:pubdate',
                formatter: Table.format.datetime
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