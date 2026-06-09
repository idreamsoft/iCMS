define(["admincp", "table"], function (admincp, Table) {
    const factory = {
        index: function () {
            var $table = $table || $('#table');
            Table.init($table, {
                app: $APP.name,
                urls: {
                    "index": window.location.href,
                },
                cardView: true,
                showHeader: false,
                columns: [
                    {
                        field: 'path',
                        title: 'fields:path',
                        class: "text-nowrap w-50 h-100",
                        formatter: function (value, row, index) {
                            var html = [
                                `<div class="file-card d-flex flex-column border-0">`,
                                `<div class="text-center flex-grow-1 d-flex flex-column justify-content-center p-3">`
                            ];

                            // 根据文件类型显示不同类型的内容
                            switch (row.fileType) {
                                case 'image':
                                    html.push(`<img src="${row.url}" class="file-preview-img img-fluid rounded mb-2" style="max-height: 150px; object-fit: cover;" />`);
                                    break;
                                case 'video':
                                    html.push(`<img src="${row.iconUrl}" class="file-preview-img img-fluid rounded mb-2" style="max-height: 150px; object-fit: cover;" />`);

                                    // html.push(`
                                    //     <div class="file-preview-video position-relative">
                                    //         <video controls class="img-fluid rounded" style="max-height: 150px;">
                                    //             <source src="${row.url}" type="video/mp4">
                                    //             您的浏览器不支持视频播放。
                                    //         </video>
                                    //     </div>
                                    // `);
                                    break;
                                default:
                                    html.push(`<div class="file-icon d-flex align-items-center justify-content-center mb-2">${row.icon}</div>`);
                                    break;
                            }

                            // html.push(`</div>`);
                            html.push(`<div class="bg-transparent p-2">`);
                                html.push(`<div class="file-info d-flex text-muted small text-center">`);
                                    html.push(`<span class="file-time mb-1">创建：${iCMS.utils.moment(row.time*1000).format('YYYY-MM-DD HH:mm')}</span>`);
                                    html.push(`<span class="file-size">大小：${row.sizeUnit}</span>`);
                                html.push(`</div>`);
                            html.push(`</div>`);
                                html.push(`<a href="${row.url}" class="text-decoration-none" target="_blank">`);
                                html.push(`<span class="file-name text-wrap text-center">${row.source}</span>`);
                                html.push(`</a>`);

                            html.push(`</div>`);
                            html.push(`</div>`);
                            return html.join('');
                        },
                    }, {
                        field: 'operate',
                        buttons: [{
                            title: Lang('download'),
                            class: 'btn btn-alt-primary',
                            ui: 'cms:ui:ajax',
                            icon: 'fa fa-fw fa-download',
                            url: './download/id/{{id}}',
                            visible: function (row) {
                                return row.source.isUrl();
                            }
                        }, {
                            title: Lang('download'),
                            class: 'btn btn-alt-primary',
                            ui: 'cms:ui:ajax',
                            icon: 'fa fa-fw fa-download',
                            url: './download/id/{{id}}/noWatermark/0',
                            visible: function (row) {
                                return row.source.isUrl();
                            }
                        }, {
                            title: Lang('upload'),
                            class: 'btn btn-alt-primary',
                            ui: 'cms:ui:modal',
                            attr:{
                                "data-width": "600px",
                                "data-height": "450px"
                            },
                            icon: 'fa fa-fw fa-upload',
                            url: './edit/id/{{id}}',
                        }],
                        // formatter: Table.format.operate,
                    }
                ]
            });
        }
    }
    return factory;

});