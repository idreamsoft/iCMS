define(["admincp"], function (admincp) {
    return function () {

        $("#rs-field").data("params", function (obj) {
            var appid = $("#rs-app").data('value');
            if(!appid){
                iCMS.notify.warning('请先选择所属应用');
                return;
            }
            return { appid };
        });
    };
});