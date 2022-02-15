(function () {
    window.MDEDITOR_CONFIG = {
        // mode:'wysiwyg',
        mode: "ir",
        cache: { enable: false },
        minHeight: 500,
        value: "",
        toolbar: [
            "emoji",
            "headings",
            "bold",
            "italic",
            "strike",
            "link",
            "|",
            "list",
            "ordered-list",
            "check",
            "outdent",
            "indent",
            "|",
            "quote",
            "line",
            "code",
            "inline-code",
            "insert-before",
            "insert-after",
            "|",
            "upload",
            "record",
            "table",
            "|",
            "undo",
            "redo",
            "|",
            "fullscreen",
            "preview",
        ],
        after: function () {
            var ed = iCMS.mdeditor.get();
            iCMS.mdeditor.textarea().hide();
        },
        input: function (value) {
            iCMS.mdeditor.textarea().text(value);
        },
        blur: function (value) {
            iCMS.mdeditor.textarea().text(value);
        },
    };
    iCMS.mdeditor = {
        eid: "mdeditor",
        container: [],
        textarea: function (eid) {
            var ed = this.get(eid);
            var el = $(ed.vditor.element);
            return el.next("textarea");
        },
        getContent: function (eid) {
            eid = eid || this.eid;
            var content = this.get(eid).getValue();
            if (content && content != "\n") {
                return content;
            }
            return "";
        },
        hasContents: function (eid) {
            eid = eid || this.eid;
            if (this.getContent(eid)) {
                return true;
            }
            return false;
        },
        get: function (eid) {
            if (eid) this.eid = eid;
            var ed = this.container[this.eid] || this.create();
            return ed;
        },
        create: function (eid) {
            if (eid) this.eid = eid;
            var ed = new Vditor(this.eid, window.MDEDITOR_CONFIG);
            this.container[this.eid] = ed;
            return ed;
        },
        destroy: function (eid) {
            eid = eid || this.eid;
            this.get(eid).destroy();
            this.container[eid] = null;
        },
        insPageBreak: function (argument) {
            var ed = this.get();
            ed.insertValue("\n#--iCMS.PageBreak--#\n");
            ed.focus();
        },
        delPageBreakflag: function () {
            var ed = this.get(),
                text = ed.getValue();
            text = text.replace(/#--iCMS\.PageBreak--#/g, "");
            ed.setValue(text);
            ed.focus();
        },
        cleanup: function (eid) {},
    };
})();
