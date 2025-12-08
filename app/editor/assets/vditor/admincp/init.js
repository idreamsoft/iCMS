define(["jquery", "iCMS"], function ($, iCMS) {
    const baseUrl = '//' + window.location.host + '/assets/libs/editor/vditor';
    window.VditorEditor = {};
    window.VditorConfig = {
        // _lutePath: `http://192.168.0.107:9090/lute.min.js?${new Date().getTime()}`,
        _lutePath: baseUrl + '/dist/js/lute/lute.min.js',
        cdn: baseUrl,
        toolbar: [
            "emoji", "headings", "bold", "italic", "strike", "link", "|",
            "list", "ordered-list", "check", "outdent", "indent", "|",
            "quote", "line", "code", "inline-code", "insert-before", "insert-after", "|",
            "upload", "record", "table", "|",
            "undo", "redo", "|",
            "fullscreen", "preview",
        ],
        lang: 'zh_CN',
        // mode:'wysiwyg',
        mode:'sv',
        //mode: "ir",
        cache: { enable: false },
        minHeight: 450,
        value: "",
        height: window.innerHeight + 100,
        outline: {
            enable: true,
            position: 'right',
        },
        debugger: false,
        typewriterMode: true,
        // placeholder: 'Hello, Vditor!',
        preview: {
            markdown: {
                toc: true,
                mark: true,
                footnotes: true,
                autoSpace: true,
            },
            math: {
                engine: 'KaTeX',
            },
        },
        toolbarConfig: {
            pin: true,
        },
        counter: {
            enable: true,
            type: 'text',
        },
        tab: '\t',
        upload: {
            // accept: 'image/*,.mp3, .wav, .rar',
            // token: 'test',
            url: iCMS.url("editorVditor/upload"),
            linkToImgUrl: iCMS.url("editorVditor/fetch"),
            max: 10 * 1024 * 1024,
            fieldName: "upfile[]",
            multiple: true,
            linkToImgFormat: function (response) {
                var json = JSON.parse(response)||{};
                var ret = {
                    "msg": "",
                    "code": 0,
                    "data": {
                        "originalURL": '',
                        "url": {}
                    }
                };
                if (json.code > 0) {
                    ret.data.originalURL = json.data.originalURL;
                    ret.data.url = iCMS.utils.resUrl(json.data.url);
                } else {
                    iCMS.notify.error(json.message);
                }
                return JSON.stringify(ret);

            },
            format: function (File, response) {
                var json = JSON.parse(response)||{};
                var ret = {
                    "msg": "",
                    "code": 0,
                    "data": {
                        "errFiles": [],
                        "succMap": {}
                    }
                };
                if (json.code > 0) {
                    $.each(json.data, function (idx, f) {
                        ret.data.succMap[f['source']] = iCMS.utils.resUrl(f['path'])
                    });
                } else {
                    iCMS.notify.error(json.message);
                }
                return JSON.stringify(ret);
            },
            filename(name) {
                return name.replace(/[^(a-zA-Z0-9\u4e00-\u9fa5\.)]/g, '').
                    replace(/[\?\\/:|<>\*\[\]\(\)\$%\{\}@~]/g, '').
                    replace('/\\s/g', '')
            },
        },
        "after": function () {
            var vd = this.getInstance();
            // var value = this.getValue();
            top.console.log(vd.options);
            // vd.options.$el.text(value);
        },
        "input": function (value, vd) {
            // top.console.log(vd.options.$el,value);
            $(vd.options.$el).text(value);
        },
        "blur": function (value, vd) {
            // top.console.log(vd.options.$el,value);
            $(vd.options.$el).text(value);
        },
    }
});