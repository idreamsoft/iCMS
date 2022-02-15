$(function() {
    var edialog;
    $(".edit").dblclick(function() {
        var a = $(this),
            aid = a.attr("aid"),
            box = $('#ed-box'),
            title = $.trim($('.aTitle', a).text());
        $('#edcid,#edpid').empty();
        var edcid = $("#cid").clone().show().appendTo('#edcid'),
            edpid = $("#pid").clone().show().appendTo('#edpid'),
            edtitle = $('#edtitle', box).val(title),
            edtags = $('#edtags', box),
            edsource = $('#edsource', box),
            eddesc = $('#eddesc', box);

        // $(".chosen-select", box).chosen(chosen_config);

        $.getJSON($APP_URL, {
            'do': 'getJson',
            'id': aid
        }, function(ret) {
            if (ret.code) {
                edcid.val(ret.data.cid).trigger("chosen:updated");
                edpid.val(ret.data.pid).trigger("chosen:updated");
                edtags.val(ret.data.tags);
                edsource.val(ret.data.source);
                eddesc.val(ret.data.description);
            }
        });

        iCMS.ui.dialog({
            title: '简易编辑',
            content: document.getElementById('ed-box'),
            button: [{
                value: '保存',
                callback: function() {
                    var title = edtitle.val(),
                        cid = edcid.val();
                    if (title == "") {
                        iCMS.ui.alert("请填写标题!");
                        edtitle.focus();
                        return false;
                    }
                    if (cid == 0) {
                        iCMS.ui.alert("请选择栏目!");
                        return false;
                    }
                    $(box).trigger("chosen:updated");
                    $.post($APP_URL + "&do=simpleEdit&CSRF_TOKEN=" + $CSRF_TOKEN, {
                            id: aid,
                            cid: cid,
                            pid: edpid.val(),
                            title: title,
                            source: edsource.val(),
                            tags: edtags.val(),
                            description: eddesc.val()
                        },
                        function(res) {
                            if (res.code) {
                                $('.aTitle', a).text(title);
                                iCMS.ui.alert("修改完成!", true);
                            }
                        }, 'json');
                }
            }]
        });
    });
});
iCMS.set('batch', {
    scid: function() {
        return $("#scidBatch").clone(true);
    }
})