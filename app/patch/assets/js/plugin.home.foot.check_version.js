/**
 * 后台首页插件 检测版本
 */
define(function () {
    return function () {
        iCMS.api.spost("patch/check_version", function (json) {
            $('#iCMS_RELEASE').text(json.data.release);
            $('#iCMS_RELEASE_TIME').text("REL:" + json.data.release_time);
            $('#iCMS_GIT').text(json.data.git);
            $('#iCMS_GIT_COUNT').removeClass("hide").text(json.data.git_count);
        });
    };
});