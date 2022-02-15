$(function() {
    $($APP_FORMID).submit(function() {
        var name = $("#name").val();
        if (name == '') {
            $("#name").focus();
            iCMS.ui.alert("应用名称不能为空");
            return false;
        }
        var app = $("#_app").val();
        if (app == '') {
            $("#_app").focus();
            iCMS.ui.alert("应用标识不能为空");
            return false;
        }
    });

    // $("#type").change(function() {
    //     if (this.value == "3") {
    //         $('[name="apptype"]').val('1');
    //         $('#config_iFormer').val('0');
    //         $("#menu").data('data', $("#menu").text());
    //         $.get($APP_URL + "&do=menu_source", function(json) {
    //             $("#menu").text(json);
    //         });
    //     } else if (this.value == "2") {
    //         $('[name="apptype"]').val('2');
    //         $('#config_iFormer').val('1');
    //         var data = $("#menu").data('data');
    //         if (data) {
    //             $("#menu").text(data);
    //         }
    //     }
    // });
    $(".add_table_item").click(function() {
        // var clone = $("#table_item").clone();
        // console.log(clone);
        var key = $("#table_list").find('tr').size();
        var tr = $("<tr>");
        for (var i = 0; i < 4; i++) {
            var td = $("<td>");
            td.html('<input type="text" name="table[' + key + '][' + i + ']" class="form-control" id="table_' +
                key + '_' + i + '" value=""/>');
            tr.append(td);
        };
        tr.append(
            '<td class="type_3"><button class="btn btn-sm btn-danger del_table" type="button"><i class="fa fa-trash-alt"></i> 删除</button></td>'
        );
        $("#table_list").append(tr);
    });
    $(".add_route_item").click(function() {
        // var clone = $("#table_item").clone();
        // console.log(clone);
        var key = $("#routeList").find('tr').size();
        var tr = $("<tr>");
        for (var i = 0; i < 3; i++) {
            var td = $("<td>");
            td.html('<input class="form-control" type="text" name="route[' + key + '][' + i + ']" value=""/>');
            tr.append(td);
        };
        tr.append(
            '<td><button class="btn btn-danger del_route" type="button"><i class="fa fa-trash-alt"></i> 删除</button></td>'
        );
        $("#routeList").append(tr);
    });
    var doc = $(document);
    doc.on("click", ".del_table", function() {
        $(this).parent().parent().remove();
    });
    doc.on("click", ".del_route", function() {
        $(this).closest('tr').remove();
    });
})
iCMS.set('Vue.watch', {
    // vrootid: function(val, oldVal) {
    //     // this.menu = val;
    //     // $('#menu').val(val);
    //     var app = $("[value="+val+"]","#rootid").data('app');
    //     console.log(app);
    //     this.menu = app;
    // },
    vtype: function(val, oldVal) {
        this.iformer = (val == "2");
        if (val == "3"||val == "4") {
            this.vapptype = 1;
        } else if (val == "2") {
            this.vapptype = 2;
        }else{
            this.vapptype = 0;
        }
    },
})