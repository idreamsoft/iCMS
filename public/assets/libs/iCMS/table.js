define(["jquery","iCMS", "bs-table", "bs-table-lang",  "bs-table-print", "bs-table-jumpto", "bs-table-fixed-columns"], function ($,iCMS) {
    "use strict";
    var doc = $(document);
    var $_GET = iCMS.utils.getQuery();
    var loadIdx = Layer.load(1);
    let Table = {
        $dom: $('#table'),
        response: {},
        options: {},
        params: {},
        cache: {},
        search: [],
        urls: {
            index: "index",
            add: "add",
            edit: "edit",
            detail: "detail",
            publish: "update",
            update: "update",
            trash: "trash",
            import: "import",
            delete: "delete",
        },
        defaults: {
            url: '',
            editModal: false,//是否开启编辑弹窗
            tablist: true,
            searchui: true,
            locale: 'zh-CN',
            sidePagination: "server",
            paginationLoop: false,
            paginationParts: [ /*'pageInfo',*/ 'pageInfoShort', 'pageSize', 'pageList'],
            pagination: true,
            paginationPreText: '‹',
            paginationNextText: '›',
            paginationSuccessivelySize: 1,
            paginationPagesBySide: 1,
            showJumpTo: true,
            // paginationUseIntermediate: true,//快速翻页 总数/2
            buttonsClass: "secondary",
            iconSize: 'sm',
            loadingFontSize: 16,
            search: false,
            searchText: '', //默认值
            customSearch: undefined,
            showSearchButton: false,
            silent: true, //静默刷新数据
            // searchAlign:"left",
            //searchHighlight:false,
            // buttonsToolbar:"#buttonsToolbar",
            cardView: isMobile.any, //设置true 为显示名片视图表，例如移动视图
            // detailView: false,//设置true为显示详细视图表。
            showColumns: true,
            showColumnsToggleAll: true,
            minimumCountColumns: 2,
            showRefresh: true,
            showToggle: true,
            showPrint: true,
            showFullscreen: true,
            showExport: true,
            buttonsOrder: ['tableReset', 'refresh', 'columns', 'toggle', 'export', 'exportDataType', 'fullscreen', 'print'],
            iconsPrefix: "",
            icons: {
                paginationSwitchDown: 'fa fa-caret-square-down',
                paginationSwitchUp: 'fa fa-caret-square-up',
                refresh: 'si si-refresh',
                toggleOff: 'si si-list',
                toggleOn: 'si si-grid',
                columns: 'si si-settings',
                detailOpen: 'si si-plus',
                detailClose: 'si si-close',
                fullscreen: 'si si-size-fullscreen',
                search: 'si si-magnifier',
                export: 'fa fa-download',
                print: 'fa fa-print',
                clearSearch: 'si si-trash'
            },
            // iconsPrefix: "fa",
            buttons: function () {
                return {
                    exportDataType: {
                        text: Lang("exportDataType"),
                        iconsPrefix: "",
                        icon: 'fa fa-fw fa-gears',
                        event: function () {
                            Layer.open({
                                type: 1,
                                title: Lang("exportDataType"),
                                content: $('#exportDataType'),
                                success: function (layero, index) {
                                    $('select.table-exportDataType').data('index', index)
                                }
                            });
                        },
                        attributes: {
                            title: Lang("exportDataType")
                        }
                    },
                    tableReset: {
                        text: Lang("tableReset"),
                        iconsPrefix: "",
                        icon: 'fa fa-fw fa-repeat',
                        class: 'table-btn-reset',
                        event: function () {
                            $(".table-btn-reset").click();
                        },
                        attributes: {
                            title: Lang("tableReset"),
                            // class: 'table-btn-reset'
                        }
                    },
                    // btnAdd: {
                    //     text: 'Add new row',
                    //     icon: 'fa-plus',
                    //     event: function () {
                    //         alert('Do some stuff to e.g. add a new row')
                    //     },
                    //     attributes: {
                    //         title: 'Add a new row to the table'
                    //     }
                    // }
                }
            },
            // checkboxClass: 'form-check-input',
            selectItemClass: 'form-check-input ids-input',
            selectItemName: 'ids[]',
            idField: 'id',
            uniqueId: 'id',
            sortName: 'id',
            sortOrder: 'desc',
            clickToSelect: true,
            virtualScroll: false,
            fixedColumns: true,
            fixedNumber: 2,
            fixedRightNumber: 1,
            // height: '602', 
            pageNumber: 1, //初始化加载第一页，默认第一页
            pageSize: 20, //分页20
            pageList: [10, 20, 25, 50, 100, 500, 1000, 'All'], //分页
            // singleSelect : true,//只能单选
            classes: "table table-sm text-nowrap  table-hover table-striped table-vcenter fs-sm",
            theadClasses: "bg-light text-black",
            toolbar: "#toolbar",
            exportDataType: "basic",
            exportTypes: ['json', 'xml','csv', 'txt', 'doc', 'excel', 'xlsx'],
            exportOptions: {
                fileName: function () {
                    return Lang('Export');
                }
            },
            // detailView:"true"
            // detailFormatter:function(index, row, $dom) { return '' }
            rowAttributes: function (row, index) { //tr 属性
                return {
                    'id': 'id' + row['id'],
                    // 'data-bs-toggle': 'popover',
                    // 'data-bs-placement': 'bottom',
                    // 'data-bs-trigger': 'hover',
                    // 'data-bs-content': [
                    //     'Index: ' + index,
                    //     'ID: ' + row.id,
                    //     'Name: ' + row.title,
                    //     'editor: ' + row.editor
                    // ].join(', ')
                }
            },
            onLoadError: function (status, jqXHR) {
                // console.log(status, jqXHR);
            },
            onLoadSuccess: function (data, status, jqXHR) {
                var json = jqXHR.responseJSON;
                if (json.code > 0) {
                    Table.response = data;
                    // Table.params['totalCount'] = data.total;
                    // Table.params['pageTotal'] = data.pageTotal;
                } else {
                    iCMS.request.success(json);
                }
            },
            responseHandler: function (response, jqXHR) {
                if (response.code > 0) {
                    return response.data;
                } else {
                    iCMS.request.success({
                        code: response.code,
                        message: Lang('Data load error'),
                        response
                    });
                    return [];
                }
            },
            // method: "GET", 
            queryParamsType: "", //searchText=&pageSize=10&pageNumber=1
            // queryParamsType:"limit",//search=&offset=0&limit=10

            queryParams: function (params) {
                // console.log(params, Table.params);
                params = $.extend({}, params, Table.params);
                params['page'] = params['pageNumber'];
                if (params['searchText']) {
                    params['keywords'] = params['searchText'];
                }

                if (!params['orderby']) {
                    params['orderby'] = params['sortName'] + ' ' + params['sortOrder'].toUpperCase()
                }
                // // console.log("queryParams:", params);
                if (params['pageSize'] > 100) {
                    Table.defaults.fixedColumns = false;
                    Table.defaults.fixedNumber = 0;
                    Table.defaults.fixedRightNumber = 0;
                }
                $.each(params, function (i, v) {
                    var ipt = $("[name=" + i + "]", "form.table-search");
                    if (ipt.length > 0) {
                        ipt.val(v).trigger("change");
                    }
                    var dp = $("[data-params-" + i + "='" + v + "']");
                    if (dp.length > 0) {
                        $("[data-params-" + i + "]").removeClass("active");
                        dp.addClass("active")
                    }
                });
                $.each(params, function (i, v) {
                    if (isUndef(v) || v === '') {
                        delete params[i];
                    }
                });
                // if (params.orderby) {
                //     // $('option[value="' + params.orderby + '"]', "select[name='orderby']").attr("selected", true);
                //     $("select[name='orderby']").val(params.orderby).trigger("change");
                // }
                console.log(params, Table.params);
                return params
            },
            columns: []
        },
        init: function ($table, options, func) {
            this.$dom = $table || $('#table');
            var params = $APP.context["params"] || {};
            this.params = $.extend({}, params, $_GET);
            this.makeOptions(options);

            if (Table.options.tablist) {
                this.ui.tablist();
            }
            if (Table.options.searchui) {
                this.ui.search();
            }

            if (Table.options.toolbar) {
                Table.ui.toolbar();
            }
            // console.log($.fn.bootstrapTable.VERSION);
            // $.fn.bootstrapTable.theme = "bootstrap5";
            $table.bootstrapTable('destroy')
                .bootstrapTable(Table.options);

            //在表体呈现并在DOM中可用之后触发
            $table.on('post-body.bs.table', function (e) {
                $('[name="btSelectAll"]').addClass("form-check-input");
                $('input', 'td.bs-checkbox').addClass("form-check-input ids-input");
                // events.all();
            })

            $table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
                var $toolbar = $(Table.options.toolbar);
                var idArr = Table.method.getIds();
                var $btns = $toolbar.find(".btn[disabled]");
                // save your data, here just save the current page
                if (idArr.length) {
                    $btns.prop("disabled", false).removeClass('disabled');
                } else {
                    $btns.prop("disabled", true).addClass('disabled');
                }
            });
            // // 触发所有事件时触发
            // $table.on('all.bs.table', function(name, args) {});
            // // 用户点击一行时触发
            // $table.on('click-row.bs.table', function(row, $dom, field) {});
            // // 用户双击一行时触发
            // $table.on('dbl-click-row.bs.table', function(row, $dom, field) {});
            // $table.on('refresh.bs.table', function(e, params) {
            //     // console.log(e, params);
            // });

            $table.on('load-success.bs.table', function (data) {
                Layer.close(loadIdx);
            }).on('load-error.bs.table', function (data) {
                iCMS.notify.error(Lang('errors:load'));
                Layer.close(loadIdx);
            }).on('export-started.bs.table', function (e, o) {
                // console.log(o);
                // return '1111';
            });

            doc.on('click', '.table-btn-refresh', function (e) {
                e.preventDefault();
                var $params = $(this).getDatas("params");
                Table.method.refresh($params);
            });
            doc.on('change', '.table-input-update', function (e) {
                e.preventDefault();

                Table.method.post(Table.urls.update, {
                    id: $(this).data('id'),
                    field: $(this).data('field'),
                    value: $(this).val()
                })
            });

            doc.on('change', '.table-exportDataType', function (e) {
                var index = $(this).data('index');
                Layer.close(index);
                // console.log($(this).val());
                Table.method.updateOptions({ exportDataType: $(this).val() });
            });
            // doc.on('click', '.table-btn-search', function (e) {
            doc.on("reset", "form.table-search", function (e) {
                this.reset();
                var form = $(this);
                form[0].reset();
                form.find('input,select').val(null).trigger("change");
                Table.params = {};
                Table.method.refresh(null);
            }).on("submit", "form.table-search", function (e) {
                e.preventDefault();
                var form = $(this);
                var $data = form.serializeJSON();
                // console.log("form.submit:",$data);
                // $.each($data, function (i, v) {
                //     if (v === '' || isUndef(v)) delete $data[i];
                // });
                Table.params = {};
                Table.method.refresh($data);
            });

            if (typeof (func) === "function") {
                func.call(this);
            }
            return $table;
        },
        makeOptions: function (option) {
            var options = $.extend({}, Table.defaults, option);
            this.makeUrls(options);
            if (Table.urls.index) {
                // console.log(window.location.href);
                // options.url = iCMS.url(window.location.href);
                // options.url = iCMS.url(Table.urls.index);
                options.url = Table.urls.index;
            }
            options.columns = Table.makeColumns(options);

            if (!isUndef($_GET['pageSize'])) {
                options['pageSize'] = $_GET['pageSize'];
            }
            if ($("#buttonsToolbar").length > 0) {
                options['buttonsToolbar'] = "#buttonsToolbar";
            }
            this.options = options;
            // console.log(this.options);
            return options;
        },
        method: {
            post: function (url, params, success) {
                iCMS.api.post(url, params, function (json) {
                    if (json.code) {
                        iCMS.notify.success(json.message);
                        if (typeof success === 'function') {
                            success();
                        }
                    }
                })
            },
            resetWidth: function () {
                return Table.$dom.bootstrapTable('resetWidth');
            },
            destroy: function () {
                return Table.$dom.bootstrapTable('destroy');
            },
            //刷新表格
            refresh: function (params, url) {
                var options = {
                    pageNumber: 1, query: {
                        page: 1,
                        pageNumber: Table.defaults.pageNumber,
                        pageSize: Table.defaults.pageSize,
                        sortName: Table.defaults.sortName,
                        sortOrder: Table.defaults.sortOrder
                    }
                };

                if (options.query['pageSize']) {
                    options['pageSize'] = parseInt(options.query['pageSize']);
                    delete options.query['pageSize'];
                }
                if (url) options['url'] = url;

                Table.params = $.extend({}, Table.params, params);

                return Table.$dom.bootstrapTable('refresh', options);
            },
            called: function (methods, params) {
                if (isUndef(params)) {
                    return Table.$dom.bootstrapTable(methods);
                }
                return Table.$dom.bootstrapTable(methods, params);
            },
            //从表中删除数据
            remove: function (values, field) {
                var field = field || 'id'
                return Table.$dom.bootstrapTable('remove', { field, values })
            },
            check: function (index) {
                return Table.$dom.bootstrapTable('check', { index })
            },
            uncheck: function (index) {
                return Table.$dom.bootstrapTable('uncheck', { index });
            },
            checkBy: function (values, field) {
                var field = field || 'id'
                return Table.$dom.bootstrapTable('checkBy', { field, values })
            },
            uncheckBy: function (index) {
                var field = field || 'id'
                return Table.$dom.bootstrapTable('uncheckBy', { field, values });
            },
            //获取配置
            getOptions: function () {
                return Table.$dom.bootstrapTable('getOptions');
            },
            //更新配置
            updateOptions: function (options) {
                return Table.$dom.bootstrapTable('refreshOptions', options);
            },
            //获取表格数据数组
            getData: function (index) {
                var data = Table.$dom.bootstrapTable('getData')
                return isUndef(index) ? data : data[index];
            },
            //获取行数据
            getRow: function (id) {
                return Table.$dom.bootstrapTable('getRowByUniqueId', id);
            },
            removeRow: function (ids) {
                if (!isArray(ids)) {
                    ids = [ids];
                }
                $.each(ids, function (k, id) {
                    Table.$dom.bootstrapTable('removeByUniqueId', id);
                });
            },

            // showRow: function (index) {
            //     return Table.$dom.bootstrapTable('showRow', {index});
            // },
            // showRowById: function (uniqueId) {
            //     return Table.$dom.bootstrapTable('showRow', {uniqueId});
            // },
            updateRow: function (id, row) {
                return Table.$dom.bootstrapTable('updateByUniqueId', { id, row });
            },
            //获取所选行数据
            getSelections: function () {
                return Table.$dom.bootstrapTable('getSelections');
            },
            //获取所选行id
            getIds: function () {
                var data = Table.method.getSelections();
                return $.map(data, function (row) {
                    return row.id
                });
            },
        },
        makeUrls: function (options) {
            var urls = Table.urls;
            if (options.app) {
                $.each(urls, function (idx, value) {
                    urls[idx] = options.app + '/' + idx;
                });
            }
            if (options.urls) {
                urls = $.extend({}, urls, options.urls);
            }
            var $operate = Table.$dom.getDatas('operate');
            if ($operate) {
                urls = $.extend({}, urls, $operate);
            }
            $.each(urls, function (idx, value) {
                if (value) {
                    if (value && value.indexOf("./") == 0) {
                        value = value.replace(/.\//, options.app + '/');
                    }
                    if (value[0] != '/' && !value.isUrl()) {
                        value = '/' + value;
                    }
                }

                urls[idx] = iCMS.url(value, false);
                // urls[idx] = value;
            });

            if ($APP.ACCESS) {//假装限制一下
                $.each(urls, function (idx, value) {
                    if (value && !Table.checkAccess(value)) {
                        urls[idx] = null;
                    }
                });
            }
            Table.urls = urls;
        },

        checkAccess: function (value) {
            if ($APP.ACCESS && value) {//假装限制一下
                let path = value;
                if (value.includes('http')) {
                    let url = value.match(/\/\/[^/]+(\/[^?#]+)/);
                    path = url[1]
                }
                path = path.ltrim($APP.SCRIPT_NAME).toLowerCase();
                path = path.ltrim('/');
                const result = $APP.ACCESS.filter(item => item.toLowerCase().includes(path));
                // console.log({ a:$APP.ACCESS, path, result });
                if (result) {
                    return true;
                }
                return false;
            }
            return true;
        },
        makeColumns: function (options) {
            var columns = options.columns;
            //判断是否有checkbox
            if (columns[0].hasOwnProperty("checkbox")) {
                //有而且为false 
                if (columns[0]['checkbox'] === false) {
                    columns.shift();
                    // delete columns[0]
                }
            } else {//没有checkbox 追加默认
                columns.unshift(Table.columns['checkbox']);
            }

            var defOperate = {};
            var operate_idx = null;
            $.each(columns, function (idx, item) {
                if (item.field == 'operate') {
                    defOperate = item;
                    operate_idx = idx;
                }
            });
            var operate = Table.columns.operate();
            if (!isEmpty(defOperate)) {
                operate = $.extend({}, defOperate, operate);
                columns[operate_idx] = operate;
            } else {
                columns.push(operate);
            }

            // $.each(columns, function (idx, item) {
            //     var defs = Table.columns[item.field] || {};
            //     if (typeof defs == 'function') {
            //         defs = defs();
            //     }
            //     var events = item.events || {}
            //     if (!isEmpty(defs.events)) {
            //         events = $.extend({}, defs.events, events);
            //     }
            //     if (!isEmpty(defs)) {
            //         columns[idx] = $.extend({}, defs, item, { events });
            //     }
            // });

            $.each(columns, function (idx, item) {
                if (typeof item.title == 'string' && item.title.indexOf(':') > 0) {
                    item.title = Lang(item.title);
                }
                if (isUndef(item.title) && item.field) {
                    item.title = Lang(`fields:${item.field}`);
                }

                if (typeof item.formatter == 'function') {
                    if ($.inArray(item.formatter.name, ["switch", "input"])!== -1) {
                        item.clickToSelect = false;
                    }
                    if (item.formatter.name == 'operate') {
                        var events = item.events || {};
                        item.events = $.extend(events, Table.events.operate);
                    }
                    // var mapkey = item.formatter.name + "Map";
                    if (item.setting && typeof (item.setting["map"]) === "string" && item.setting["map"].indexOf("{") < 0) {
                        var url = item.setting["map"];
                        // console.log(url);
                        Table.cache[url] = {};
                        iCMS.api.post(url, {
                            field: item.field,
                            appid: $APP.appId,
                            appname: $APP.name,
                        }, function (json) {
                            Table.cache[url] = json.data
                            item.dataMap = json.data;
                        });
                    }
                } else {
                    item.formatter = Table.format.text;
                }
                if (!isUndef(item.search)) {
                    Table.search.push(item);
                }
                columns[idx] = item;
            });
            // console.log(columns);
            return columns;
        },
        columns: {
            'checkbox': {
                checkbox: true,
                forceHide: false,
            },
            'operate': function () {
                return {
                    field: 'operate',
                    clickToSelect: false,
                    forceHide: false,
                    title: Lang("Operate"),
                    class: 'text-end',
                    events: Table.events.operate,
                    formatter: Table.format.operate
                }
            }
        },
        events: {
            api: {
                update: function (params, url, title) {
                    Layer.confirm(Lang("msg:confirm", title), function (index) {
                        Layer.close(index);
                        iCMS.api.post(url, params, function (ret) {
                            iCMS.notify.success(["msg:success", title]);
                            // Table.method.remove([row.id], 'id');
                            Table.method.refresh();
                        }, function (ret) {
                            iCMS.notify.warning(["msg:fail", title]);
                        });
                    });
                },
                delete: function (ids, title) {
                    Layer.confirm(Lang("msg:confirm", title), function (index) {
                        Layer.close(index);
                        iCMS.api.post(Table.urls.delete, { ids }, function (ret) {
                            iCMS.notify.success(["msg:success", title]);
                            Table.method.remove(ids, 'id');
                        }, function (ret) {
                            iCMS.notify.warning(["msg:fail", title]);
                        });
                    });
                }
            },
            delete: function (e, value, row, index) {
                if (!Table.urls.delete) return;

                Table.events.api.delete([row['id']], Lang("Delete"));
            },

            trash: function (e, value, row, index) {
                if (!Table.urls.trash) return;

                Table.events.api.update({
                    id: row.id,
                    field: 'status',
                    value: '2'
                }, Table.urls.trash, Lang("msg:trash"))
            },
            publish: function (e, value, row, index) {
                if (!Table.urls.publish) return;

                var params = {
                    id: $(e.currentTarget).data('rowId'),
                    field: $(e.currentTarget).data('field'),
                    value: $(e.currentTarget).data('value')
                };
                var title = Lang("msg:updateto", params['field'] + ':' + params['value']);
                Table.events.api.update(params, Table.urls.publish, title);
            },
            edit: function (e, value, row, index, url) {
                if (!Table.urls.edit) return;

                var url = Table.urls.edit + '/id/' + row.id;
                url = Table.utils.display(url, row);
                // console.log(url);
                // url = iCMS.url(url);
                if (Table.options.editModal) {
                    iCMS.ui.modal(e.currentTarget, { url, row, height: "auto" });
                }
            },
            detail: function (e, value, row, index, url) {
                if (!Table.urls.detail) return;

                var url = Table.urls.detail + '/id/' + row.id;
                url = Table.utils.display(url, row);

                if (url.isUrl()) {
                    url = url.replace(/URL:\/\//, '');
                    window.location.href = url;
                } else {
                    // url = iCMS.url(url);
                    iCMS.ui.modal(e.currentTarget, { url, row, height: "auto" });
                }
            },
            operate: {
                'click .table-btn-detail': function (e, value, row, index) {
                    e.preventDefault();
                    Table.events.detail(e, value, row, index)
                },
                'click .table-btn-edit': function (e, value, row, index) {
                    if (Table.options.editModal) {
                        e.preventDefault();
                        Table.events.edit(e, value, row, index)
                    }
                },
                'click .table-btn-trash': function (e, value, row, index) {
                    e.preventDefault();
                    Table.events.trash(e, value, row, index);
                },
                'click .table-btn-delete': function (e, value, row, index) {
                    e.preventDefault();
                    Table.events.delete(e, value, row, index);
                },
                'click .table-btn-publish': function (e, value, row, index) {
                    e.preventDefault();
                    Table.events.publish(e, value, row, index);
                }
            }
        },
        format: {
            _display: function (s, row) {
                $.each(s, function (key, value) {
                    if (key != 'format') {
                        s[key] = Table.utils.display(value, row);
                    }
                });
                if (typeof s['text'] == "string" && s['text'].indexOf(':') > 0) {
                    s['text'] = Lang(s['text']);
                }
                return iCMS.utils.display(s.format, s);
            },
            icon: function (value, row, index) {
                if (value === '') return;
                if (isUndef(value)) value = this.field;

                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { text: value }
                }

                var defaults = {
                    field: this.field,
                    prefix: 'fa',
                    map: {},
                    format: '<i class="{{icon}}"></i>',
                };

                var s = $.extend(defaults, vobj, this.setting);

                Table.utils.getMapper.call(this, s, s.text);
                s.icon = s.prefix + ' ' + s.prefix + '-' + s.text;
                if (this.icon) s.icon = this.icon;

                return Table.format._display(s, row);
            },
            image: function (value, row, index) {
                if (value === '') return;
                if (isUndef(value)) value = this.field;

                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { src: value }
                }
                var defaults = {
                    src: '',
                    class: "",
                    popover: {
                        class: "img-thumbnail img-h50",
                    },
                    target: "_blank",
                    mode: "popover",
                    url: null,
                    title: '',
                    format: '<a i="{{ui}}" class="btn btn-sm btn-alt-secondary {{class}}" href="{{url}}" title="{{title}}" target="{{target}}" {{attr}}>' +
                        // '<img class="%s" src="%s">' +
                        '<i class="fa fa-fw fa-image" aria-hidden="true"></i>' +
                        '</a>',
                }


                var s = $.extend(defaults, vobj, this.setting);

                var src = s.src;
                if (typeof (src) == "function") {
                    var src = s.src.call(this, s, row, index);
                } else {
                    if (src) s.src = iCMS.utils.resUrl(src);
                }

                if (!s.url) s.url = src;

                if (s.mode == "dialog") s.ui = "cms:ui:dialog"

                s.attr = s.mode == "popover" ? [
                    'i="cms:ui:popover"',
                    'data-html="true"',
                    'data-placement="top"',
                    'data-content=\'<img class="' + s.popover.class + '" src="' + src + '">\''
                ].join(" ") : '';
                return Table.format._display(s, row);
            },
            images: function (value, row, index) {
                if (value === '') return;
                if (isUndef(value)) value = this.field;

                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { values: value.split(",") }
                }

                var s = $.extend({
                    values: [],
                    title: '',
                    text: '',
                    items: '',
                    format: '<button i="cms:ui:gallery" class="btn btn-sm btn-alt-secondary" data-items=\'{{items}}\' title="{{title}}">' +
                        '<i class="fa fa-fw fa-images" aria-hidden="true"></i> {{text}}' +
                        '</button>',
                }, vobj, this.setting, { text: this.text });

                if (s.values.length > 0) {
                    s.title = s.values.length + 'P';
                    var items = [];
                    $.each(s.values, function (idx, src) {
                        var url = iCMS.utils.resUrl(src);
                        items.push({ src: url });
                    });
                    if (items.length > 0) {
                        s.items = JSON.stringify(items);
                    }
                }
                if (s.items) {
                    return Table.format._display(s, row);
                }
            },
            status: function (value, row, index) {
                if (isUndef(value)) return;

                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { value }
                }

                var defaults = {
                    field: this.field,
                    text: '',
                    value: '',
                    classMap: {
                        0: 'link-primary',
                        1: 'link-success',
                        2: 'link-secondary',
                        3: 'link-danger',
                        4: 'link-warning',
                        5: 'link-info',
                        6: 'link-dark',
                        7: 'link-light bg-dark',
                    },
                    map: {
                        0: Lang("status:inbox"),
                        1: Lang("status:normal"),
                        2: Lang("status:trash"),
                        3: Lang("status:examine"),
                        4: Lang("status:off"),
                        5: Lang("status:deleted"),
                        6: Lang("status:locked"),
                        7: Lang("status:hidden"),
                    },
                    format: '<a href="#" class="table-btn-refresh {{class}}" data-params-{{field}}="{{value}}">' +
                        '<i class="fa fa-fw fa-circle fa-xs"></i> {{text}}' +
                        '</a>',
                }
                var s = $.extend(defaults, vobj, this.setting, { text: this.text });

                Table.utils.getMapper.call(this, s, s.value);

                s.class = s.classMap[s.value];
                if (isUndef(s.class)) s.class = 'link-info';

                return Table.format._display(s, row)
            },

            flag: function (value, row, index) {
                if (isUndef(value)) return;
                if (value === '') return;

                var field = this.field;

                var vobj = {};

                if (!isArray(value)) {
                    if (typeof value !== "string") {
                        value = value.toString();
                    }
                    vobj = { values: value.split(",") }
                }

                var defaults = {
                    values: [],
                    classMap: {
                        0: 'bg-primary',
                        1: 'bg-success',
                        2: 'bg-secondary',
                        3: 'bg-warning',
                        4: 'bg-info',
                        5: 'bg-light',
                        6: 'bg-dark',
                    },
                    classname: '',
                    map: {},
                    format: '<span role="button" class="badge {{classname}} table-btn-refresh" data-params-{{field}}="{{value}}">{{text}}</span> ',
                };

                var s = $.extend(defaults, vobj, this.setting);

                Table.utils.getMapper.call(this, s);

                var html = '';
                $.each(s.values, function (idx, v) {
                    var ss = $.extend({}, s);
                    var text = s.map[v];
                    var classname = s.classMap[v];
                    if (isUndef(text)) text = v;
                    if (isUndef(classname)) classname = 'bg-info';
                    var dd = { classname, field, value: v, text };
                    var ss = $.extend({}, ss, dd);
                    html += Table.format._display(ss, row);
                });

                return html;
            },
            switch: function (value, row, index) {
                if (isUndef(value)) value = this.field;
                var field = this.field;
                row["field"] = field;

                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { value, checked: (value ? true : false) }
                }
                var defaults = {
                    params: {},
                    value: '',
                    checked: false,
                    class: 'form-check-input',
                    label: '',
                    labelclass: 'form-check-label',
                    url: Table.urls.update,
                    name: 'TableSwitch[' + row.id + '][' + field + ']',
                    id: 'TableSwitch_' + field + '_' + row.id,
                    checked: isUndef(this.checked) ? vobj.checked : this.checked,
                    format: '<div class="form-check form-switch" i="cms:ui:form-switch">' +
                        '<input class="{{class}}" type="checkbox"' +
                        ' data-params=\'{{params}}\'' +
                        ' data-url="{{url}}"' +
                        ' value="{{value}}"' +
                        ' {{checked}}' +
                        ' id="{{id}}' +
                        ' name="{{name}}">' +
                        '<label class="{{labelclass}}" for="{{id}}">{{label}}</label>' +
                        '</div>'
                };
                var s = $.extend(defaults, vobj, this.setting, { label: this.label });

                s.params = $.extend({ id: row.id, field }, s.params);
                s.params = JSON.stringify(s.params);
                s.checked = s.checked ? ' checked' : '';
                return Table.format._display(s, row);
            },

            mapper: function (value, row, index) {
                // console.log(this);
                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { value }
                }

                var s = $.extend({
                    value: '',
                    text: '',
                    map: {},
                    format: '{{text}}',
                }, vobj, this.setting, { text: this.text });
                Table.utils.getMapper.call(this, s, s.value);
                return Table.format._display(s, row)
            },
            text: function (value, row, index) {
                var field = this.field;
                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { text: value }
                }

                var s = $.extend({
                    text: '',
                    key: field + '_text',
                    width: this.width || 240,
                    height: this.height || 20,
                    style: '',
                    // format: '{{text}}'
                    format: '<div class="{{class}}" style="{{style}}" title="{{text}}">{{text}}</div>'
                }, vobj, this.setting, { text: this.text });

                if (!isUndef(row[s.key])) {
                    s.text = row[s.key];
                }
                return Table.format._display(s, row);
            },
            content: function (value, row, index) {
                if (value == "") {
                    return;
                }
                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { text: value }
                }
                var s = $.extend({
                    field: this.field,
                    text: '',
                    width: this.width || 240,
                    height: this.height || 150,
                    unit: this.widthUnit,
                    class: "overflow-auto",
                    format: '<div class="text-wrap text-break {{class}}" style="width: {{width}};max-height: {{height}}">{{text}}</div>',
                }, vobj, this.setting, { text: this.text });
                if (!isNaN(s.width)) {
                    s.width += s.unit;
                }
                if (!isNaN(s.height)) {
                    s.height += s.unit;
                }
                return Table.format._display(s, row);
            },

            search: function (value, row, index, field) {
                if (isUndef(value)) value = this.field;

                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { text: value, value }
                }
                // var text = row[this.field + '_text']
                // if (!isUndef(text)) {
                //     vobj.text = text;
                // }
                var s = $.extend({
                    field: field || this.field,
                    value: '',
                    text: '',
                    class: "link-primary",
                    format: '<a href="javascript:;" class="{{class}} text-decoration-underline table-btn-refresh" data-params-{{field}}="{{value}}">{{text}}</a>'
                }, vobj, this.setting, { text: this.text });

                Table.utils.getMapper.call(this, s, s.value);

                return Table.format._display(s, row);
            },
            dialog: function (value, row, index) {
                if (isUndef(value)) value = this.field;

                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { text: value, value }
                }

                var s = $.extend({
                    field: this.field,
                    value: '',
                    text: '',
                    url: row['url'],
                    title: '',
                    class: "btn btn-secondary",
                    format: '<a i="cms:ui:dialog" href="{{url}}" class="{{class}}" title="{{title}}">{{text}}</a>'
                }, vobj, this.setting, { text: this.text });
                s.url = iCMS.url(s.url);
                return Table.format._display(s, row);
            },
            input: function (value, row, index) {
                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { value }
                }
                var s = $.extend({
                    field: this.field,
                    name: this.field,
                    id: row.id,
                    key: row.id,
                    value: '',
                    type: 'text',
                    update_class: this.input_update ? 'table-input-update' : '',
                    class: "form-control form-control-sm",
                    format: '<input type="{{type}}" data-id="{{id}}" data-field="{{field}}" name="{{name}}[{{key}}]" class="{{class}} {{update_class}}" value="{{value}}">'
                }, vobj, this.setting, { value: this.value });
                return Table.format._display(s, row);
            },
            badge: function (value, row, index) {
                if (value == "") return;
                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { text: value, value }
                }
                var s = $.extend({
                    field: this.field,
                    value: '',
                    text: '',
                    class: "bg-primary",
                    format: '<span class="badge rounded-pill {{class}}">{{text}}</span>'
                }, vobj, this.setting, { text: this.text });
                return Table.format._display(s, row);
            },
            datetime: function (value, row, index) {
                if (value == "") return;
                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { value }
                }
                var s = $.extend({
                    value: '',
                    format: "YYYY-MM-DD HH:mm",
                }, vobj, this.setting);

                if (s.value) {
                    if (s.value.length > 10) {
                        return iCMS.utils.moment(s.value).format(s.format);
                    } else {
                        return iCMS.utils.moment.unix(s.value).format(s.format);
                    }
                }
            },

            buttons: function (value, row, index) {
                var html = '<div class="btn-group btn-group-sm">';
                if (this.buttons) {
                    html += Table.utils.makeButton.call(this, this.buttons, row, index);
                }
                html += '</div>';
                return html;
            },
            operate: function (value, row, index) {
                if (isUndef(value)) value = this.field;

                var qs = '/id/' + row.id
                var defaults = [];
                Table.urls.detail && defaults.push({
                    // url: iCMS.url(Table.urls.detail + qs, false),
                    url: Table.urls.detail + qs,
                    class: 'btn btn-alt-primary table-btn-detail',
                    name: "detail",
                    title: Lang("View"),
                    operate: 'detail',
                    text: '',
                    icon: "fa fa-fw fa-circle-info",
                    visible: Table.urls.detail ? true : false
                });
                Table.urls.edit && defaults.push({
                    // url: iCMS.url(Table.urls.edit + qs, false),
                    url: Table.urls.edit + qs,
                    class: 'btn btn-alt-primary table-btn-edit',
                    name: "edit",
                    title: Lang("Edit"),
                    operate: 'edit',
                    text: '',
                    icon: "fa fa-fw fa-edit",
                    visible: Table.urls.edit ? true : false
                });
                if (row['status'] != '2') {
                    Table.urls.trash && defaults.push({
                        url: Table.urls.trash,
                        class: 'btn btn-alt-warning table-btn-trash',
                        name: "trash",
                        title: Lang("Trash"),
                        operate: 'trash',
                        text: '',
                        icon: "fa fa-fw fa-trash",
                        visible: Table.urls.trash ? true : false
                    });
                }
                if (row['status'] != '1') {
                    Table.urls.publish && defaults.push({
                        url: Table.urls.publish,
                        class: 'btn btn-alt-success table-btn-publish',
                        name: "publish",
                        title: Lang('status:normal'),
                        operate: 'publish', //用于判断 每行数据里是否有operate字段
                        text: '',
                        icon: "fa fa-fw fa-check",
                        attr: {
                            'data-field': 'status',
                            'data-value': '1'
                        },
                        visible: Table.urls.publish ? true : false
                    });
                }
                Table.urls.delete && defaults.push({
                    url: Table.urls.delete,
                    class: 'btn btn-alt-danger table-btn-delete',
                    name: "delete",
                    title: Lang("Delete"),
                    operate: 'delete', //用于判断 每行数据里是否有operate字段
                    text: '',
                    icon: "fa fa-fw fa-times",
                    visible: Table.urls.delete ? true : false
                });
                var html = '<div class="btn-group btn-group-sm">';
                if (this.buttons) {
                    html += Table.utils.makeButton.call(this, this.buttons, row, index);
                }
                if (this.operate === false) {
                    defaults = [];
                }

                $.each(defaults, function (idx, item) {
                    var key = item['operate'];
                    var name = item['name'];
                    var ops = row['operate'] || {};
                    if (isDef(ops[key])) {
                        item['visible'] = ops[key];
                    } else if (isDef(ops[name])) {
                        item['visible'] = ops[name];
                    }
                });
                html += Table.utils.makeButton.call(this, defaults, row, index);
                // console.log(html);
                html += '</div>';
                return html
            },
            link: function (value, row, index) {
                if (isUndef(value)) value = this.field;
                var vobj = {};
                if (typeof value !== "object") {
                    vobj = { text: value, value }
                }
                var s = $.extend({
                    field: this.field,
                    value: '',
                    text: '',
                    url: row['url'],
                    title: row['title'],
                    class: "btn-link",
                    target: "_blank",
                    format: '<a class="{{class}}" href="{{url}}" title="{{title}}" target="{{target}}">{{text}}</a>'
                }, vobj, this.setting, { text: this.text, url: this.url });
                return Table.format._display(s, row);

            }
        },
        utils: {
            getMapper: function (s, value) {
                var mapper = {};
                var field = this.field;
                var mapkey = this.formatter.name + "Map";
                if (this.setting && typeof (this.setting["map"]) === "string" && this.setting["map"].indexOf("{") < 0) {
                    var mapkey = this.setting["map"];
                }

                if (typeof (s.map) === "string" && s.map.indexOf("{") < 0 && typeof (Table.cache[mapkey]) === "object") {
                    mapper = Table.cache[mapkey];
                    // console.log(Table.cache[mapkey]);
                }
                if (isEmpty(mapper)) {
                    var fmkey = field + 'Map';
                    if ($APP.context && typeof ($APP.context[fmkey]) === "object") {
                        mapper = $APP.context[fmkey]
                    }
                }
                // s.mapper = {};
                if (!isEmpty(mapper)) {
                    s.map = mapper;
                }

                if (!isUndef(value) && s.map) {
                    s.text = s.map[value];
                    if (isUndef(s.text)) {
                        s.text = value;
                    }
                }
                // console.log(s);

            },
            display: function (str, row) {
                if (typeof (str) == 'string' && (str.indexOf("{{") > -1 || str.indexOf("<%") > -1)) {
                    str = iCMS.utils.display(str, row);
                }
                return str
            },
            makeButton: function (buttons, row, index) {
                var column = this;
                var html = '';
                $.each(buttons, function (idx, _btn) {
                    var btn = $.extend({
                        class: "btn btn-alt-primary btn-sm",
                        name: '',
                        title: '',
                        text: _btn.name ? Lang(_btn.name) : '',
                        visible: true,
                        attr: {},
                        target: '_self',
                        url: '',
                        ui: '',
                        icon: '',
                        callback: null, //btn-dialog
                        success: null, //btn-ajax
                        error: null, //btn-ajax
                    }, _btn);

                    var visible = btn.visible;
                    if (typeof btn.visible === 'function') {
                        visible = btn.visible.call(Table, row);
                    }
                    if (!visible) {
                        return html;
                    }
                    var href = '';
                    if (btn.url) {
                        href = iCMS.url(Table.utils.display(btn.url, row), false);
                        if (!Table.checkAccess(href)) {
                            href = 'javascript:;'
                        }
                    } else {
                        href = 'javascript:;'
                    }
                    var attr = '';
                    var attrs = $.extend({
                        'data-table-id': Table.$dom.attr("id"),
                        'data-field-index': column.fieldIndex,
                        'data-row-index': index,
                        'data-row-id': row.id,
                        'data-button-index': idx,
                        'name': btn.name,
                        'class': btn.class,
                        'i': btn.ui,
                        'title': btn.title,
                        'target': btn.target,
                        'href': href,
                    }, btn.attr)

                    if (!isEmpty(attrs)) {
                        $.each(attrs, function (key, value) {
                            if (isFunction(value)) {
                                value = value(row);
                            }
                            value = Table.utils.display(value, row);
                            attr += iCMS.utils.sprintf(' %s="%s"', key, value);
                        });
                    }

                    var icon = btn.icon ? '<i class="' + btn.icon + '"></i> ' : '';
                    html += iCMS.utils.display(
                        '<a {{attr}}>{{icon}}{{text}}</a>', { attr, icon, text: btn.text }
                    );

                    if (btn.dropdown) {
                        html += '<div class="dropdown-menu fs-sm" aria-labelledby="' + btn.id + '">'
                        $.each(btn.dropdown, function (key, ddv) {
                            if (ddv == 'divider') {
                                html += '<div role="separator" class="dropdown-divider"></div>'
                            } else {
                                if (ddv.href) {
                                    ddv.href = iCMS.url(ddv.href, false);
                                    // ddv.href = ddv.href;
                                    ddv.href = Table.utils.display(ddv.href, row);
                                } else {
                                    ddv.href = 'javascript:;';
                                }
                                html += iCMS.utils.display(
                                    '<a class="dropdown-item {{class}}" href="{{href}}">{{text}}</a>',
                                    ddv
                                );
                            }
                        });
                        html += '</div>';
                    }
                });
                return html;
            },
        },
        ui: {
            toolbar: function ($toolbar) {
                var $toolbar = $(Table.options.toolbar);

                var editurl = $('.table-btn-edit', $toolbar).attr("href");
                if (editurl) { //使用工具栏和操作栏 链接一致
                    // Table.urls.edit = iCMS.url(editurl, false);
                    Table.urls.edit = editurl;
                }
                var deleteurl = $('.table-btn-delete', $toolbar).attr("href");
                if (deleteurl) { //使用工具栏和操作栏 链接一致
                    // Table.urls.delete = iCMS.url(deleteurl, false);
                    Table.urls.delete = deleteurl;
                }

                $toolbar.show();
                $toolbar.on('click', '.table-btn-edit', function (e) {
                    e.preventDefault();
                    var url = $(this).attr("href");

                    var edit = function (rows) {
                        $.each(rows, function (index, row) {
                            var value = row['id'];
                            Table.events.edit(e, value, row, index)
                        });
                    }
                    var rows = Table.method.getSelections();
                    edit(rows);

                    // if(rows.length>3){
                    //     Layer.confirm(Lang("Are you sure you want to open multiple edit boxes?")+rows.length, function (index) {
                    //         edit(rows);
                    //     });
                    // }else{
                    // }

                }).on('click', '.table-btn-delete', function (e) {
                    e.preventDefault();

                    var ids = Table.method.getIds();
                    var msg = Lang("Delete") + 'ID:' + ids.join(',')
                    Table.events.api.delete(ids, msg);
                });
            },
            search: function () {
                var html = '';
                // console.log(Table.search);
                // Table.$dom.before(html);
            },
            tablist: function () {
                var html = '';

                function li_refresh(data) {
                    var format = '<li class="nav-item">' +
                        '<a class="nav-link {{active}} table-btn-refresh" href="javascript:;" data-params-{{field}}="{{value}}">{{text}}</a>' +
                        '</li>';
                    return iCMS.utils.display(format, data);
                }

                function li_link(url, title) {
                    var format = '<li class="nav-item">' +
                        '<a class="nav-link js-tabs-link" href="{{url}}">{{text}}</a>' +
                        '</li>';
                    // var url = iCMS.url(url, false);
                    return iCMS.utils.display(format, { url, text: Lang(title) });
                }

                function map_li(field, active) {
                    var html = '';
                    var collection = $APP.context[field + 'Map'];

                    if (isUndef(collection)) return '';

                    var active = Table.params[field] || active;
                    html += '<ul i="cms:ui:tabs" class="nav nav-tabs nav-tabs-block align-items-center without-auto-active" data-toggle="tabs" role="tablist">';
                    html += li_refresh({ active: (active == null ? 'active' : ''), field, value: 'null', text: Lang('All') });
                    $.each(collection, function (value, text) {
                        html += li_refresh({
                            active: active == value ? 'active' : '',
                            field,
                            value,
                            text: Lang(text)
                        });
                    });
                    html += '</ul>';
                    return html;
                }
                if (!isUndef($APP.context.tablist)) {
                    var tablist = $APP.context.tablist;
                    $.each(tablist, function (key, tvals) {
                        if (key == 'params') {
                            $.each(tvals, function (field, active) {
                                html += map_li(field, active)
                            });
                        } else {
                            html += '<ul i="cms:ui:tabs" class="nav nav-tabs nav-tabs-alt nav-tabs-block align-items-center without-auto-active" data-toggle="tabs" role="tablist">';
                            if (isArray(tvals) || isObject(tvals)) {
                                $.each(tvals, function (title, url) {
                                    html += li_link(url, title);
                                });
                            } else {
                                html += li_refresh({ active: '', field: key, value: tvals, text: key });
                            }

                            html += '</ul>';
                        }
                    });
                    Table.$dom.closest(".block").before(html);
                    // Table.$dom.before(html);

                }
            },
        }

    }
    Table.$dom.on('refresh', function (event) {
        Table.method.refresh();
        // console.log(event);
        // return 'sdfsdf';
    });

    return Table;
});