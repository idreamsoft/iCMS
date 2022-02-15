var doc = $(document);

doc.on("click", '.menu_access', function() {
    menu_access(this)
});

function menu_access(a) {
    var target, mid = $(a).attr('mid'),
        pid = $(a).attr('pid'),
        checkedStatus = $(a).prop("checked");
    target = $('[pid="' + mid + '"]');
    var value = $(a).val();
    $("[value='" + value + "']").prop("checked", checkedStatus);

    if (pid != "0") {
        $("[mid='" + pid + "']").prop("checked", checkedStatus);
    }
    var all = $('[pid="' + pid + '"]:checked');
    $("[mid='" + pid + "']").prop("checked", (all.length > 0));
    target.each(function() {
        this.checked = checkedStatus;
        menu_access(this);
    });

}