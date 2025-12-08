define(["vue"], function (Vue) {
    const factory = {
        load: function (params, call) {
            iCMS.api.spost('FilesExplorer/browse', params, call);
        },
        browse: function ({ $el, el }) {
            var page = 1; // 当前页数
            var pageTotal = 1; // 总数
            factory.load({ page }, function (json) {
                page = json.data.pageData.current
                pageTotal = json.data.pageData.pageTotal; // 总数
                factory.vm = new Vue({
                    el,
                    data: {
                        items: json.data.rows
                    }
                })
            });
            // 监听滚动事件
            $(window).scroll(function () {
                console.log($(window).scrollTop(), $(window).height());
                console.log($(document).height());
                // 当滚动到底部时加载更多数据
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 1) {
                    page++; // 更新页数
                    console.log({ page });
                    if (page > pageTotal) {
                        page = pageTotal;
                        return;
                    }
                    factory.load({ page }, function (json) {
                        // page = json.data.pageData.current
                        pageTotal = json.data.pageData.pageTotal; // 总数        
                        factory.vm.items = factory.vm.items.concat(json.data.rows);
                    });
                }
            });
        }
    }
    return factory;
});