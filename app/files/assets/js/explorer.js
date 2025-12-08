define(["iCMS", "admincp"], function (iCMS, admincp) {
    return function () {

        window.multi_callback = function (file, json, uploader) {
            // console.log(file.name);
            var stats = uploader.getStats();
            // var num = stats.successNum+stats.uploadFailNum
            if (json.code) {
                top.iCMS.notify.success(file.name + " >> " + json.data.path + Lang("Uploaded"));
            }
            if (stats.successNum == uploader.$fileCount) {
                window.location.reload();
                Layer.close(window.ui_modal_idx);
            }
        }
        $(document).on('click', '.btn-refresh', function (e) {
            e.preventDefault();
            window.location.reload();
        }).on('click', '.btn-mkdir', function (e) {
            e.preventDefault();
            var dir = $(this).data('sdir');
            Layer.prompt({
                title: '创建新目录',
                placeholder: '请输入目录名称',
                inputClass: 'form-control'
            }, function (value, index, elem) {
                if (value == "") {
                    top.iCMS.notify.error("请输入目录名称!");
                    return false;
                }
                var $url = "filesExplorer/mkdir";
                if (dir == 'template') {
                    $url = "filesExplorer/template_mkdir";
                }

                var pwd = $('#pwd').val();
                iCMS.api.post(
                    $url,
                    { name: value, pwd },
                    function (json) {
                        top.iCMS.notify.success(value + " 创建成功！");
                        iCMS.ui.timeout(function () {
                            Layer.close(index);
                            window.location.reload();
                        }, 1000);
                    }
                );
            });
        });
    }
});
