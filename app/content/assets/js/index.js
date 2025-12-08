define(["admincp", "table"], function (admincp, Table) {
    return function () {
        var $table = $table || $('#table');
        Table.init($table, {
            app: $APP.name,
            urls: {
                "index": window.location.href,
            //     "add": "article/add",
            //     "edit": "article/edit",
            //     "detail": "article/detail",
            //     "publish": "article/update",
            //     "trash": "article/trash",
            //     "import": "article/import",
            //     "delete": "article/delete"
            },
            // search:true,
            columns: [{
                field: 'id',
                title: 'fields:id',
                width: "90",
                sortable: true
            },
            {
                field: 'cid',
                title: 'fields:nodeName',
                setting: { map: 'node/mapper' },
                search: "=",
                formatter: Table.format.search,
            },
            {
                field: 'title',
                title: 'fields:title',
                class: "10%",
                formatter: function (value, row, index) {
                    var html = [Table.format.link.apply(this, arguments)];
                    if (row['postype'] == 0) {
                        html.push('<span class="badge bg-info">' + Lang("User") + '</span>');
                    }
                    // console.log(html);
                    return html.join(' ');
                },
            },
            {
                field: 'pid',
                title: 'fields:propName',
                setting: { map: "prop/mapper" },
                formatter: Table.format.flag,
            },
            {
                field: 'pubdate',
                title: 'fields:pubdate',
                formatter: function (value, row, index) {
                    var format = 'YYYY-MM-DD HH:mm';
                    return (row['pubdate'] > 0 ? iCMS.utils.moment.unix(row['pubdate']).format(format) : '-') +
                        '<br />' +
                        (row['postime'] > 0 ? iCMS.utils.moment.unix(row['postime']).format(format) : '-')
                },
            },
            {
                field: 'hits',
                title: 'fields:hits',
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
                field: 'status',
                title: 'fields:status',
                formatter: Table.format.status,
            }
            ]
        });
    };
});