define(["cms"], function (cms,USER) {
    return {
        preview: function ({ $el, el }) {
            var wrap = $('<iframe src="/assets/libs/editor/vditor/preview.html" name="markdown-preview" frameborder="0" style="width: 100%; height: 100%;overflow: hidden;"></iframe>');
            $el.after(wrap);

            window.addEventListener('message', function (event) {
                if (event.data && event.data.height) {
                    wrap.height(event.data.height);
                }
            });
        }
    }
});