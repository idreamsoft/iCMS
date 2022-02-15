function event_user_avatar_upload() {
    iCMS.$i("user:avatar:upfile").click();
}
function uploadAvatar(){
    var $el = iCMS.$i("event:user:avatar:upload");
    var $src = $el.attr("src");
    $el.attr("src", $src + "?t=" + Math.random());
}
jQuery(() => {
    (function ($) {
        iCMS.$i("user:avatar:upfile").change(function (event) {
            event.preventDefault();
            var $form = iCMS.$i("user:avatar:upload").submit();
            // var $form = iCMS.$i("user:avatar:upload");
            // $.ajax({
            //     url: $form.attr("action"),
            //     type: "POST",
            //     cache: false,
            //     data: new FormData($form[0]),
            //     processData: false,
            //     contentType: false,
            //     success: function (json) {
            //         console.log(json);
            //     },
            // });
        });
        var doc = $(document);
        doc.on("click", "[data-preview]", function (e) {
            var field = $(this).data("preview"),
                pic = $("#" + field).val();
            if (pic) {
                var $el = $("#modal-iframe")
                var src = $el.attr("src");
                $el.attr("src", src + "&pic=" + pic);
                $el.css('width',"100%");
            } else {
                var check = $(this).data("check"),
                    title = $(this).attr("title");
                if (check) {
                    window.iCMS_MODAL.destroy();
                    iCMS.ui.alert("暂无图片,您现在不能" + title);
                }
            }
        });
    })(jQuery);
});
