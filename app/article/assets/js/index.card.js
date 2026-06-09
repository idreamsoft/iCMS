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
                cardView:true,
                showHeader:false,
                columns: [
                {
                    field: 'title',
                    title: 'fields:title',
                    class: "text-nowrap w-25 h-100",
                    formatter: function (value, row, index) {
                        var html = [
                            `<div class="custom-card">`,
                            `<div class="d-flex align-items-center"><img src="${row.pic_url}"/></div>`,
                            `<a  href="${row.url}" class="d-flex align-items-center">${row.title}</a>`,
                            `</div>`
                        ];
                        // console.log(html);
                        return html.join(' ');
                    },
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