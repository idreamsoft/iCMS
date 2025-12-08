/**
 * 后台首页插件 检测版本
 */
define(function () {
    return function () {
        iCMS.api.spost("patch/check_upgrade", function (json) {
            if (json.code=="2") {
                Layer.confirm(json.message, {
                    type: 1,
                    area: ['360px', '240px'],
                    time: 10000,
                    shadeClose: true,
                    btn: ['马上升级', '以后在说'] //按钮
                }, function () {
                    window.location.href = json.data.upgrade_url;
                }, function () {
                    return true;
                });
            }
        });
    };
});
