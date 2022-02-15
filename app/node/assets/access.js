var doc = $(document);
doc.on("click", '.node_access_all', function() {
    var target,
        dv = $(this).attr('data-value'),
        ty = $(this).attr('data-type'),
        checkedStatus = $(this).prop("checked");
    // $(".checkAll").prop("checked", checkedStatus);
    if (ty == 'v') {
        target = $("[value$='" + dv + "']");
    } else if (ty == 'r') {
        var nids = $.parseJSON(dv);
        $.each(nids, function(index, val) {
            var el = $('input:checkbox', $("[id='" + val + "']"));
            el.each(function() {
                this.checked = checkedStatus;
            });
        });
        var pp = $(this).parents('tr');
        target = $('input:checkbox', pp);
    } else {
        target = $("[value^='" + dv + "']");
    }
    target.each(function() {
        this.checked = checkedStatus;
    });
});