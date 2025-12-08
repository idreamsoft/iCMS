define(function () {
    return function () {
        iCMS.api.spost("admincp/plugin", function (json) {
            $.each(json.data, function (idx, paths) {
                iCMS.start(paths);
            });
        });
    };
});