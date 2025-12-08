define(["iCMS", "vue"], function (iCMS, Vue) {
    // 添加一个虚假的历史记录项
    window.history.pushState(null, null, window.location.href);

    // 监听浏览器的返回按钮事件
    window.addEventListener('popstate', function (event) {
        if (confirm('您确定要离开此页面吗？')) {
            // 如果用户确认，则允许跳转
            // 如果需要真正禁用，这里不执行任何操作即可
        } else {
            // 用户取消，重新推入当前状态
            window.history.pushState(null, null, window.location.href);
        }
    });
    // window.addEventListener("beforeunload", function (e) {
    //     const confirmationMessage = "您确定要离开此页面吗？";
    //     e.returnValue = confirmationMessage; // 兼容旧浏览器
    //     return confirmationMessage;          // 兼容新浏览器
    // });
    const factory = {
        add_success: function (json) {
            console.log(json);
        },
        edit: function () {

        },
        add: function () {
            $(".cms-editor").on('change', function () {
                console.log('内容改变了');
            });

        },
        storage: function () {

        },

    };

    return factory;
});