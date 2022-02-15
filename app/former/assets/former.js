$($APP_FORMID).submit(function() {
    function validate() {};

    var submitBtn = $('button[type="submit"]', this);
    submitBtn.button('loading');
    iCMS.ui.dialog({
        id: "iPHP-DIALOG",
        content: "提交中，请稍候...",
        time: 10000,
        height: '150',
        icon: 'loading',
    }, function() {
        submitBtn.button('reset');
    });
});