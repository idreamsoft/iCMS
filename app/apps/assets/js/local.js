define(function () {
    return function () {
        $(".install_app").on("click", function () {
            var $btn = $(this);
            var zipfile = $btn.data("zipfile");
            if (!zipfile) {
                iCMS.notify.alert("应用包文件名为空");
                return;
            }
            $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> 安装中...');
            iCMS.api.post("/apps/install", { zipfile: zipfile }, function (res) {
                $btn.prop("disabled", false).html('<i class="fa fa-fw fa-download"></i> 安装');
                if (res.code === 1) {
                    iCMS.notify.success("安装成功");
                    $btn.parents("tr").remove();
                } else {
                    iCMS.notify.alert(res.msg || "安装失败");
                }
            });
        });
    }
})