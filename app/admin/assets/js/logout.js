define(function () {
    function clearAdminData() {
        localStorage.removeItem('admin_token')
        localStorage.removeItem('admin_expire')
        localStorage.removeItem('admin_info')
    }

    return function ({ $el, el }) {
        $el.click(function (e) {
            e.preventDefault();
            var url = $el.attr("href");
            iCMS.api.post("admin/logout", function (json) {
                iCMS.notify.success("auth:logout");
                clearAdminData();
                setTimeout(function () {
                    window.location.href = json.url;
                }, 1000);
            });
        });
    };
});