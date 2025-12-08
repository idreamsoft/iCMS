define(["jquery", "iCMS", "admincp"], function ($, iCMS, admincp) {
    return function () {
        // var typeMap = {
        //     'file': '文件',
        //     'dir': '目录'
        // };

        // //选择文件或者目录回调
        // function callback(a) {
        //     window.parent.modal_callback({
        //         "code": "1",
        //         "state": "SUCCESS",
        //         "data": {
        //             "value": a.val(),
        //             "url": a.attr("url")
        //         }
        //     });
        // }
        // var doc = $(document);
        // var clickMode = $("#clickMode").val();
        // if (clickMode) {
        //     doc.on("click", ".btn-choice", function (event) {
        //         $(".form-check-input").prop("checked", false);
        //         var el = $(this).closest("tr").find("[data-type]");
        //         el.prop("checked",true);
        //         checked(el)
        //     });
        //     doc.on("change", "[data-type]", function (event) {
        //         event.preventDefault();
        //         checked($(this))
        //     });
        //     function checked($this) {
        //         var type = $this.data('type');
        //         if ($this.prop("checked")) {
        //             if (type == clickMode) {
        //                 callback($this);
        //             } else {
        //                 $this.prop("checked", false);
        //                 if (type == "dir") {
        //                     var tr = $this.closest('tr');
        //                     var href = $(".dirname", tr).attr('href');
        //                     console.log(href);
        //                     window.location.href = href;
        //                 }
        //                 iCMS.notify.info("当前只能选择" + typeMap[clickMode]);
        //             }
        //         }
        //     }
        // }
        // if (clickMode == 'file') {
        //     $('[data-toggle="checkAll"]').on("click",function () {
        //         var target = $(this).data('target');
        //         $('[data-type="file"]:checkbox', $(target)).each(function () {
        //             if (this.checked) {
        //                 callback(this);
        //             }
        //         });
        //     });
        // }
        $(".btn-refresh").on("click", function (e) {
            e.preventDefault();
            window.location.reload();
        });
        $('.btn-mkdir').on("click", function (e) {
            e.preventDefault();
            var dir = $(this).data('sdir');
            Layer.prompt({
                title: '创建新目录',
                placeholder: '请输入目录名称',
                inputClass: 'form-control'
            }, function (value, index, elem) {
                if (value == "") {
                    iCMS.notify.error("请输入目录名称!");
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
                        iCMS.notify.success(value + " 创建成功！");
                        iCMS.ui.timeout(function () {
                            Layer.close(index);
                            window.location.reload();
                        }, 1000);

                    }
                );
            });
        });
    };
});

