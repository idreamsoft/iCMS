define(function () {
    function clearAdmincpData() {
        localStorage.removeItem('admincp_token')
        localStorage.removeItem('admincp_expire')
        localStorage.removeItem('admincp_info')
    }

    return function ({ $el, el }) {
        $el.click(function (e) {
            e.preventDefault();
            var url = $el.attr("href");
            iCMS.api.post("admin/logout", function (json) {
                iCMS.notify.success("auth:logout");
                clearAdmincpData();
                setTimeout(function () {
                    window.location.href = json.url;
                }, 1000);
            });
        });
    };
});