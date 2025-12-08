/**
 * 后台首页插件 检测版本
 */
define(function () {
    function patch_check_release(flag) {
        iCMS.api.spost("patch/check_release", function (json) {
            if (json.code == "3") {
                if (flag) iCMS.ui.success(json.message);
                return;
            }
            iCMS.ui.success(json.message, function () {
                window.location.href = json.url;
            }, { btn: ['马上升级', '以后在说'], height: '240px' })
        });
    }
    return function () {
        $("[i=iCMS_RELEASE]").on("click", function (e) {
            e.preventDefault();
            patch_check_release(1);
        });
        iCMS.ui.timeout(function () {
            patch_check_release();
        }, 1000);
    };
});


