define(["iCMS", "uploader"], function (iCMS, uploader) {
    return uploader({
        pick: {
            id: '#filePicker',
            label: '点击选择图片'
        },
        fileVal: 'upfile',
        formData: {},
        dnd: '#dndArea',
        paste: document.body,
        swf: '/assets/libs/webuploader/Uploader.swf',
        chunked: false,
        chunkSize: 512 * 1024,
        server: iCMS.url('files/upload'),
        callback: {
            "uploadSuccess": function ($file, json, $uploader) {
                if (json.state == 'SUCCESS') {
                    var callback = iCMS.utils.getQuery('callback');
                    if (callback) {
                        window.parent['multi_callback']($file, json, $uploader);
                    }
                } else {
                    top.iCMS.notify.error(json.state);
                }

            },
            "startUpload": function (uploader) {
                var formData = uploader.option('formData');
                var $form = $("#multiForm").serializeJSON();
                var udir = iCMS.utils.getQuery('udir');

                formData = $.extend(formData, $form);
                if (udir) formData['udir'] = udir;
                console.log(formData);
                uploader.option('formData', formData);
                return false;
            }
        },
        // runtimeOrder: 'flash',

        // accept: {
        //     title: 'Images',
        //     extensions: 'gif,jpg,jpeg,bmp,png',
        //     mimeTypes: 'image/*'
        // },

        // 禁掉全局的拖拽功能。这样不会出现图片拖进页面的时候，把图片打开。
        disableGlobalDnd: true,
        fileNumLimit: 300,
        fileSizeLimit: 200 * 1024 * 1024, // 200 M
        fileSingleSizeLimit: 50 * 1024 * 1024 // 50 M
    });
});