define(function () {
    return function ({ $el, el }) {
        iCMS.api.spost("admincp/hooks", { name: $APP.name, do: $APP.do }, function (json) {
            $.each(json.data, function (idx, paths) {
                console.log(paths);
                iCMS.start(paths);
            });
        });
    };
});