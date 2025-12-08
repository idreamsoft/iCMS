define(["admincp"], function (admincp) {
    return function () {
        $("#rsapp").on('change', function (e) {
            var fun = $(this).find('option[value="' + this.value + '"]').attr('fun');
            $("#fun").val(fun);
            var tipMap = {
                'forms': '自定义表单 需要填写 form_id',
                'content': '自定义应用 需要填写 appid'
            }
            var tip = tipMap[this.value] || '';
            $(".post-tip").addClass('hide').removeClass('hide').html(tip);
        });
    }
});