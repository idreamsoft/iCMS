define(["admincp", "table"], function (admincp, Table) {
    const factory ={
        user: function () {
            return factory.index()
        },
        index: function () {
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
                    class: "text-nowrap w-25",
                    formatter: function (value, row, index) {
                        var html = [Table.format.link.apply(this, arguments)];
                        if (row['postype'] == 0) {
                            html.push('<i class="fa fa-fw fa-user text-primary-light"></i>');
                        }
                        if (row['pic']) {
                            html.push('<i class="fa fa-fw fa-image text-success"></i>');
                        }
                        if (row['title_extra']) {
                            html.push(`<br /><span class="py-1 px-3 rounded-pill bg-warning-light text-warning">${row['title_extra']}</span>`);
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
                    // formatter: Table.format.datetime,
                    formatter: function (value, row, index) {
                        var format = 'YYYY-MM-DD HH:mm';
                        return (row['pubdate'] > 0 ? iCMS.utils.moment.unix(row['pubdate']).format(format) : '-') +
                            '<br />' +
                            (row['postime'] > 0 ? iCMS.utils.moment.unix(row['postime']).format(format) : '-')
                    },
                },
                {
                    field: 'hits',
                    title: 'fields:hits_text',
                    formatter: function (value, row, index) {
                        var s = {
                            href: "javascript:;",
                            text: value,
                            format: '<a class="btn btn-sm btn-light" href="{{href}}" title="{{title}}" {{attr}}>{{text}}</a>',
                        }
                        s.attr = [
                            'i="cms:ui:popover"',
                            'data-html="true"',
                            'data-placement="top"',
                            'data-content="' + [
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
                }, {
                    field: 'operate',
                    buttons: [{
                        title: Lang('subApp'),
                        id: 'dropdown-default-primary',
                        class: 'btn btn-alt-primary dropdown-toggle',
                        icon: 'fa fa-fw fa-bars',
                        attr: {
                            "data-bs-toggle": "dropdown",
                            "aria-haspopup": "true",
                            "aria-expanded": "false",
                        },
                        visible: function(){
                            if(isArray($APP.context.subApp) && $APP.context.subApp.length > 0){
                                return true;
                            }
                            return false;
                        },
                        dropdown: $APP.context.subApp
                        // dropdown: [
                        //     {
                        //         'href': './dfg',
                        //         'text': 'fgh',
                        //         'class': 'btn-modal'
                        //     },
                        //     {
                        //         'href': './dfg',
                        //         'text': 'fgh',
                        //         'class': 'btn-modal'
                        //     },
                        // ]
                    }],
                    formatter: Table.format.operate,
                }
                ]
            });

            // $("#test").on("click",function (e) {
            //     e.preventDefault();
            //     var row = Table.method.refresh({
            //         'sortName': 'id',
            //         'sortOrder': 'asc'
            //     });
            //     // alert(JSON.stringify(row));
            //     // var that = this;
            //     // var href = $(this).attr("href");
            //     // console.log(href);
            //     // $.each(Table.ids, function (idx, value) {
            //     //     var url = iCMS.api.makeUrl(href, { id: value });
            //     //     console.log(url);
            //     //     iCMS.ui.modal(that, { url });
            //     // });
            //     // alert(this.href)
            // });
        }
    }
    return factory;

});