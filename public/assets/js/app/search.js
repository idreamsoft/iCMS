define(["cms"], function (cms) {
    return {
        form: function ({ $el, el }) {
            $el.submit(function (e) {
                // e.preventDefault();
                var $kwEl = $('[name="keyword"]', $el);
                var placeholder = $kwEl.attr("placeholder");
                if (!$kwEl.val()) {
                    var msg = placeholder || "请输入关键词"
                    iCMS.ui.tips($kwEl,msg,{
                        tips: [1, '#4c78dd']
                    });
                    $kwEl.focus();
                    return false;
                }else{
                    // var query = $el.serializeJSON();
                    // var url = iCMS.url('search', false);
                    // var data = {
                    //     action: "sign",
                    //     query: query,
                    //     CSRF_TOKEN: $APP.CSRF_TOKEN
                    // }
                    // iCMS.request.post(url, data);
                }
            });
        },
    }
});