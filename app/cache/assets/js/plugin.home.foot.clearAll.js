/**
 * 后台首页插件 清除所有缓存
 */
define(["iCMS"], function (iCMS) {
    return function () {
        iCMS.api.spost("cache/clearAll",function (json) {});
    };
});
